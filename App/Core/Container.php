<?php
declare(strict_types=1);

namespace App\Core;

use App\Controller\AdminController;
use App\Controller\AddressController;
use App\Controller\AuthController;
use App\Controller\ContactController;
use App\Controller\EmailController;
use App\Controller\OrderController;
use App\Controller\PublicController;
use App\Controller\QuoteController;
use App\Controller\NotificationController;
use App\Repository\CommentRepository;
use App\Repository\DishRepository;
use App\Repository\MenuRepository;
use App\Repository\OpeningHoursRepository;
use App\Repository\OrderRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Service\AdminService;
use App\Service\AuthService;
use App\Service\CacheService;
use App\Service\CommentService;
use App\Service\ContactService;
use App\Service\EmailService;
use App\Service\EmailTemplateRenderer;
use App\Service\MailService;
use App\Service\MenuService;
use App\Service\MenuStatisticsService;
use App\Service\OrderService;
use App\Service\QuoteService;
use App\Service\NotificationService;
use App\Middleware\Admin;
use App\Middleware\Auth;
use App\Middleware\Guest;
use App\Middleware\Security;
use App\Middleware\Staff;

use RuntimeException;

// The application classes are loaded by Composer at runtime. Keep the
// container independent from the editor's class-indexing state.
/** @noinspection PhpUndefinedClassInspection */
final class Container
{
    private array $factories = [];
    private array $instances = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new RuntimeException('Service introuvable : ' . $id);
        }

        $instance = ($this->factories[$id])($this);

        if (!is_object($instance)) {
            throw new RuntimeException('Le service doit retourner un objet : ' . $id);
        }

        $this->instances[$id] = $instance;

        return $instance;
    }

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    private function registerDefaults(): void
    {
        $this->set(
            UserRepository::class,
            static fn (): UserRepository => new UserRepository()
        );

        $this->set(
            MenuRepository::class,
            static fn (): MenuRepository => new MenuRepository()
        );

        $this->set(
            OrderRepository::class,
            static fn (): OrderRepository => new OrderRepository()
        );

        $this->set(
            CommentRepository::class,
            static fn (Container $container): CommentRepository => new CommentRepository(
                $container->get(UserRepository::class)
            )
        );

        $this->set(
            DishRepository::class,
            static fn (): DishRepository => new DishRepository()
        );

        $this->set(
            OpeningHoursRepository::class,
            static fn (): OpeningHoursRepository => new OpeningHoursRepository()
        );

        $this->set(
            CacheService::class,
            static fn (): CacheService => new CacheService()
        );

        $this->set(
            ContactService::class,
            static fn (): ContactService => new ContactService()
        );

        $this->set(
            MailService::class,
            static fn (): MailService => new MailService(
                (string) config('mail.from_address', 'noreply@viteetgourmand.com'),
                (string) config('mail.from_name', 'Vite & Gourmand')
            )
        );

        $this->set(
            EmailTemplateRenderer::class,
            static fn (): EmailTemplateRenderer => new EmailTemplateRenderer(
                dirname(__DIR__, 2) . '/email-templates'
            )
        );

        $this->set(
            EmailService::class,
            static fn (Container $container): EmailService => new EmailService(
                $container->get(MailService::class),
                $container->get(EmailTemplateRenderer::class)
            )
        );

        $this->set(
            MenuService::class,
            static fn (Container $container): MenuService => new MenuService(
                $container->get(MenuRepository::class),
                $container->get(CacheService::class)
            )
        );

        $this->set(
            AuthService::class,
            static fn (Container $container): AuthService => new AuthService(
                $container->get(UserRepository::class)
            )
        );

        $this->set(
            CommentService::class,
            static fn (Container $container): CommentService => new CommentService(
                $container->get(CommentRepository::class),
                $container->get(OrderRepository::class),
                $container->get(CacheService::class)
            )
        );

        $this->set(
            MenuStatisticsService::class,
            static fn (Container $container): MenuStatisticsService => new MenuStatisticsService(
                $container->get(OrderRepository::class),
                $container->get(MenuRepository::class)
            )
        );

        $this->set(
            OrderService::class,
            static fn (Container $container): OrderService => new OrderService(
                $container->get(OrderRepository::class),
                $container->get(UserRepository::class),
                $container->get(MenuRepository::class),
                $container->get(MailService::class),
                $container->get(MenuStatisticsService::class),
                $container->get(NotificationService::class)
            )
        );

        $this->set(
            AdminService::class,
            static fn (Container $container): AdminService => new AdminService(
                $container->get(UserRepository::class),
                $container->get(OrderRepository::class),
                $container->get(MenuRepository::class),
                $container->get(MenuStatisticsService::class)
            )
        );

        $this->set(
            PublicController::class,
            static fn (Container $container): PublicController => new PublicController(
                $container->get(MenuService::class),
                $container->get(CommentService::class)
            )
        );

        $this->set(
            AuthController::class,
            static fn (Container $container): AuthController => new AuthController(
                $container->get(AuthService::class),
                $container->get(UserRepository::class),
                $container->get(MailService::class)
            )
        );

        $this->set(
            OrderController::class,
            static fn (Container $container): OrderController => new OrderController(
                $container->get(OrderService::class),
                $container->get(UserRepository::class),
                $container->get(MenuService::class),
                $container->get(CommentService::class)
            )
        );

        $this->set(
            AdminController::class,
            static fn (Container $container): AdminController => new AdminController(
                $container->get(AdminService::class),
                $container->get(AuthService::class),
                $container->get(MenuService::class),
                $container->get(CommentService::class),
                $container->get(OrderService::class),
                $container->get(DishRepository::class),
                $container->get(OpeningHoursRepository::class),
                $container->get(QuoteService::class)
            )
        );

        $this->set(
            AddressController::class,
            static fn (): AddressController => new AddressController()
        );

        $this->set(
            ContactController::class,
            static fn (Container $container): ContactController => new ContactController(
                $container->get(ContactService::class),
                $container->get(MailService::class),
                $container->get(NotificationService::class)
            )
        );

        $this->set(
            EmailController::class,
            static fn (Container $container): EmailController => new EmailController(
                $container->get(EmailService::class)
            )
        );

        $this->set(
            NotificationRepository::class,
            static fn (): NotificationRepository => new NotificationRepository()
        );

        $this->set(
            NotificationService::class,
            static fn (Container $container): NotificationService => new NotificationService(
                $container->get(NotificationRepository::class),
                $container->get(UserRepository::class)
            )
        );

        $this->set(
            QuoteService::class,
            static fn (Container $container): QuoteService => new QuoteService(
                $container->get(MailService::class),
                $container->get(NotificationService::class),
                $container->get(AuthService::class)
            )
        );

        $this->set(
            QuoteController::class,
            static fn (Container $container): QuoteController => new QuoteController(
                $container->get(QuoteService::class)
            )
        );

        $this->set(
            NotificationController::class,
            static fn (Container $container): NotificationController => new NotificationController(
                $container->get(NotificationService::class)
            )
        );

        $this->set(
            Auth::class,
            static fn (Container $container): Auth => new Auth(
                $container->get(UserRepository::class)
            )
        );

        $this->set(
            Security::class,
            static fn (Container $container): Security => new Security(
                $container->get(CacheService::class)
            )
        );

        $this->set(
            Admin::class,
            static fn (Container $container): Admin => new Admin(
                $container->get(Auth::class)
            )
        );

        $this->set(
            Staff::class,
            static fn (Container $container): Staff => new Staff(
                $container->get(Auth::class)
            )
        );

        $this->set(
            Guest::class,
            static fn (): Guest => new Guest()
        );
    }
}
