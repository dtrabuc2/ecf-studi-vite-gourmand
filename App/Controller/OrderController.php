<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Repository\UserRepository;
use App\Service\CommentService;
use App\Service\MenuService;
use App\Service\OrderService;

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

        $this->render('order/new', [
            'menus' => $menus,
            'selectedMenuId' => $selectedMenuId,
            'orderUser' => $user,
        ]);
    }

    public function create(): void
    {
        $userId = Session::id();

        if ($userId === null) {
            $this->redirect('/login');
        }

        $menuId = $_POST['menu_id'] ?? null;
        $numberOfPeople = $_POST['number_of_people'] ?? null;
        $deliveryDate = trim((string) ($_POST['delivery_date'] ?? ''));
        $deliveryTime = trim((string) ($_POST['delivery_time'] ?? ''));
        $deliveryAddress = trim((string) ($_POST['delivery_address'] ?? ''));
        $deliveryCity = trim((string) ($_POST['delivery_city'] ?? ''));
        $deliveryPostalCode = trim((string) ($_POST['delivery_postal_code'] ?? ''));
        $deliveryDistanceKm = isset($_POST['delivery_distance_km']) && $_POST['delivery_distance_km'] !== ''
            ? (float) $_POST['delivery_distance_km']
            : null;

        $errors = [];

        if (!is_numeric($menuId)) {
            $errors['menu_id'] = 'Menu requis.';
        }

        if (!is_numeric($numberOfPeople) || (int) $numberOfPeople < 1) {
            $errors['number_of_people'] = 'Nombre de personnes requis et supérieur à 0.';
        }

        if ($deliveryDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deliveryDate)) {
            $errors['delivery_date'] = 'Date de livraison invalide.';
        } elseif ($deliveryDate < date('Y-m-d')) {
            $errors['delivery_date'] = 'La date de livraison ne peut pas être passée.';
        }

        if ($deliveryTime === '' || !preg_match('/^\d{2}:\d{2}$/', $deliveryTime)) {
            $errors['delivery_time'] = 'Heure de livraison invalide.';
        }

        if ($deliveryAddress === '') {
            $errors['delivery_address'] = 'Adresse de livraison requise.';
        }

        if ($deliveryCity === '') {
            $errors['delivery_city'] = 'Ville de livraison requise.';
        }

        if (
            $deliveryCity !== ''
            && mb_strtolower($deliveryCity) !== 'bordeaux'
            && ($deliveryDistanceKm === null || $deliveryDistanceKm < 0)
        ) {
            $errors['delivery_distance_km'] = 'Distance de livraison requise hors Bordeaux.';
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
                $deliveryDistanceKm
            );

            $this->redirect('/orders/confirmation/' . $result['order_id']);
        } catch (\InvalidArgumentException $exception) {
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
}
