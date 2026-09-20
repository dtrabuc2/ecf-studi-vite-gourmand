<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;
use App\Repository\UserRepository;

final class Staff
{
    public function __invoke(): void
    {
        $userId = Session::id();
        $role = $_SESSION['role'] ?? '';

        if ($userId === null || !in_array($role, ['employee', 'admin'], true)) {
            header('Location: /login');
            exit;
        }

        if (!(new UserRepository())->isActive($userId)) {
            Session::logout();
            header('Location: /admin/login');
            exit;
        }
    }
}