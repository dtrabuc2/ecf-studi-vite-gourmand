<?php
declare(strict_types=1);

namespace App\Core;

use App\Controller\ErrorController;
use RuntimeException;

final readonly class Application
{
    private Router $router;
    private Container $container;

    private function __construct()
    {
        require_once __DIR__ . '/functions.php';

        $this->loadEnvironment();
        date_default_timezone_set((string) ($_ENV['APP_TIMEZONE'] ?? 'Europe/Paris'));
        $this->configureErrorHandling();
        $this->configureSession();

        $this->container = new Container();
        $this->router = new Router($this->container);
        $this->router->setRoutes(
            require dirname(__DIR__, 2) . '/config/routes.php'
        );
    }

    public static function boot(): self
    {
        return new self();
    }

    public function run(): void
    {
        try {
            $this->router->dispatch();
        } catch (\Throwable $exception) {
            error_log($exception->__toString());

            if ((bool) config('app.debug', false)) {
                throw $exception;
            }

            $this->renderServerError();
        }
    }

    /**
     * Page 500 en HTML (JSON pour les appels d'API), sans détail technique.
     * Si la page elle-même ne peut pas être affichée, repli sur un texte simple.
     */
    private function renderServerError(): void
    {
        // Abandonne une éventuelle sortie partielle de la page en échec.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        try {
            $controller = $this->container->get(ErrorController::class);

            if ($controller instanceof ErrorController) {
                $controller->serverError();
                return;
            }
        } catch (\Throwable $exception) {
            error_log('Page d’erreur indisponible : ' . $exception->getMessage());
        }

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
        }

        echo 'Une erreur interne est survenue.';
    }

    private function loadEnvironment(): void
    {
        $envFile = dirname(__DIR__, 2) . '/.env';

        if (!is_readable($envFile)) {
            return;
        }

        $lines = file(
            $envFile,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if (
                $line === ''
                || str_starts_with($line, '#')
                || !str_contains($line, '=')
            ) {
                continue;
            }

            [$key, $value] = array_map(
                trim(...),
                explode('=', $line, 2)
            );

            $value = trim($value, "\"'");

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }

    private function configureErrorHandling(): void
    {
        error_reporting(E_ALL);

        $debug = (bool) config('app.debug', false);

        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
    }

    private function configureSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $session = is_array(config('session', [])) ? config('session', []) : [];

        session_name(
            (string) ($session['name'] ?? 'viteetgourmand_session')
        );

        session_set_cookie_params([
            'lifetime' => (int) ($session['lifetime'] ?? 120) * 60,
            'path' => '/',
            'domain' => (string) ($session['domain'] ?? ''),
            'secure' => (bool) ($session['secure'] ?? false),
            'httponly' => true,
            'samesite' => isset($session['samesite']) && is_string($session['samesite'])
                ? $session['samesite']
                : 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');

        if (!session_start()) {
            throw new RuntimeException('Impossible de démarrer la session.');
        }
    }
}
