<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Response;
use App\Core\Session;

final class Admin
{
    public function __construct(
        private readonly Auth $auth
    ) {
    }

    public function __invoke(): void
    {
        $this->auth();

        if (Session::role() !== 'admin') {
            Response::redirect('/admin/login');
        }
    }
}
