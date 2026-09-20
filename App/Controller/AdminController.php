<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Repository\DishRepository;
use App\Repository\OpeningHoursRepository;
use App\Service\AdminService;
use App\Service\AuthService;
use App\Service\CommentService;
use App\Service\MenuService;
use App\Service\OrderService;
use App\Service\QuoteService;
use Throwable;

final class AdminController extends BaseController
{
    public function __construct(
        private readonly AdminService $adminService,
        private readonly AuthService $authService,
        private readonly MenuService $menuService,
        private readonly CommentService $commentService,
        private readonly OrderService $orderService,
        private readonly DishRepository $dishRepository,
        private readonly OpeningHoursRepository $openingHoursRepository,
        private readonly QuoteService $quoteService
    ) {
    }

    public function showLogin(): void
    {
        $this->render('auth/admin_login');
    }

    public function login(): void
    {
        $user = $this->authService->login(
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? '')
        );

        if ($user === null) {
            Session::flash('login_error', 'Identifiants invalides ou compte indisponible.');
            $this->redirect('/admin/login');
        }

        $role = $user->getRole();

        if ($role === 'user') {
            Session::flash(
                'login_error',
                'Ce compte est un compte client. Utilisez la connexion client.'
            );
            $this->redirect('/login');
        }

        if (!in_array($role, ['employee', 'admin'], true)) {
            Session::flash(
                'login_error',
                'Le rôle de ce compte ne permet pas d’accéder à l’espace équipe.'
            );
            $this->redirect('/admin/login');
        }

        Session::login($user->getId(), $role, [
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
        ]);

