<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Service\NotificationService;

final class NotificationController extends BaseController
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function index(): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $notifications = $this->notificationService->forUser($userId);
        $this->notificationService->markAllRead($userId);

        $this->render('notification/index', [
            'notifications' => $notifications,
        ]);
    }

    public function read(int $id): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $this->notificationService->markRead($id, $userId);
        $this->redirect('/notifications');
    }
}
