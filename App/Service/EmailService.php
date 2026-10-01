<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\MailboxRepository;
use InvalidArgumentException;

final readonly class EmailService
{
    public function __construct(
        private MailService $mailService,
        private EmailTemplateRenderer $templateRenderer,
        private MailboxRepository $mailboxRepository
    ) {
    }

    public function listInbox(string $mailbox = 'contact'): array
    {
        return $this->mailboxRepository->findInbox($mailbox);
    }

    public function listFolder(string $mailbox, string $folder): array
    {
        return match ($folder) {
            'inbox' => $this->listInbox($mailbox),
            'sent' => $this->mailboxRepository->findOutboxByStatus($mailbox, 'sent'),
            'drafts' => $this->mailboxRepository->findOutboxByStatus($mailbox, 'draft'),
            default => $this->mailboxRepository->findTrash($mailbox),
        };
    }

    public function findMessage(int $id): ?array
    {
        return $this->mailboxRepository->findContactMessage($id);
    }

    public function findFolderMessage(string $mailbox, string $folder, int $id): ?array
    {
        if (in_array($folder, ['sent', 'drafts'], true)) {
            return $this->mailboxRepository->findOutboxMessage($mailbox, $id);
        }

        return $this->findMessage($id);
    }

    public function listOutbox(string $mailbox): array
    {
        return $this->mailboxRepository->findRecentOutbox($mailbox);
    }

    public function markStatus(int $id, string $status): void
    {
        if (!in_array($status, ['new', 'processed', 'closed', 'trash'], true)) {
            throw new InvalidArgumentException('Statut email invalide.');
        }

        $this->mailboxRepository->updateContactStatus($id, $status);
    }

    public function reply(int $id, string $body): bool
    {
        $message = $this->findMessage($id);

        if ($message === null) {
            throw new InvalidArgumentException('Message introuvable.');
        }

        $body = trim($body);
        if ($body === '') {
            throw new InvalidArgumentException('Le contenu de la réponse est requis.');
        }

        $subject = 'Re: ' . $message['subject'];
        $sent = $this->mailService->send((string) $message['email'], $subject, $this->renderHtml($subject, $body), true);

        $this->mailboxRepository->addToOutbox(
            str_ends_with((string) $message['email'], '@mail.ai') ? 'mail.ai' : 'contact',
            (string) $message['email'],
            $subject,
            $body,
            $sent ? 'sent' : 'failed',
            $sent ? null : 'Serveur SMTP indisponible.'
        );

        if ($sent) {
            $this->markStatus($id, 'processed');
        }

        return $sent;
    }

    public function simulateIncoming(string $email, string $subject, string $body): int
    {
        if (!preg_match('/^[^@\s]+@mail\.ai$/i', $email)) {
            throw new InvalidArgumentException('La simulation utilise exclusivement une adresse @mail.ai.');
        }

        if (trim($subject) === '' || trim($body) === '') {
            throw new InvalidArgumentException('Sujet et contenu sont requis.');
        }

        return $this->mailboxRepository->createContactMessage(
            strtolower(trim($email)),
            trim($subject),
            trim($body)
        );
    }

    public function sendTest(string $recipient, string $subject, string $body): bool
    {
        if (!preg_match('/^[^@\s]+@mail\.ai$/i', $recipient)) {
            throw new InvalidArgumentException('Un envoi de test utilise exclusivement une adresse @mail.ai.');
        }

        if (trim($subject) === '' || trim($body) === '') {
            throw new InvalidArgumentException('Sujet et contenu sont requis.');
        }

        $sent = $this->mailService->send($recipient, $subject, $this->renderHtml($subject, $body), true);

        $this->mailboxRepository->addToOutbox(
            'mail.ai',
            strtolower(trim($recipient)),
            trim($subject),
            trim($body),
            $sent ? 'sent' : 'failed',
            $sent ? null : 'Serveur SMTP indisponible.'
        );

        return $sent;
    }

    public function saveDraft(string $mailbox, string $recipient, string $subject, string $body): int
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || trim($subject) === '') {
            throw new InvalidArgumentException('Destinataire et sujet valides requis.');
        }

        return $this->mailboxRepository->addToOutbox(
            $mailbox,
            strtolower(trim($recipient)),
            trim($subject),
            trim($body),
            'draft'
        );
    }

    public function sendComposed(string $mailbox, string $recipient, string $subject, string $body): bool
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || trim($subject) === '' || trim($body) === '') {
            throw new InvalidArgumentException('Destinataire, sujet et contenu sont requis.');
        }

        $sent = $this->mailService->send($recipient, $subject, $this->renderHtml($subject, $body), true);

        $this->mailboxRepository->addToOutbox(
            $mailbox,
            strtolower(trim($recipient)),
            trim($subject),
            trim($body),
            $sent ? 'sent' : 'failed',
            $sent ? null : 'Serveur SMTP indisponible.'
        );

        return $sent;
    }

    public function moveToTrash(string $source, int $id): void
    {
        if ($source === 'outbox') {
            $this->mailboxRepository->trashOutboxMessage($id);
            return;
        }

        $this->mailboxRepository->updateContactStatus($id, 'trash');
    }

    private function renderHtml(string $subject, string $body): string
    {
        return $this->templateRenderer->render('contact-reply.html', [
            'subject' => $subject,
            'message_html' => nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            'contact_email' => $this->mailService->companyAddress(),
        ]);
    }
}
