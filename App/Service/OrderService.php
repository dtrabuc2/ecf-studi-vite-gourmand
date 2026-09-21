<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Entity\Order;
use App\Repository\MenuRepository;
use App\Repository\OpeningHoursRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use InvalidArgumentException;

final readonly class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private UserRepository $userRepository,
        private MenuRepository $menuRepository,
        private OpeningHoursRepository $openingHoursRepository,
        private MailService $mailService,
        private MenuStatisticsService $menuStatisticsService,
        private NotificationService $notificationService
    ) {
    }

    public function createOrder(
        int $userId,
        int $menuId,
        int $numberOfPeople,
        string $deliveryDate,
        string $deliveryTime,
        string $deliveryAddress,
        string $deliveryCity,
        string $deliveryPostalCode,
        ?float $deliveryDistanceKm = null,
        ?string $customization = null,
        string $serviceType = 'delivery',
        string $paymentMethod = 'cash_on_site',
        ?string $deliveryInstructions = null,
        ?string $contactPhone = null
    ): array {
        $user = $this->userRepository->findById($userId);
        $menu = $this->menuRepository->findById($menuId);

        if ($user === null) {
            throw new InvalidArgumentException('Utilisateur non trouvé.');
        }

        if ($menu === null || $menu->getAvailableStock() < 1) {
            throw new InvalidArgumentException('Menu non trouvé ou indisponible.');
        }

        if ($numberOfPeople > 30) {
            throw new InvalidArgumentException(
                'Au-delà de 30 personnes, une demande de devis traiteur est obligatoire.'
            );
        }

        if ($numberOfPeople < 15 || $numberOfPeople > 30) {
            throw new InvalidArgumentException(
                'Une commande traiteur directe doit comprendre entre 15 et 30 convives.'
            );
        }

        if (!in_array($serviceType, ['delivery', 'on_site', 'pickup'], true)) {
            throw new InvalidArgumentException('Mode de prestation invalide.');
        }

        if ($paymentMethod !== 'cash_on_site') {
            throw new InvalidArgumentException('Le paiement est effectué en espèces sur place.');
        }

        if ($contactPhone === null || trim($contactPhone) === '') {
            throw new InvalidArgumentException('Un numéro de téléphone est requis pour la commande.');
        }

        $normalizedPhone = preg_replace('/[\\s().-]+/', '', trim($contactPhone)) ?? '';
        if (!preg_match('/^(?:\\+33[67]\\d{8}|0[67]\\d{8}|\\+34[6789]\\d{8}|[6789]\\d{8})$/', $normalizedPhone)) {
            throw new InvalidArgumentException('Numéro de téléphone invalide.');
        }

        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $deliveryDate) || $deliveryDate < date('Y-m-d')) {
            throw new InvalidArgumentException('La date de prestation est invalide.');
        }

        if (!preg_match('/^\\d{2}:\\d{2}$/', $deliveryTime)) {
            throw new InvalidArgumentException('L’heure de prestation est invalide.');
        }

        [$hour, $minute] = array_map('intval', explode(':', $deliveryTime));
        if ($hour > 23 || $minute > 59) {
            throw new InvalidArgumentException('L’heure de prestation est invalide.');
        }

        $this->validateServiceDateTime($deliveryDate, $deliveryTime);

        if ($deliveryDistanceKm !== null && (!is_finite($deliveryDistanceKm) || $deliveryDistanceKm < 0)) {
            throw new InvalidArgumentException('La distance de livraison est invalide.');
        }

        if ($serviceType === 'delivery' && (trim($deliveryAddress) === '' || trim($deliveryCity) === '')) {
            throw new InvalidArgumentException('L’adresse et la ville de livraison sont requises.');
        }

        if ($serviceType === 'pickup') {
            $deliveryAddress = '';
            $deliveryCity = '';
            $deliveryPostalCode = '';
            $deliveryDistanceKm = null;
            $deliveryInstructions = null;
        }

        if ($serviceType === 'on_site') {
            $deliveryAddress = '12 Quai des Chartrons';
            $deliveryCity = 'Bordeaux';
            $deliveryPostalCode = '33000';
            $deliveryDistanceKm = null;
            $deliveryInstructions = null;
        }

        [$menuPrice, $discountRate] = $this->calculateMenuPrice(
            $menu->getBasePrice(),
            $menu->getMinPeople(),
            $numberOfPeople
        );

        $deliveryCost = $serviceType === 'delivery'
            ? $this->calculateDeliveryCost($deliveryCity, $deliveryDistanceKm)
            : 0.00;

        $orderData = [
            'user_id' => $userId,
            'menu_id' => $menuId,
            'number_of_people' => $numberOfPeople,
            'order_date' => date('Y-m-d H:i:s'),
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
            'delivery_address' => trim($deliveryAddress),
            'delivery_city' => trim($deliveryCity),
            'delivery_postal_code' => trim($deliveryPostalCode),
            'delivery_distance_km' => $deliveryDistanceKm,
            'delivery_cost' => $deliveryCost,
            'customization' => $customization,
            'service_type' => $serviceType,
            'payment_method' => $paymentMethod,
            'delivery_instructions' => $deliveryInstructions,
            'contact_phone' => trim((string) $contactPhone),
            'menu_price' => $menuPrice,
            'discount_rate' => $discountRate,
            'total_price' => round($menuPrice + $deliveryCost, 2),
            'status' => 'pending',
            'equipment_loaned' => false,
        ];

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            // Une commande n'est considérée comme reçue par le site
            // qu'après l'enregistrement de l'ordre, la réservation du stock
            // et la création de son premier historique dans la même transaction.
            $orderId = $this->orderRepository->create($orderData);
            $this->orderRepository->decreaseMenuStock($menuId);
            $this->orderRepository->addToHistory($orderId, 'pending', $userId, 'Commande créée');

            if ($orderId <= 0) {
                throw new \RuntimeException('La commande n’a pas pu être enregistrée.');
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        $mailDetails = [
            'id' => $orderId,
            'order_date' => $orderData['order_date'],
            'menu_title' => $menu->getTitle(),
            'number_of_people' => $numberOfPeople,
            'menu_price' => $menuPrice,
            'delivery_cost' => $deliveryCost,
            'total_price' => $orderData['total_price'],
            'service_type' => $serviceType,
            'delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
            'delivery_address' => trim($deliveryAddress),
            'delivery_city' => trim($deliveryCity),
            'delivery_postal_code' => trim($deliveryPostalCode),
            'delivery_instructions' => trim((string) $deliveryInstructions),
            'customization' => trim((string) $customization),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'contact_phone' => $contactPhone,
        ];

        try {
            $this->mailService->sendOrderConfirmationEmail(
                $user->getEmail(),
                $user->getFirstName(),
                $mailDetails
            );

            $staffEmail = (string) ($_ENV['MAIL_TO_ADDRESS'] ?? '');

            if ($staffEmail !== '' && filter_var($staffEmail, FILTER_VALIDATE_EMAIL)) {
                $this->mailService->sendOrderNotificationToStaff(
                    $staffEmail,
                    $mailDetails
                );
            }
        } catch (\Throwable $exception) {
            error_log('Order email notification error: ' . $exception->getMessage());
        }

        // Les notifications sont secondaires par rapport à l'enregistrement.
        // Une panne de notification ne doit jamais faire échouer une commande déjà commitée.
        try {
            $this->notificationService->notify(
                $userId,
                'order',
                'Commande enregistrée',
                'Votre commande ' . $orderId . ' a bien été enregistrée et attend la confirmation de l’équipe.',
                $orderId
            );

            $this->notificationService->notifyStaff(
                'order',
                'Nouvelle commande ' . $orderId,
                'Commande de ' . $user->getFirstName() . ' ' . $user->getLastName()
                . ' — ' . $menu->getTitle()
                . ' — ' . $numberOfPeople . ' personne(s)'
                . ' — total ' . number_format((float) $orderData['total_price'], 2, ',', ' ') . ' €.'
                . ' Prestation : ' . $serviceType
                . ' le ' . $deliveryDate . ' à ' . $deliveryTime . '.',
                $orderId
            );
        } catch (\Throwable $exception) {
            error_log('Order internal notification error: ' . $exception->getMessage());
        }

        return [
            'success' => true,
            'order_id' => $orderId,
            'menu_price' => $menuPrice,
            'discount_rate' => $discountRate,
            'delivery_cost' => $deliveryCost,
            'total_price' => $orderData['total_price'],
        ];
    }

    /**
     * Valide une date et un créneau de prestation dans le fuseau Europe/Paris.
     */
    public function validateServiceDateTime(string $deliveryDate, string $deliveryTime): void
    {
        $timezone = new \DateTimeZone('Europe/Paris');
        $now = new \DateTimeImmutable('now', $timezone);

        $requested = \DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            $deliveryDate . ' ' . $deliveryTime,
            $timezone
        );
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $requested === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        ) {
            throw new InvalidArgumentException('La date ou l’heure de prestation est invalide.');
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $deliveryTime)) {
            throw new InvalidArgumentException('Le créneau horaire est invalide.');
        }

        [$hour, $minute] = array_map('intval', explode(':', $deliveryTime));
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $minute % 15 !== 0) {
            throw new InvalidArgumentException('Le créneau doit être aligné sur 15 minutes.');
        }

        if ($requested <= $now) {
            throw new InvalidArgumentException(
                $deliveryDate === $now->format('Y-m-d')
                    ? 'L’heure de prestation est déjà passée.'
                    : 'La date de prestation ne peut pas être passée.'
            );
        }

        $windows = $this->openingHoursRepository->getWindowsByDay((int) $requested->format('N'));

        if ($windows === []) {
            throw new InvalidArgumentException('Cette date est fermée. Veuillez choisir un autre jour.');
        }

        $requestedTime = $requested->format('H:i:s');

        foreach ($windows as [$opening, $closing]) {
            if ($requestedTime >= $opening && $requestedTime < $closing) {
                return;
            }
        }

        throw new InvalidArgumentException('Ce créneau est en dehors des horaires d’ouverture.');
    }

    public function getAvailableTimeSlots(string $deliveryDate): array
    {
        $timezone = new \DateTimeZone('Europe/Paris');
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $deliveryDate, $timezone);
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        ) {
            return [];
        }

        $windows = $this->openingHoursRepository->getWindowsByDay((int) $date->format('N'));
        if ($windows === []) {
            return [];
        }

        $now = new \DateTimeImmutable('now', $timezone);
        $slots = [];

        foreach ($windows as [$opening, $closing]) {
            $slot = new \DateTimeImmutable(
                $date->format('Y-m-d') . ' ' . substr($opening, 0, 5),
                $timezone
            );
            $end = new \DateTimeImmutable(
                $date->format('Y-m-d') . ' ' . substr($closing, 0, 5),
                $timezone
            );

            while ($slot < $end) {
                if ($slot > $now) {
                    $slots[] = $slot->format('H:i');
                }
                $slot = $slot->modify('+15 minutes');
            }
        }

        return array_values(array_unique($slots));
    }

    public function getServiceDateAvailability(string $deliveryDate): array
    {
        $timezone = new \DateTimeZone('Europe/Paris');
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $deliveryDate, $timezone);
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        ) {
            throw new InvalidArgumentException('La date de prestation est invalide.');
        }

        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];
        $dayOfWeek = (int) $date->format('N');
        $windows = $this->openingHoursRepository->getWindowsByDay($dayOfWeek);

        return [
            'date' => $date->format('Y-m-d'),
            'day_of_week' => $dayOfWeek,
            'day_label' => $days[$dayOfWeek] ?? '',
            'open' => $windows !== [],
            'windows' => $windows,
            'slots' => $this->getAvailableTimeSlots($date->format('Y-m-d')),
        ];
    }

    public function getMenuAvailabilityForPeople(int $numberOfPeople): array
    {
        if ($numberOfPeople < 1) {
            throw new InvalidArgumentException('Le nombre de convives doit être supérieur à 0.');
        }

        if ($numberOfPeople > 30) {
            return [];
        }

        $menus = $this->menuRepository->findAllForOrderSelection();
        $result = [];

        foreach ($menus as $menu) {
            $stockAvailable = $menu->getAvailableStock() > 0;
            $peopleAvailable = $numberOfPeople >= 1;
            $available = $stockAvailable && $peopleAvailable;
            $reason = $available
                ? null
                : (!$stockAvailable
                    ? 'Stock indisponible.'
                    : 'Menu indisponible pour ce nombre de convives.');

            $menuPrice = null;
            $discountRate = null;

            if ($peopleAvailable) {
                [$menuPrice, $discountRate] = $this->calculateMenuPrice(
                    $menu->getBasePrice(),
                    $menu->getMinPeople(),
                    $numberOfPeople
                );
            }

            $result[] = [
                'id' => $menu->getId(),
                'title' => $menu->getTitle(),
                'min_people' => $menu->getMinPeople(),
                'base_price' => $menu->getBasePrice(),
                'available_stock' => $menu->getAvailableStock(),
                'available' => $available,
                'reason' => $reason,
                'menu_price' => $menuPrice,
                'discount_rate' => $discountRate,
            ];
        }

        return $result;
    }

    public function calculatePricePreview(
        int $menuId,
        int $numberOfPeople,
        string $serviceType,
        string $deliveryCity = '',
        ?float $deliveryDistanceKm = null
    ): array {
        if ($numberOfPeople < 1 || $numberOfPeople > 30) {
            throw new InvalidArgumentException('Le nombre de convives est invalide pour une commande directe.');
        }

        if (!in_array($serviceType, ['delivery', 'pickup', 'on_site'], true)) {
            throw new InvalidArgumentException('Mode de prestation invalide.');
        }

        $menu = $this->menuRepository->findById($menuId);

        if ($menu === null || $menu->getAvailableStock() < 1) {
            throw new InvalidArgumentException('Menu indisponible.');
        }

        if ($numberOfPeople < 1) {
            throw new InvalidArgumentException('Le nombre de convives est invalide.');
        }

        [$menuPrice, $discountRate] = $this->calculateMenuPrice(
            $menu->getBasePrice(),
            $menu->getMinPeople(),
            $numberOfPeople
        );

        if ($serviceType !== 'delivery') {
            return [
                'ready' => true,
                'menu_price' => $menuPrice,
                'discount_rate' => $discountRate,
                'delivery_cost' => 0.00,
                'total_price' => round($menuPrice, 2),
            ];
        }

        $city = mb_strtolower(trim($deliveryCity));

        if ($city === '') {
            return [
                'ready' => false,
                'menu_price' => $menuPrice,
                'discount_rate' => $discountRate,
                'delivery_cost' => null,
                'total_price' => null,
                'message' => 'Renseignez la ville de livraison.',
            ];
        }

        if ($city !== 'bordeaux' && ($deliveryDistanceKm === null || !is_finite($deliveryDistanceKm) || $deliveryDistanceKm < 0)) {
            return [
                'ready' => false,
                'menu_price' => $menuPrice,
                'discount_rate' => $discountRate,
                'delivery_cost' => null,
                'total_price' => null,
                'message' => 'Renseignez la distance de livraison hors Bordeaux.',
            ];
        }

        $deliveryCost = $this->calculateDeliveryCost($deliveryCity, $deliveryDistanceKm);

        return [
            'ready' => true,
            'menu_price' => $menuPrice,
            'discount_rate' => $discountRate,
            'delivery_cost' => $deliveryCost,
            'total_price' => round($menuPrice + $deliveryCost, 2),
        ];
    }

    private function calculateMenuPrice(float $basePrice, int $minPeople, int $numberOfPeople): array
    {
        if ($basePrice < 0 || $minPeople < 1 || $numberOfPeople < 1) {
            throw new InvalidArgumentException('Paramètres de tarification invalides.');
        }

        $effectiveMinPeople = max(1, $minPeople);
        if ($numberOfPeople < $effectiveMinPeople && $numberOfPeople < 15) {
            throw new InvalidArgumentException('Le nombre de convives ne permet pas de calculer ce tarif.');
        }

        // Le catalogue indique le prix pour le nombre minimal de personnes.
        // On ramène donc ce prix à un tarif unitaire avant de recalculer la commande.
        // Règle ECF : remise de 10 % à partir de 5 personnes au-dessus du minimum.
        $grossPrice = round(($basePrice / $minPeople) * $numberOfPeople, 2);
        $discountRate = $numberOfPeople >= ($minPeople + 5) ? 10.0 : 0.0;
        $price = round($grossPrice * (1 - ($discountRate / 100)), 2);

        return [$price, $discountRate];
    }

    public function getUserOrders(int $userId): array
    {
        return $this->orderRepository->findByUserId($userId);
    }

    public function getOrdersForStaff(?string $status = null, ?string $customer = null): array
    {
        return $this->orderRepository->findForStaff($status, $customer);
    }

    public function getOrderById(int $orderId): ?Order
    {
        return $this->orderRepository->findById($orderId);
    }

    public function updateCustomerOrder(
        int $orderId,
        int $userId,
        int $numberOfPeople,
        string $deliveryDate,
        string $deliveryTime,
        string $address,
        string $city,
        string $postalCode,
        ?float $distanceKm
    ): void {
        $order = $this->orderRepository->findById($orderId);

        if ($order === null || $order->getUserId() !== $userId) {
            throw new InvalidArgumentException('Commande introuvable.');
        }

        if ($order->getStatus() !== 'pending') {
            throw new InvalidArgumentException('Cette commande ne peut plus être modifiée.');
        }

        if ($numberOfPeople < 1) {
            throw new InvalidArgumentException('Le nombre de personnes doit être supérieur à 0.');
        }

        $this->validateServiceDateTime($deliveryDate, $deliveryTime);

        if (trim($address) === '' || trim($city) === '') {
            throw new InvalidArgumentException('L’adresse et la ville sont requises.');
        }

        if ($distanceKm !== null && $distanceKm < 0) {
            throw new InvalidArgumentException('La distance de livraison est invalide.');
        }

        $menu = $this->menuRepository->findById($order->getMenuId());

        if ($menu === null) {
            throw new InvalidArgumentException('Le menu de la commande est introuvable.');
        }

        [$menuPrice, $discountRate] = $this->calculateMenuPrice(
            $menu->getBasePrice(),
            $menu->getMinPeople(),
            $numberOfPeople
        );

        $deliveryCost = $this->calculateDeliveryCost($city, $distanceKm);

        $this->orderRepository->updateCustomerOrder(
            $orderId,
            $numberOfPeople,
            $deliveryDate,
            $deliveryTime,
            trim($address),
            trim($city),
            trim($postalCode),
            $distanceKm,
            $menuPrice,
            $deliveryCost,
            $discountRate,
            round($menuPrice + $deliveryCost, 2)
        );
    }

    public function updateOrderStatus(
        int $orderId,
        string $status,
        ?int $changedByUserId = null,
        string $notes = '',
        ?string $cancellationReason = null,
        ?bool $equipmentLoaned = null
    ): void {
        $order = $this->orderRepository->findById($orderId);

        if ($order === null) {
            throw new InvalidArgumentException('Commande non trouvée.');
        }

        $validStatuses = [
            'pending',
            'accepted',
            'preparing',
            'delivering',
            'delivered',
            'awaiting_return',
            'completed',
            'cancelled',
        ];

        if (!in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException('Statut de commande invalide.');
        }

        if ($order->getStatus() === $status) {
            throw new InvalidArgumentException('La commande possède déjà ce statut.');
        }

        $allowedTransitions = [
            'pending' => ['accepted', 'cancelled'],
            'accepted' => ['preparing', 'cancelled'],
            'preparing' => ['delivering', 'cancelled'],
            'delivering' => ['delivered', 'cancelled'],
            'delivered' => ['awaiting_return', 'completed'],
            'awaiting_return' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ];

        if (!in_array($status, $allowedTransitions[$order->getStatus()] ?? [], true)) {
            throw new InvalidArgumentException('Transition de statut non autorisée.');
        }

        if (
            $order->getStatus() === 'delivered'
            && $status === 'awaiting_return'
            && !$order->isEquipmentLoaned()
        ) {
            throw new InvalidArgumentException(
                'Le statut « en attente du retour de matériel » nécessite un prêt de matériel.'
            );
        }

        if (
            $order->getStatus() === 'delivered'
            && $status === 'completed'
            && $order->isEquipmentLoaned()
        ) {
            throw new InvalidArgumentException(
                'Cette commande doit passer par le retour du matériel avant d’être terminée.'
            );
        }

        if ($status === 'cancelled' && trim((string) $cancellationReason) === '') {
            throw new InvalidArgumentException('Un motif est obligatoire pour annuler une commande.');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            if ($equipmentLoaned !== null && $order->isEquipmentLoaned() !== $equipmentLoaned) {
                $this->orderRepository->setEquipmentLoaned($orderId, $equipmentLoaned);
                $order->setEquipmentLoaned($equipmentLoaned);
            }

            $this->orderRepository->updateStatus(
                $orderId,
                $status,
                $status === 'cancelled' ? trim((string) $cancellationReason) : null
            );

            if ($status === 'cancelled' && $order->getStatus() !== 'cancelled') {
                $this->orderRepository->increaseMenuStock($order->getMenuId());
            }

            $this->orderRepository->addToHistory(
                $orderId,
                $status,
                $changedByUserId,
                $notes
            );

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        try {
            $this->notificationService->notify(
                $order->getUserId(),
                'order',
                'Mise à jour de votre commande',
                'La commande #' . $orderId . ' est maintenant « ' . $status . ' ».'
                    . ($notes !== '' ? ' ' . $notes : ''),
                $orderId
            );
        } catch (\Throwable $exception) {
            error_log('Order internal notification error: ' . $exception->getMessage());
        }

        $user = $this->userRepository->findById($order->getUserId());

        if ($user === null) {
            return;
        }

        try {
            if ($status === 'awaiting_return') {
                $this->mailService->sendEquipmentReturnNoticeEmail(
                    $user->getEmail(),
                    $user->getFirstName(),
                    $orderId
                );
            } elseif ($status === 'completed') {
                $this->mailService->sendReviewInvitationEmail(
                    $user->getEmail(),
                    $user->getFirstName(),
                    $orderId
                );
                $this->menuStatisticsService->aggregateAndStore();
            }
        } catch (\Throwable $exception) {
            error_log('Order status notification error: ' . $exception->getMessage());
        }
    }

    public function getOrderHistory(int $orderId): array
    {
        return $this->orderRepository->getHistory($orderId);
    }

    private function calculateDeliveryCost(string $deliveryCity, ?float $deliveryDistanceKm): float
    {
        $city = mb_strtolower(trim($deliveryCity));

        if ($city === '') {
            throw new InvalidArgumentException('La ville de livraison est requise.');
        }

        if ($city === 'bordeaux') {
            return 0.00;
        }

        if ($deliveryDistanceKm === null || $deliveryDistanceKm < 0) {
            throw new InvalidArgumentException('La distance de livraison est requise hors Bordeaux.');
        }

        return round(5.00 + (0.59 * $deliveryDistanceKm), 2);
    }
}
