<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Repository\UserRepository;
use App\Service\CommentService;
use App\Service\MenuService;
use App\Service\OrderService;
use InvalidArgumentException;

final class OrderController extends BaseController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly UserRepository $userRepository,
        private readonly MenuService $menuService,
        private readonly CommentService $commentService
    ) {
    }

    public function index(): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $orders = $this->orderService->getUserOrders($userId);
        $history = [];
        $reviews = [];

        foreach ($orders as $order) {
            $history[$order->getId()] = $this->orderService->getOrderHistory($order->getId());
            $reviews[$order->getId()] = $this->commentService->getByOrderId($order->getId());
        }

        $this->render('order/index', [
            'orders' => $orders,
            'history' => $history,
            'reviews' => $reviews,
        ]);
    }

    public function new(): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $menus = $this->menuService->getAllMenus();
        $selectedMenuId = isset($_GET['menu']) ? (int) $_GET['menu'] : 0;
        $user = $this->userRepository->findById($userId);

        $errors = Session::pullFlash('order_errors', []);
        $oldInput = Session::pullFlash('order_old_input', []);

        $this->render('order/new', [
            'menus' => $menus,
            'selectedMenuId' => $selectedMenuId,
            'selectedServiceType' => is_array($oldInput) && in_array(
                $oldInput['service_type'] ?? '',
                ['delivery', 'pickup', 'on_site'],
                true
            ) ? $oldInput['service_type'] : 'delivery',
            'errors' => is_array($errors) ? $errors : [],
            'oldInput' => is_array($oldInput) ? $oldInput : [],
            'orderUser' => $user,
        ]);
    }

    public function menuOptions(): never
    {
        $numberOfPeople = filter_var(
            $_GET['number_of_people'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($numberOfPeople === false) {
            $this->json(['success' => false, 'error' => 'Nombre de convives invalide.'], 422);
        }

        if ($numberOfPeople > 30) {
            $this->json([
                'success' => true,
                'data' => [
                    'menus' => [],
                    'quote_required' => true,
                    'quote_url' => '/quote',
                ],
            ]);
        }

        $this->json([
            'success' => true,
            'data' => [
                'menus' => $this->orderService->getMenuAvailabilityForPeople($numberOfPeople),
                'quote_required' => false,
            ],
        ]);
    }

    public function availability(): never
    {
        $date = trim((string) ($_GET['date'] ?? ''));

        if ($date === '') {
            $this->json(['success' => false, 'error' => 'Date de prestation requise.'], 422);
        }

        try {
            $this->json([
                'success' => true,
                'data' => $this->orderService->getServiceDateAvailability($date),
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->json(['success' => false, 'error' => $exception->getMessage()], 422);
        }
    }

    public function pricePreview(): never
    {
        $menuId = filter_var(
            $_GET['menu_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $numberOfPeople = filter_var(
            $_GET['number_of_people'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($menuId === false || $numberOfPeople === false) {
            $this->json(['success' => false, 'error' => 'Menu ou nombre de convives invalide.'], 422);
        }

        $serviceType = trim((string) ($_GET['service_type'] ?? 'delivery'));
        $deliveryCity = trim((string) ($_GET['delivery_city'] ?? ''));
        $deliveryDistanceKm = ($_GET['delivery_distance_km'] ?? '') !== ''
            ? (float) $_GET['delivery_distance_km']
            : null;

        try {
            $this->json([
                'success' => true,
                'data' => $this->orderService->calculatePricePreview(
                    $menuId,
                    $numberOfPeople,
                    $serviceType,
                    $deliveryCity,
                    $deliveryDistanceKm
                ),
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->json(['success' => false, 'error' => $exception->getMessage()], 422);
        }
    }

    public function create(): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $menuId = $_POST['menu_id'] ?? null;
        $numberOfPeople = $_POST['number_of_people'] ?? null;
        $confirmedGuestCount = $_POST['confirmed_guest_count'] ?? null;
        $serviceType = trim((string) ($_POST['service_type'] ?? 'delivery'));

        if (is_numeric($numberOfPeople) && (int) $numberOfPeople > 30) {
            Session::flash(
                'quote_old_input',
                [
                    'number_of_people' => (int) $numberOfPeople,
                    'event_date' => trim((string) ($_POST['delivery_date'] ?? '')),
                    'service_type' => in_array($serviceType, ['delivery', 'pickup', 'on_site'], true) ? $serviceType : 'delivery',
                    'event_location' => trim((string) ($_POST['delivery_address'] ?? '')),
                    'postal_code' => trim((string) ($_POST['delivery_postal_code'] ?? '')),
                    'request_details' => 'Demande traiteur pour ' . (int) $numberOfPeople . ' convives.',
                ]
            );
            $this->redirect('/quote');
        }

        $deliveryDate = trim((string) ($_POST['delivery_date'] ?? ''));
        $deliveryTime = trim((string) ($_POST['delivery_time'] ?? ''));
        $deliveryAddress = trim((string) ($_POST['delivery_address'] ?? ''));
        $deliveryCity = trim((string) ($_POST['delivery_city'] ?? ''));
        $deliveryPostalCode = trim((string) ($_POST['delivery_postal_code'] ?? ''));
        $deliveryDistanceKm = isset($_POST['delivery_distance_km']) && $_POST['delivery_distance_km'] !== ''
            ? (float) $_POST['delivery_distance_km']
            : null;
        $deliveryInstructions = trim((string) ($_POST['delivery_instructions'] ?? ''));
        $contactPhone = trim((string) ($_POST['contact_phone'] ?? ''));

        $errors = [];

        if (!is_numeric($menuId) || (int) $menuId <= 0) {
            $errors['menu_id'] = 'Menu requis.';
        }

        if (!is_numeric($numberOfPeople) || (int) $numberOfPeople < 1) {
            $errors['number_of_people'] = 'Nombre de personnes requis et supérieur à 0.';
        } elseif (
            (int) $numberOfPeople < 50
            && (!is_numeric($confirmedGuestCount) || (int) $confirmedGuestCount !== (int) $numberOfPeople)
        ) {
            $errors['number_of_people'] = 'Confirmez le nombre de convives avant de sélectionner un menu.';
        }

        if ($menuId !== null && is_numeric($menuId) && $numberOfPeople !== null && is_numeric($numberOfPeople) && (int) $numberOfPeople <= 30) {
            $menu = $this->menuService->getMenuById((int) $menuId);

            if ($menu === null || $menu->getAvailableStock() < 1) {
                $errors['menu_id'] = 'Ce menu n’est plus disponible.';
            }
        }

        if ($deliveryDate === '') {
            $errors['delivery_date'] = 'Date de prestation invalide.';
        }

        if ($deliveryTime === '') {
            $errors['delivery_time'] = 'Heure de prestation invalide.';
        }

        if ($deliveryDate !== '' && $deliveryTime !== '') {
            try {
                $this->orderService->validateServiceDateTime($deliveryDate, $deliveryTime);
            } catch (InvalidArgumentException $exception) {
                if (str_contains($exception->getMessage(), 'heure') || str_contains($exception->getMessage(), 'créneau')) {
                    $errors['delivery_time'] = $exception->getMessage();
                } else {
                    $errors['delivery_date'] = $exception->getMessage();
                }
            }
        }

        if (!in_array($serviceType, ['delivery', 'pickup', 'on_site'], true)) {
            $errors['service_type'] = 'Mode de prestation invalide.';
        }

        if ($contactPhone === '') {
            $errors['contact_phone'] = 'Un numéro de téléphone est requis pour cette commande.';
        } elseif ($this->authPhoneError($contactPhone) !== null) {
            $errors['contact_phone'] = $this->authPhoneError($contactPhone);
        }

        if ($serviceType === 'delivery') {
            if ($deliveryDistanceKm !== null && (!is_finite($deliveryDistanceKm) || $deliveryDistanceKm < 0)) {
                $errors['delivery_distance_km'] = 'Distance de livraison invalide.';
            }

            if ($deliveryAddress === '') {
                $errors['delivery_address'] = 'Adresse de livraison requise.';
            }
            if ($deliveryCity === '') {
                $errors['delivery_city'] = 'Ville de livraison requise.';
            }
            if ($deliveryPostalCode === '') {
                $errors['delivery_postal_code'] = 'Code postal requis pour une livraison.';
            }
            if (
                $deliveryCity !== ''
                && mb_strtolower($deliveryCity) !== 'bordeaux'
                && ($deliveryDistanceKm === null || $deliveryDistanceKm < 0)
            ) {
                $errors['delivery_distance_km'] = 'Distance de livraison requise hors Bordeaux.';
            }
        }

        if ($serviceType === 'pickup' || $serviceType === 'on_site') {
            $deliveryAddress = '';
            $deliveryCity = '';
            $deliveryPostalCode = '';
            $deliveryDistanceKm = null;
            $deliveryInstructions = '';
        }

        if ($errors !== []) {
            Session::flash('order_errors', $errors);
            Session::flash('order_old_input', $_POST);
            $this->redirect('/orders/new');
        }

        try {
            $result = $this->orderService->createOrder(
                $userId,
                (int) $menuId,
                (int) $numberOfPeople,
                $deliveryDate,
                $deliveryTime,
                $deliveryAddress,
                $deliveryCity,
                $deliveryPostalCode,
                $deliveryDistanceKm,
                null,
                $serviceType,
                'cash_on_site',
                $deliveryInstructions,
                $contactPhone
            );

            $this->redirect('/orders/confirmation/' . $result['order_id']);
        } catch (InvalidArgumentException $exception) {
            Session::flash('order_errors', ['general' => $exception->getMessage()]);
            Session::flash('order_old_input', $_POST);
            $this->redirect('/orders/new');
        } catch (\Throwable $exception) {
            error_log('Order creation error: ' . $exception->getMessage());
            Session::flash('order_errors', ['general' => 'Une erreur est survenue lors de la création de la commande.']);
            Session::flash('order_old_input', $_POST);
            $this->redirect('/orders/new');
        }
    }

    public function confirmation(int $id): void
    {
        $userId = Session::id();
        $order = $this->orderService->getOrderById($id);

        if ($userId === null || $order === null || $order->getUserId() !== $userId) {
            $this->redirect('/');
        }

        $menu = $this->menuService->getMenuById($order->getMenuId());

        $this->render('order/confirmation', [
            'order' => $order,
            'menu' => $menu,
        ]);
    }

    public function updateCustomerOrder(int $id): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        try {
            $distance = ($_POST['delivery_distance_km'] ?? '') !== ''
                ? (float) $_POST['delivery_distance_km']
                : null;

            $this->orderService->updateCustomerOrder(
                $id,
                $userId,
                (int) $_POST['number_of_people'],
                trim((string) $_POST['delivery_date']),
                trim((string) $_POST['delivery_time']),
                trim((string) $_POST['delivery_address']),
                trim((string) $_POST['delivery_city']),
                trim((string) $_POST['delivery_postal_code']),
                $distance
            );

            Session::flash('order_success', 'Commande modifiée.');
        } catch (\Throwable $exception) {
            Session::flash('order_error', $exception->getMessage());
        }

        $this->redirect('/orders');
    }

    public function review(int $id): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $order = $this->orderService->getOrderById($id);

        if ($order === null || $order->getUserId() !== $userId) {
            http_response_code(403);
            return;
        }

        if ($order->getStatus() !== 'completed') {
            Session::flash('order_error', 'Un avis est possible après la fin de la prestation.');
            $this->redirect('/orders');
        }

        try {
            $this->commentService->create([
                'user_id' => $userId,
                'order_id' => $id,
                'menu_id' => $order->getMenuId(),
                'rating' => (int) ($_POST['rating'] ?? 0),
                'comment' => trim((string) ($_POST['comment'] ?? '')),
            ]);
            Session::flash('order_success', 'Votre avis a été transmis pour validation.');
        } catch (\Throwable $exception) {
            Session::flash('order_error', $exception->getMessage());
        }

        $this->redirect('/orders');
    }

    public function updateStatus(int $id): void
    {
        $userId = Session::id();
        $role = Session::role();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $order = $this->orderService->getOrderById($id);

        if ($order === null) {
            http_response_code(404);
            echo 'Commande introuvable';
            return;
        }

        $isOwner = $order->getUserId() === $userId;
        $isStaff = in_array($role, ['employee', 'admin'], true);

        if (!$isOwner && !$isStaff) {
            http_response_code(403);
            echo 'Accès refusé';
            return;
        }

        $status = trim((string) ($_POST['status'] ?? ''));
        $cancellationReason = trim((string) ($_POST['cancellation_reason'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $equipmentLoaned = $isStaff && array_key_exists('equipment_loaned', $_POST)
            ? ((string) $_POST['equipment_loaned'] === '1')
            : null;

        if (!$isStaff && ($status !== 'cancelled' || $order->getStatus() !== 'pending')) {
            http_response_code(403);
            echo 'Une commande ne peut être annulée par le client que lorsqu’elle est en attente.';
            return;
        }

        if ($status === 'cancelled' && $isStaff) {
            $contactMode = trim((string) ($_POST['contact_mode'] ?? ''));
            if ($contactMode === '') {
                http_response_code(400);
                echo 'Le mode de contact du client est obligatoire pour une annulation.';
                return;
            }

            $notes = 'Contact client : ' . $contactMode
                . ($notes !== '' ? ' — ' . $notes : '');
        }

        if ($status === 'cancelled' && $cancellationReason === '') {
            http_response_code(400);
            echo 'Le motif d’annulation est obligatoire.';
            return;
        }

        try {
            $this->orderService->updateOrderStatus(
                $id,
                $status,
                $userId,
                $notes,
                $cancellationReason !== '' ? $cancellationReason : null,
                $equipmentLoaned
            );

            $this->redirect('/orders');
        } catch (\InvalidArgumentException $exception) {
            http_response_code(400);
            echo $exception->getMessage();
        } catch (\Throwable $exception) {
            error_log('Order status update error: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Erreur serveur';
        }
    }
    private function authPhoneError(string $phone): ?string
    {
        $value = preg_replace('/[\s().-]+/', '', $phone) ?? '';

        if (preg_match('/^(?:\+33[67]\d{8}|0[67]\d{8})$/', $value)) {
            return null;
        }

        if (preg_match('/^(?:\+34[6789]\d{8}|[6789]\d{8})$/', $value)) {
            return null;
        }

        return 'Numéro de téléphone invalide. Formats acceptés : France (+33 6/7 ou 06/07) ou Espagne (+34 6/7/8/9).';
    }

}
