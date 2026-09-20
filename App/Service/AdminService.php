<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Repository\MenuRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use InvalidArgumentException;

final class AdminService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly OrderRepository $orderRepository,
        private readonly MenuRepository $menuRepository,
        private readonly MenuStatisticsService $menuStatisticsService
    ) {
    }

    public function createEmployee(array $data): int
    {
        $password = (string) ($data['password'] ?? '');
        $errors = $this->validatePassword($password);

        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresse email invalide.');
        }

        if ($this->userRepository->findByEmail($email) !== null) {
            throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
        }

        return $this->userRepository->create([
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'employee',
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'phone' => trim((string) $data['phone']),
            'gsm' => trim((string) $data['gsm']),
            'address' => trim((string) $data['address']),
        ]);
    }

    public function getCustomers(?string $search = null, ?bool $active = null): array
    {
        return $this->userRepository->findCustomers($search, $active);
    }

    public function disableCustomer(int $id): void
    {
        $this->userRepository->setCustomerActive($id, false);
    }

    public function enableCustomer(int $id): void
    {
        $this->userRepository->setCustomerActive($id, true);
    }

    public function getEmployees(): array
    {
        $stmt = Database::pdo()->query(
            "SELECT id, email, first_name, last_name, role, is_active, created_at
             FROM users
             WHERE role = 'employee'
             ORDER BY created_at DESC"
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'email' => $row['email'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'role' => $row['role'],
                'is_active' => (bool) $row['is_active'],
                'created_at' => !empty($row['created_at'])
                    ? new \DateTimeImmutable($row['created_at'])
                    : null,
            ],
            $stmt->fetchAll()
        );
    }

    public function disableEmployee(int $id): void
    {
        $this->userRepository->setActive($id, false);
    }

    public function enableEmployee(int $id): void
    {
        $this->userRepository->setActive($id, true);
    }

    public function getDashboardStats(): array
    {
        $pdo = Database::pdo();

        $totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $totalOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $pendingOrders = (int) $pdo->query(
            "SELECT COUNT(*) FROM orders WHERE status = 'pending'"
        )->fetchColumn();
        $totalRevenue = (float) $pdo->query(
            "SELECT COALESCE(SUM(total_price), 0)
             FROM orders
             WHERE status = 'completed'"
        )->fetchColumn();

        $menuStats = [];
        $menuStatsError = null;

        try {
            $statistics = $this->menuStatisticsService->getStatistics('all_time');

            foreach ($statistics as $stat) {
                $menuStats[] = [
                    'menu_id' => (string) $stat['menuId'],
                    'menu_title' => $stat['menuTitle'],
                    'order_count' => (int) $stat['orderCount'],
                    'revenue' => (float) $stat['revenue'],
                ];
            }
        } catch (\Throwable $exception) {
            error_log(
                'Statistiques MongoDB indisponibles : '
                . $exception->getMessage()
            );
            $menuStatsError = 'Les statistiques de commandes par menu sont momentanément indisponibles.';
        }

        return [
            'total_users' => $totalUsers,
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingOrders,
            'total_revenue' => $totalRevenue,
            'menu_stats' => $menuStats,
            'menu_stats_error' => $menuStatsError,
        ];
    }

    public function getRevenueByMenu(
        ?string $from = null,
        ?string $to = null,
        ?int $menuId = null
    ): array {
        $sql = "SELECT
                    m.id AS menu_id,
                    m.title AS menu_title,
                    COUNT(o.id) AS order_count,
                    COALESCE(SUM(o.total_price), 0) AS revenue
                FROM menus m
                LEFT JOIN orders o
                    ON o.menu_id = m.id
                   AND o.status = 'completed'";

        $where = [];
        $params = [];

        if ($from !== null && $from !== '') {
            $where[] = 'o.delivery_date >= :from_date';
            $params['from_date'] = $from;
        }

        if ($to !== null && $to !== '') {
            $where[] = 'o.delivery_date <= :to_date';
            $params['to_date'] = $to;
        }

        if ($menuId !== null && $menuId > 0) {
            $where[] = 'm.id = :menu_id';
            $params['menu_id'] = $menuId;
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY m.id, m.title ORDER BY revenue DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return array_map(
            static fn (array $row): array => [
                'menu_id' => (int) $row['menu_id'],
                'menu_title' => $row['menu_title'],
                'order_count' => (int) $row['order_count'],
                'revenue' => (float) $row['revenue'],
            ],
            $stmt->fetchAll()
        );
    }

    private function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < 10) {
            $errors[] = 'Le mot de passe doit contenir au moins 10 caractères.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une minuscule.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
        }

        return $errors;
    }
}
