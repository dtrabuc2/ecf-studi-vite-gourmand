<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repository\OpeningHoursRepository;

abstract class BaseController
{
    protected function render(string $template, array $data = []): void
    {
        try {
            $data['openingHours'] ??= (new OpeningHoursRepository())->findAll();
        } catch (\Throwable $exception) {
            error_log('Impossible de charger les horaires : ' . $exception->getMessage());
            $data['openingHours'] = [];
        }

        $content = View::render($template, $data);

        $layoutData = array_merge($data, [
            'content' => $content,
            'title' => $data['title'] ?? $this->titleFromTemplate($template),
            'user' => Session::id(),
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? '',
            'role' => $_SESSION['role'] ?? '',
            'csrfToken' => Session::csrfToken(),
        ]);

        echo View::render('layout/layout', $layoutData);
    }

    protected function json(array $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function redirect(string $location, int $status = 302): never
    {
        Response::redirect($location, $status);
    }

    private function titleFromTemplate(string $template): string
    {
        $label = str_replace(['/', '_'], ' ', trim($template, '/'));

        return ucfirst($label) . ' - Vite & Gourmand';
    }
}