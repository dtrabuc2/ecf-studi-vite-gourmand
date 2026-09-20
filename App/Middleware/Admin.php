<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;
use App\Repository\UserRepository;

final class Admin
{
    public function __invoke(): void
    {
        $userId = Session::id();
        $role = $_SESSION['role'] ?? '';

        if ($userId === null || $role !== 'admin') {
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