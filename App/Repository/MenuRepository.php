<?php
namespace App\Repository;

use App\Entity\Menu;
use App\Core\Database;

class MenuRepository
{
    public function findAll(): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->query('SELECT * FROM menus WHERE is_active = 1 AND available_stock > 0 ORDER BY id DESC');
        return $this->hydrateMany($stmt->fetchAll());
    }

    public function findById(int $id): ?Menu
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM menus WHERE id = :id AND is_active = 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $this->hydrate($row);
    }

    public function filter(array $filters): array
    {
        $pdo = Database::getPDO();
        $query = 'SELECT * FROM menus WHERE is_active = 1 AND available_stock > 0';
        $params = [];

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $maxPrice = filter_var($filters['max_price'], FILTER_VALIDATE_FLOAT);
            if ($maxPrice === false || $maxPrice < 0) {
                throw new \InvalidArgumentException('Le prix maximum est invalide.');
            }
            $query .= ' AND base_price <= :max_price';
            $params['max_price'] = $maxPrice;
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $minPrice = filter_var($filters['min_price'], FILTER_VALIDATE_FLOAT);
            if ($minPrice === false || $minPrice < 0) {
                throw new \InvalidArgumentException('Le prix minimum est invalide.');
            }
            $query .= ' AND base_price >= :min_price';
            $params['min_price'] = $minPrice;
        }

        if (!empty($filters['theme'])) {
            $query .= ' AND theme = :theme';
            $params['theme'] = trim((string) $filters['theme']);
        }

        if (!empty($filters['dietary_regime'])) {
            $allowedRegimes = ['classic', 'vegetarian', 'vegan', 'other'];
            $regime = trim((string) $filters['dietary_regime']);
            if (!in_array($regime, $allowedRegimes, true)) {
                throw new \InvalidArgumentException('Le régime alimentaire est invalide.');
            }
            $query .= ' AND dietary_regime = :dietary_regime';
            $params['dietary_regime'] = $regime;
        }

        if (isset($filters['min_people']) && $filters['min_people'] !== '') {
            $minPeople = filter_var($filters['min_people'], FILTER_VALIDATE_INT);
            if ($minPeople === false || $minPeople < 1) {
                throw new \InvalidArgumentException('Le nombre de personnes est invalide.');
            }
            $query .= ' AND min_people <= :min_people';
            $params['min_people'] = $minPeople;
        }

        if (isset($params['min_price'], $params['max_price']) && $params['min_price'] > $params['max_price']) {
            throw new \InvalidArgumentException('La fourchette de prix est invalide.');
        }

        $query .= ' ORDER BY id DESC';
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $this->hydrateMany($stmt->fetchAll());
    }

    public function findDetails(int $menuId): array
    {
        return [];
    }

    public function create(Menu $menu): int
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('INSERT INTO menus
            (title, description, theme, dietary_regime, min_people, base_price, conditions, available_stock)
            VALUES (:title, :description, :theme, :dietary_regime, :min_people, :base_price, :conditions, :available_stock)');
        $stmt->execute([
            'title' => $menu->getTitle(),
            'description' => $menu->getDescription(),
            'theme' => $menu->getTheme(),
            'dietary_regime' => $menu->getDietaryRegime(),
            'min_people' => $menu->getMinPeople(),
            'base_price' => $menu->getBasePrice(),
            'conditions' => $menu->getConditions(),
            'available_stock' => $menu->getAvailableStock(),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function update(Menu $menu): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('UPDATE menus SET
            title = :title, description = :description, theme = :theme,
            dietary_regime = :dietary_regime, min_people = :min_people,
            base_price = :base_price, conditions = :conditions,
            available_stock = :available_stock, updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            'id' => $menu->getId(),
            'title' => $menu->getTitle(),
            'description' => $menu->getDescription(),
            'theme' => $menu->getTheme(),
            'dietary_regime' => $menu->getDietaryRegime(),
            'min_people' => $menu->getMinPeople(),
            'base_price' => $menu->getBasePrice(),
            'conditions' => $menu->getConditions(),
            'available_stock' => $menu->getAvailableStock(),
        ]);
    }

    public function delete(int $id): void
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('UPDATE menus SET is_active = 0, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function hydrateMany(array $rows): array
    {
        return array_map($this->hydrate(...), $rows);
    }

    private function hydrate(array $row): Menu
    {
        $menu = new Menu();
        $menu->setId((int) $row['id']);
        $menu->setTitle($row['title']);
        $menu->setDescription($row['description']);
        $menu->setTheme($row['theme']);
        $menu->setDietaryRegime($row['dietary_regime'] ?? 'classic');
        $menu->setMinPeople((int) $row['min_people']);
        $menu->setBasePrice((float) $row['base_price']);
        $menu->setConditions($row['conditions']);
        $menu->setAvailableStock((int) $row['available_stock']);
        $menu->setCreatedAt(!empty($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null);
        $menu->setUpdatedAt(!empty($row['updated_at']) ? new \DateTimeImmutable($row['updated_at']) : null);
        return $menu;
    }
}
