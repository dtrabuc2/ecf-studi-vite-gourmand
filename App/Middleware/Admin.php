<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Response;
use App\Core\Session;

final class Admin
{
    public function __invoke(): void
    {
        (new Auth())();

        if (Session::role() !== 'admin') {
            Response::redirect('/login');
        }
    }
}