<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

/**
 * Boîte e-mail interne : messages reçus (contact_messages) et messages
 * envoyés, brouillons ou en échec (email_outbox).
 *
 * Deux boîtes : "mail.ai" (adresses @mail.ai de démonstration) et "contact" (toutes les autres).
 */
final class MailboxRepository
{
    private const DEMO_DOMAIN = '%@mail.ai';

    public function createContactMessage(string $email, string $subject, string $message): int
    {
        $pdo = Database::pdo();
        $pdo->prepare(
            'INSERT INTO contact_messages (email, subject, message) VALUES (:email, :subject, :message)'
        )->execute([
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function findInbox(string $mailbox): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT id, email, subject, message, status, created_at, 'inbox' AS source
             FROM contact_messages
             WHERE email " . $this->mailboxOperator($mailbox) . " :domain AND status <> 'trash'
             ORDER BY created_at DESC"
        );
        $stmt->execute(['domain' => self::DEMO_DOMAIN]);

        return $stmt->fetchAll();
    }

    public function findOutboxByStatus(string $mailbox, string $status): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT id, recipient AS email, subject, body AS message, status, created_at, 'outbox' AS source
             FROM email_outbox WHERE mailbox = :mailbox AND status = :status
             ORDER BY created_at DESC"
        );
        $stmt->execute(['mailbox' => $mailbox, 'status' => $status]);

        return $stmt->fetchAll();
    }

    public function findTrash(string $mailbox): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT id, email, subject, message, status, created_at, 'inbox' AS source
             FROM contact_messages
             WHERE email " . $this->mailboxOperator($mailbox) . " :domain AND status = 'trash'
             UNION ALL
             SELECT id, recipient AS email, subject, body AS message, status, created_at, 'outbox' AS source
             FROM email_outbox WHERE mailbox = :mailbox AND status = 'trash'
             ORDER BY created_at DESC"
        );
        $stmt->execute(['domain' => self::DEMO_DOMAIN, 'mailbox' => $mailbox]);

        return $stmt->fetchAll();
    }

    public function findContactMessage(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT id, email, subject, message, status, created_at, 'inbox' AS source
             FROM contact_messages WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $message = $stmt->fetch();

        return $message === false ? null : $message;
    }

    public function findOutboxMessage(string $mailbox, int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT id, recipient AS email, subject, body AS message, status, created_at, 'outbox' AS source
             FROM email_outbox WHERE id = :id AND mailbox = :mailbox LIMIT 1"
        );
        $stmt->execute(['id' => $id, 'mailbox' => $mailbox]);
        $message = $stmt->fetch();

        return $message === false ? null : $message;
    }

    /**
     * 20 derniers envois (journal affiché dans la boîte).
     */
    public function findRecentOutbox(string $mailbox): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT recipient, subject, status, error_message, created_at
             FROM email_outbox WHERE mailbox = :mailbox ORDER BY created_at DESC LIMIT 20'
        );
        $stmt->execute(['mailbox' => $mailbox]);

        return $stmt->fetchAll();
    }

    public function updateContactStatus(int $id, string $status): void
    {
        Database::pdo()->prepare(
            'UPDATE contact_messages SET status = :status WHERE id = :id'
        )->execute(['id' => $id, 'status' => $status]);
    }

    public function addToOutbox(
        string $mailbox,
        string $recipient,
        string $subject,
        string $body,
        string $status,
        ?string $errorMessage = null
    ): int {
        $pdo = Database::pdo();
        $pdo->prepare(
            'INSERT INTO email_outbox (mailbox, recipient, subject, body, status, error_message)
             VALUES (:mailbox, :recipient, :subject, :body, :status, :error)'
        )->execute([
            'mailbox' => $mailbox,
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'status' => $status,
            'error' => $errorMessage,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function trashOutboxMessage(int $id): void
    {
        Database::pdo()->prepare(
            "UPDATE email_outbox SET status = 'trash' WHERE id = :id"
        )->execute(['id' => $id]);
    }

    private function mailboxOperator(string $mailbox): string
    {
        return $mailbox === 'mail.ai' ? 'LIKE' : 'NOT LIKE';
    }
}
