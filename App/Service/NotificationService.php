<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\NotificationRepository;

final readonly class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {
    }

    public function notify(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?int $orderId = null,
        ?int $quoteRequestId = null
    ): int {
        return $this->notificationRepository->create(
            $userId,
            $type,
            $title,
            $message,
            $orderId,
            $quoteRequestId
        );
    }

    public function forUser(int $userId, int $limit = 30): array
    {
        return $this->notificationRepository->findForUser($userId, $limit);
    }

    public function unreadCount(int $userId): int
    {
        return $this->notificationRepository->countUnread($userId);
    }

    public function markRead(int $id, int $userId): void
    {
        $this->notificationRepository->markRead($id, $userId);
    }

    public function markAllRead(int $userId): void
    {
        $this->notificationRepository->markAllRead($userId);
    }
}