        $this->redirect($role === 'admin' ? '/admin/dashboard' : '/admin/orders');
    }

    public function dashboard(): void
    {
        $this->render('admin/dashboard', [
            'stats' => $this->adminService->getDashboardStats(),
        ]);
    }

    public function orders(): void
    {
        $status = trim((string) ($_GET['status'] ?? ''));
        $customer = trim((string) ($_GET['customer'] ?? ''));

        $this->render('admin/orders', [
            'orders' => $this->orderService->getOrdersForStaff(
                $status !== '' ? $status : null,
                $customer !== '' ? $customer : null
            ),
            'selectedStatus' => $status,
            'customer' => $customer,
        ]);
    }

    public function quotes(): void
    {
        $status = trim((string) ($_GET['status'] ?? ''));

        $this->render('admin/quotes', [
            'quotes' => $this->quoteService->findForStaff($status !== '' ? $status : null),
            'selectedStatus' => $status,
        ]);
    }

    public function updateQuoteStatus(int $id): void
    {
        $status = trim((string) ($_POST['status'] ?? ''));
        $reply = trim((string) ($_POST['reply'] ?? ''));

        try {
            $this->quoteService->updateStatus($id, $status, $reply);
            Session::flash('admin_success', 'Demande de devis mise à jour et client informé par email.');
        } catch (Throwable $exception) {
            Session::flash('admin_error', $exception->getMessage());
        }

        $this->redirect('/admin/quotes');
    }

    public function updateOrderStatus(int $id): void
    {
        $status = trim((string) ($_POST['status'] ?? ''));
        $reason = trim((string) ($_POST['cancellation_reason'] ?? ''));
        $contactMode = trim((string) ($_POST['contact_mode'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $equipmentLoaned = array_key_exists('equipment_loaned', $_POST)
            ? (string) $_POST['equipment_loaned'] === '1'
            : false;

        if ($contactMode === '') {
            Session::flash('admin_error', 'Le mode de contact du client est obligatoire avant toute modification de commande.');
            $this->redirect('/admin/orders');
        }

        if ($status === 'cancelled' && $reason === '') {
            Session::flash('admin_error', 'Pour une annulation, le motif est obligatoire.');
            $this->redirect('/admin/orders');
        }

        $notes = 'Contact client : ' . $contactMode
            . ($notes !== '' ? ' — ' . $notes : '');

        try {
            $this->orderService->updateOrderStatus(
                $id,
                $status,
                (int) Session::id(),
                $notes,
                $reason !== '' ? $reason : null,
                $equipmentLoaned
            );
            Session::flash('admin_success', 'Statut de la commande mis à jour.');
        } catch (Throwable $exception) {
            Session::flash('admin_error', $exception->getMessage());
        }

        $this->redirect('/admin/orders');
    }

    public function dishes(): void
    {
        $this->render('admin/dishes', [
            'dishes' => $this->dishRepository->findAll(),
            'menus' => $this->menuService->getAllMenus(),
        ]);
    }

    public function createDish(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $menuId = isset($_POST['menu_id']) && is_numeric($_POST['menu_id'])
            ? (int) $_POST['menu_id']
            : null;
        $category = isset($_POST['category']) ? (string) $_POST['category'] : null;

        if ($name === '') {
            Session::flash('admin_error', 'Le nom du plat est requis.');
            $this->redirect('/admin/dishes');
        }

        try {
            $this->dishRepository->create($name, $description, $menuId, $category);
            Session::flash('admin_success', 'Plat créé.');
        } catch (Throwable $exception) {
            Session::flash('admin_error', $exception->getMessage());
        }

        $this->redirect('/admin/dishes');
    }

    public function updateDish(int $id): never
    {
        $this->dishRepository->update(
            $id,
            trim((string) ($_POST['name'] ?? '')),
            trim((string) ($_POST['description'] ?? '')),
            isset($_POST['menu_id']) && is_numeric($_POST['menu_id']) ? (int) $_POST['menu_id'] : null,
            isset($_POST['category']) ? (string) $_POST['category'] : null
        );

        Session::flash('admin_success', 'Plat modifié.');
        $this->redirect('/admin/dishes');
    }

    public function deleteDish(int $id): void
    {
        try {
            $this->dishRepository->delete($id);
            Session::flash('admin_success', 'Plat supprimé.');
        } catch (Throwable) {
            Session::flash('admin_error', 'Impossible de supprimer ce plat : il est peut-être encore associé à un menu.');
        }

        $this->redirect('/admin/dishes');
    }

    public function openingHours(): void
    {
        $this->render('admin/opening_hours', [
            'openingHours' => $this->openingHoursRepository->findAll(),
        ]);
    }

    public function updateOpeningHours(): never
    {
        for ($day = 1; $day <= 7; $day++) {
            $isOpen = isset($_POST['is_open'][$day]);
            $opening = $isOpen ? trim((string) ($_POST['opening_time'][$day] ?? '')) : null;
            $closing = $isOpen ? trim((string) ($_POST['closing_time'][$day] ?? '')) : null;

            $this->openingHoursRepository->saveDay(
                $day,
                $isOpen,
                $opening !== '' ? $opening : null,
                $closing !== '' ? $closing : null
            );
        }

        Session::flash('admin_success', 'Horaires mis à jour.');
        $this->redirect('/admin/hours');
    }

    public function customers(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $active = isset($_GET['active']) && $_GET['active'] !== ''
            ? (string) $_GET['active'] === '1'
            : null;

        $this->render('admin/customers', [
            'customers' => $this->adminService->getCustomers($search !== '' ? $search : null, $active),
            'search' => $search,
            'active' => $active,
        ]);
    }

    public function disableCustomer(int $id): never
    {
        $this->adminService->disableCustomer($id);
        $this->redirect('/admin/customers');
    }

    public function enableCustomer(int $id): never
    {
        $this->adminService->enableCustomer($id);
        $this->redirect('/admin/customers');
    }

    public function createEmployee(): void
    {
        $data = [
            'email' => trim((string) ($_POST['email'] ?? '')),
            'password' => (string) ($_POST['password'] ?? ''),
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'gsm' => trim((string) ($_POST['gsm'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
        ];

        try {
            $this->adminService->createEmployee($data);

            Session::flash('admin_success', 'Employé créé avec succès.');
        } catch (Throwable $exception) {
            Session::flash('admin_error', $exception->getMessage());
        }

        $this->redirect('/admin/employees');
    }

    public function getEmployees(): void
    {
        $this->render('admin/employees', [
            'employees' => $this->adminService->getEmployees(),
        ]);
    }

    public function disableEmployee(int $id): never
    {
        $this->adminService->disableEmployee($id);
        $this->json(['success' => true]);
    }

    public function enableEmployee(int $id): never
    {
        $this->adminService->enableEmployee($id);
        $this->json(['success' => true]);
    }

    public function getMenus(): void
    {
        $this->render('admin/menus', [
            'menus' => $this->menuService->getAllMenus(),
        ]);
    }

    public function createMenu(): void
    {
        try {
            $this->menuService->createMenu($_POST);
            Session::flash('menu_success', 'Menu créé avec succès.');
        } catch (Throwable $exception) {
            Session::flash('menu_error', $exception->getMessage());
        }

        $this->redirect('/admin/menus');
    }

    public function updateMenu(int $id): void
    {
        try {
            $this->menuService->updateMenu($id, $_POST);
            Session::flash('menu_success', 'Menu mis à jour avec succès.');
        } catch (Throwable $exception) {
            Session::flash('menu_error', $exception->getMessage());
        }

        $this->redirect('/admin/menus');
    }

    public function deleteMenu(int $id): void
    {
        try {
            $this->menuService->deleteMenu($id);
            $this->json(['success' => true]);
        } catch (Throwable $exception) {
            $this->json(['success' => false, 'error' => $exception->getMessage()], 500);
        }
    }

    public function getPendingComments(): void
    {
        $this->render('admin/comments', [
            'comments' => $this->commentService->getPendingComments(),
        ]);
    }

    public function validateComment(string $id): never
    {
        $this->commentService->validateComment($id);
        $this->json(['success' => true]);
    }

    public function rejectComment(string $id): never
    {
        $this->commentService->rejectComment($id);
        $this->json(['success' => true]);
    }

    public function revenuePage(): void
    {
        $from = trim((string) ($_GET['from'] ?? ''));
        $to = trim((string) ($_GET['to'] ?? ''));
        $menuId = isset($_GET['menu_id']) && is_numeric($_GET['menu_id'])
            ? (int) $_GET['menu_id']
            : null;

        $this->render('admin/revenue', [
            'revenue' => $this->adminService->getRevenueByMenu(
                $from !== '' ? $from : null,
                $to !== '' ? $to : null,
                $menuId
            ),
            'menus' => $this->menuService->getAllMenus(),
            'from' => $from,
            'to' => $to,
            'menuId' => $menuId,
        ]);
    }

    public function revenueByMenu(): never
    {
        $from = isset($_GET['from']) ? trim((string) $_GET['from']) : null;
        $to = isset($_GET['to']) ? trim((string) $_GET['to']) : null;
        $menuId = isset($_GET['menu_id']) && is_numeric($_GET['menu_id'])
            ? (int) $_GET['menu_id']
            : null;

        $this->json(
            $this->adminService->getRevenueByMenu($from, $to, $menuId)
        );
    }
}
