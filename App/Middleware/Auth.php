<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repository\UserRepository;

final readonly class Auth
{
    public function __construct(
        private UserRepository $repository
    ) {
    }

    public function __invoke(): void
    {
        $userId = Session::id();

        if ($userId === null) {
            // page demandée gardée pour y revenir après la connexion (pas pour un fetch ni un POST)
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !Request::wantsJson()) {
                Session::rememberReturnUrl((string) ($_SERVER['REQUEST_URI'] ?? ''));
            }

            Response::redirect('/login');
        }

        $user = $this->repository->findById($userId);

        if ($user === null || !$this->repository->isActive($userId)) {
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
