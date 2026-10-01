<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\MailboxRepository;

final readonly class ContactService
{
    public function __construct(
        private MailboxRepository $mailboxRepository
    ) {
    }

    public function createMessage(string $email, string $subject, string $message): int
    {
        return $this->mailboxRepository->createContactMessage($email, $subject, $message);
    }
}
