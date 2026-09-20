<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

final class Guest
{
    public function __invoke(): void
    {
        if (Session::id() !== null) {
            header('Location: /');
            exit;
        }
    }
}