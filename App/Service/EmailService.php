<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use InvalidArgumentException;

final readonly class EmailService
{
    public function __construct(
        private MailService $mailService,
        private EmailTemplateRenderer $templateRenderer
    ) {
    }

    public function listInbox(string $mailbox = 'contact'): array
    {
        $pdo = Database::pdo();
        $sql = "SELECT id, email, subject, message, status, created_at, 'inbox' AS source
            FROM contact_messages";
        $params = [];

        if ($mailbox === 'mail.ai') {
            $sql .= " WHERE email LIKE :domain AND status <> 'trash'";
            $params['domain'] = '%@mail.ai';
        } else {
            $sql .= " WHERE email NOT LIKE :domain AND status <> 'trash'";
            $params['domain'] = '%@mail.ai';
        }

        $sql .= ' ORDER BY created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function listFolder(string $mailbox, string $folder): array
    {
        if ($folder === 'inbox') {
            return $this->listInbox($mailbox);
        }

        if (in_array($folder, ['sent', 'drafts'], true)) {
            $status = $folder === 'sent' ? 'sent' : 'draft';
            $stmt = Database::pdo()->prepare(
                "SELECT id, recipient AS email, subject, body AS message, status, created_at, 'outbox' AS source
                 FROM email_outbox WHERE mailbox = :mailbox AND status = :status
                 ORDER BY created_at DESC"
            );
            $stmt->execute(['mailbox' => $mailbox, 'status' => $status]);
            return $stmt->fetchAll();
        }

        $stmt = Database::pdo()->prepare(
            "SELECT id, email, subject, message, status, created_at, 'inbox' AS source
                         FROM contact_messages WHERE email " . ($mailbox === 'mail.ai' ? "LIKE '%@mail.ai'" : "NOT LIKE '%@mail.ai'") . " AND status = 'trash'
                         UNION ALL
                         SELECT id, recipient AS email, subject, body AS message, status, created_at, 'outbox' AS source
             FROM email_outbox WHERE mailbox = :mailbox AND status = 'trash'
             ORDER BY created_at DESC"
        );
        $stmt->execute(['mailbox' => $mailbox]);
        return $stmt->fetchAll();
    }

    public function findMessage(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT id, email, subject, message, status, created_at, 'inbox' AS source
             FROM contact_messages WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $message = $stmt->fetch();

        return $message === false ? null : $message;
    }

    public function findFolderMessage(string $mailbox, string $folder, int $id): ?array
    {
        if (in_array($folder, ['sent', 'drafts'], true)) {
            $stmt = Database::pdo()->prepare(
                "SELECT id, recipient AS email, subject, body AS message, status, created_at, 'outbox' AS source
                 FROM email_outbox WHERE id = :id AND mailbox = :mailbox LIMIT 1"
            );
            $stmt->execute(['id' => $id, 'mailbox' => $mailbox]);
            $message = $stmt->fetch();
            return $message === false ? null : $message;
        }

        return $this->findMessage($id);
    }

    public function listOutbox(string $mailbox): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT recipient, subject, status, error_message, created_at
             FROM email_outbox WHERE mailbox = :mailbox ORDER BY created_at DESC LIMIT 20'
        );
        $stmt->execute(['mailbox' => $mailbox]);

        return $stmt->fetchAll();
    }

    public function markStatus(int $id, string $status): void
    {
        if (!in_array($status, ['new', 'processed', 'closed', 'trash'], true)) {
            throw new InvalidArgumentException('Statut email invalide.');
        }

        Database::pdo()->prepare(
            'UPDATE contact_messages SET status = :status WHERE id = :id'
        )->execute(['id' => $id, 'status' => $status]);
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

        $html = $this->templateRenderer->render('contact-reply.html', [
            'subject' => 'Re: ' . $message['subject'],
            'message_html' => nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
        ]);
        $sent = $this->mailService->send(
            (string) $message['email'],
            'Re: ' . $message['subject'],
            $html,
            true
        );

        Database::pdo()->prepare(
            'INSERT INTO email_outbox
                (mailbox, recipient, subject, body, status, error_message)
             VALUES (:mailbox, :recipient, :subject, :body, :status, :error)'
        )->execute([
            'mailbox' => str_ends_with((string) $message['email'], '@mail.ai') ? 'mail.ai' : 'contact',
            'recipient' => $message['email'],
            'subject' => 'Re: ' . $message['subject'],
            'body' => $body,
            'status' => $sent ? 'sent' : 'failed',
            'error' => $sent ? null : 'Serveur SMTP indisponible.',
        ]);

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

        $stmt = Database::pdo()->prepare(
            'INSERT INTO contact_messages (email, subject, message) VALUES (:email, :subject, :message)'
        );
        $stmt->execute([
            'email' => strtolower(trim($email)),
            'subject' => trim($subject),
            'message' => trim($body),
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function sendTest(string $recipient, string $subject, string $body): bool
    {
        if (!preg_match('/^[^@\s]+@mail\.ai$/i', $recipient)) {
            throw new InvalidArgumentException('Un envoi de test utilise exclusivement une adresse @mail.ai.');
        }

        if (trim($subject) === '' || trim($body) === '') {
            throw new InvalidArgumentException('Sujet et contenu sont requis.');
        }

        $html = $this->templateRenderer->render('contact-reply.html', [
            'subject' => $subject,
            'message_html' => nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
        ]);
        $sent = $this->mailService->send($recipient, $subject, $html, true);

        Database::pdo()->prepare(
            'INSERT INTO email_outbox
                (mailbox, recipient, subject, body, status, error_message)
             VALUES (\'mail.ai\', :recipient, :subject, :body, :status, :error)'
        )->execute([
            'recipient' => strtolower(trim($recipient)),
            'subject' => trim($subject),
            'body' => trim($body),
            'status' => $sent ? 'sent' : 'failed',
            'error' => $sent ? null : 'Serveur SMTP indisponible.',
        ]);

        return $sent;
    }

    public function saveDraft(string $mailbox, string $recipient, string $subject, string $body): int
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || trim($subject) === '') {
            throw new InvalidArgumentException('Destinataire et sujet valides requis.');
        }

        $stmt = Database::pdo()->prepare(
            "INSERT INTO email_outbox (mailbox, recipient, subject, body, status)
             VALUES (:mailbox, :recipient, :subject, :body, 'draft')"
        );
        $stmt->execute([
            'mailbox' => $mailbox,
            'recipient' => strtolower(trim($recipient)),
            'subject' => trim($subject),
            'body' => trim($body),
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function sendComposed(string $mailbox, string $recipient, string $subject, string $body): bool
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || trim($subject) === '' || trim($body) === '') {
            throw new InvalidArgumentException('Destinataire, sujet et contenu sont requis.');
        }

        $html = $this->templateRenderer->render('contact-reply.html', [
            'subject' => $subject,
            'message_html' => nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
        ]);
        $sent = $this->mailService->send($recipient, $subject, $html, true);

        Database::pdo()->prepare(
            'INSERT INTO email_outbox (mailbox, recipient, subject, body, status, error_message)
             VALUES (:mailbox, :recipient, :subject, :body, :status, :error)'
        )->execute([
            'mailbox' => $mailbox,
            'recipient' => strtolower(trim($recipient)),
            'subject' => trim($subject),
            'body' => trim($body),
            'status' => $sent ? 'sent' : 'failed',
            'error' => $sent ? null : 'Serveur SMTP indisponible.',
        ]);

        return $sent;
    }

    public function moveToTrash(string $source, int $id): void
    {
        if ($source === 'outbox') {
            Database::pdo()->prepare(
                "UPDATE email_outbox SET status = 'trash' WHERE id = :id"
            )->execute(['id' => $id]);
            return;
        }

        Database::pdo()->prepare(
            "UPDATE contact_messages SET status = 'trash' WHERE id = :id"
        )->execute(['id' => $id]);
    }
}
