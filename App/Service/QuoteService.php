<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Service\AuthService;
use InvalidArgumentException;

final readonly class QuoteService
{
    public function __construct(
        private MailService $mailService,
        private NotificationService $notificationService,
        private AuthService $authService
    ) {
    }

    public function create(array $data, ?int $userId = null): int
    {
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $company = trim((string) ($data['company'] ?? ''));
        $eventDate = trim((string) ($data['event_date'] ?? ''));
        $numberOfPeople = filter_var($data['number_of_people'] ?? null, FILTER_VALIDATE_INT);
        $serviceType = trim((string) ($data['service_type'] ?? ''));
        $eventLocation = trim((string) ($data['event_location'] ?? ''));
        $postalCode = trim((string) ($data['postal_code'] ?? ''));
        $details = trim((string) ($data['request_details'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresse email invalide.');
        }

        if ($phone !== '' && $this->authService->validatePhone($phone) !== null) {
            throw new InvalidArgumentException($this->authService->validatePhone($phone));
        }

        if ($eventDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
            throw new InvalidArgumentException('Date de réception invalide.');
        }

        if ($eventDate < date('Y-m-d')) {
            throw new InvalidArgumentException('La date de réception ne peut pas être passée.');
        }

        if ($numberOfPeople === false || $numberOfPeople < 50) {
            throw new InvalidArgumentException('La demande de devis est prévue pour les prestations d’au moins 50 personnes.');
        }

        $allowedServices = ['pickup', 'delivery', 'on_site'];

        if (!in_array($serviceType, $allowedServices, true)) {
            throw new InvalidArgumentException('Mode de prestation invalide.');
        }

        if ($serviceType !== 'pickup' && $eventLocation === '') {
            throw new InvalidArgumentException('Le lieu de réception est requis pour une livraison ou une prestation sur place.');
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO quote_requests (
                user_id, email, first_name, last_name, phone, company,
                event_date, number_of_people, service_type,
                event_location, postal_code, request_details
            ) VALUES (
                :user_id, :email, :first_name, :last_name, :phone, :company,
                :event_date, :number_of_people, :service_type,
                :event_location, :postal_code, :request_details
            )'
        );

        $stmt->execute([
            'user_id' => $userId,
            'email' => $email,
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'phone' => $phone !== '' ? $phone : null,
            'company' => $company !== '' ? $company : null,
            'event_date' => $eventDate,
            'number_of_people' => $numberOfPeople,
            'service_type' => $serviceType,
            'event_location' => $eventLocation !== '' ? $eventLocation : null,
            'postal_code' => $postalCode !== '' ? $postalCode : null,
            'request_details' => $details !== '' ? $details : null,
        ]);

        $id = (int) $pdo->lastInsertId();

        $this->mailService->sendQuoteRequestAcknowledgement(
            $email,
            $firstName,
            [
                'id' => $id,
                'event_date' => $eventDate,
                'number_of_people' => $numberOfPeople,
                'service_type' => $serviceType,
            ]
        );

        $companyEmail = $_ENV['MAIL_TO_ADDRESS']
            ?? $_ENV['MAIL_FROM_ADDRESS']
            ?? 'noreply@viteetgourmand.com';

        if ($userId !== null) {
            $this->notificationService->notify(
                $userId,
                'quote',
                'Demande de devis enregistrée',
                'Votre demande de devis #' . $id . ' a bien été reçue. Notre équipe va l’étudier.',
                null,
                $id
            );
        }

        $this->notificationService->notifyStaff(
            'quote',
            'Nouvelle demande de devis #' . $id,
            'Une nouvelle demande de devis attend votre traitement.',
            null,
            $id
        );

        $this->mailService->sendQuoteRequestNotificationToStaff(
            $companyEmail,
            [
                'id' => $id,
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'company' => $company,
                'event_date' => $eventDate,
                'number_of_people' => $numberOfPeople,
                'service_type' => $serviceType,
                'event_location' => $eventLocation,
                'postal_code' => $postalCode,
                'details' => $details,
            ]
        );

        return $id;
    }

    public function findForStaff(?string $status = null): array
    {
        $sql = 'SELECT q.*,
                       CONCAT(COALESCE(q.first_name, \'\'), \' \',
                              COALESCE(q.last_name, \'\')) AS contact_name
                FROM quote_requests q
                WHERE 1 = 1';
        $params = [];

        if ($status !== null && $status !== '') {
            $sql .= ' AND q.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY q.event_date ASC, q.created_at ASC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status, string $reply): void
    {
        $allowed = ['new', 'in_review', 'quoted', 'accepted', 'declined', 'closed'];

        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Statut de demande invalide.');
        }

        $stmt = Database::pdo()->prepare(
            'SELECT * FROM quote_requests WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $request = $stmt->fetch();

        if ($request === false) {
            throw new InvalidArgumentException('Demande de devis introuvable.');
        }

        $reply = trim($reply);

        if (
            in_array($status, ['quoted', 'accepted', 'declined', 'closed'], true)
            && $reply === ''
        ) {
            throw new InvalidArgumentException('Une réponse est requise pour ce statut.');
        }

        Database::pdo()->prepare(
            'UPDATE quote_requests
             SET status = :status,
                 employee_reply = :reply,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        )->execute([
            'id' => $id,
            'status' => $status,
            'reply' => $reply !== '' ? $reply : null,
        ]);

        if ($request['user_id'] !== null) {
            $this->notificationService->notify(
                (int) $request['user_id'],
                'quote',
                'Réponse à votre demande de devis',
                'Votre demande de devis #' . $id . ' a été mise à jour : ' .
                ($reply !== '' ? $reply : ($status . '.')),
                null,
                $id
            );
        }

        $this->mailService->sendQuoteRequestStatusEmail(
            (string) $request['email'],
            (string) ($request['first_name'] ?? ''),
            $id,
            $status,
            $reply
        );
    }
}
