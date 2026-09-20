<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Response;
use App\Core\Session;

final class Staff
{
    public function __invoke(): void
    {
        (new Auth())();

        if (!in_array(
            Session::role(),
            ['employee', 'admin'],
            true
        )) {
            Response::redirect('/login');
        }
    }
}