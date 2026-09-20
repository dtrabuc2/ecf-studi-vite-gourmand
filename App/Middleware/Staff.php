<?php
namespace App\Middleware;

use App\Repository\UserRepository;

class Staff
{
    public function __invoke(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $role = $_SESSION['role'] ?? '';

        if ($userId <= 0 || !in_array($role, ['employee', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        if (!(new UserRepository())->isActive($userId)) {
            $_SESSION = [];
            session_destroy();
            header('Location: /admin/login');
            exit;
        }
    }
}
