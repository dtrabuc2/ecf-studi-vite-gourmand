<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;
use App\Repository\UserRepository;

final class Auth
{
    public function __invoke(): void
    {
        $userId = Session::id();

        if ($userId === null) {
            header('Location: /login');
            exit;
        }

        $user = (new UserRepository())->findById($userId);

        if ($user === null || !(new UserRepository())->isActive($userId)) {
            Session::logout();
            header('Location: /login');
            exit;
        }
    }
}