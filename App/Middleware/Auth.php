<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Response;
use App\Core\Session;
use App\Repository\UserRepository;

final class Auth
{
    public function __invoke(): void
    {
        $userId = Session::id();

        if ($userId === null) {
            Response::redirect('/login');
        }

        $user = (new UserRepository())->findById($userId);

        if ($user === null || !(new UserRepository())->isActive($userId)) {
            Session::logout();
            Response::redirect('/login');
        }

        if (Session::role() !== $user->getRole()) {
            Session::login(
                $user->getId(),
                $user->getRole(),
                [
                    'email' => $user->getEmail(),
                    'first_name' => $user->getFirstName(),
                    'last_name' => $user->getLastName(),
                ]
            );
        }
    }
}
