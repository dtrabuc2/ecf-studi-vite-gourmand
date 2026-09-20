<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Menu;
use App\Repository\MenuRepository;
use App\Repository\MongoMenuImageRepository;

final class MenuService
{
    public function __construct(
        private readonly MenuRepository $menuRepository,
        private readonly MongoMenuImageRepository $imageRepository,
        private readonly CacheService $cacheService
    ) {
    }

    public function getAllMenus(): array
    {
        $cached = $this->cacheService->get('menus_all');

        if (is_array($cached)) {
            return $cached;
        }

        $menus = $this->menuRepository->findAll();
        $this->cacheService->set('menus_all', $menus, 300);

        return $menus;
    }

    public function getMenuById(int $id): ?Menu
    {
        if ($id < 1) {
            return null;
        }

        $key = 'menu_' . $id;
        $cached = $this->cacheService->get($key);

        if ($cached instanceof Menu) {
            return $cached;
        }

        $menu = $this->menuRepository->findById($id);

        if ($menu !== null) {
            $this->cacheService->set($key, $menu, 300);
        }

        return $menu;
    }

    public function getMenuDetails(int $id): array
    {
        if ($id < 1) {
            return ['dishes' => [], 'allergens' => []];
        }

        return $this->menuRepository->findDetails($id);
    }

    public function getMenuImages(int $id): array
    {
        return $id > 0
            ? $this->imageRepository->findByMenuId($id)
            : [];
    }

    public function getMenusImages(array $menus): array
    {
        $ids = array_map(
            static fn (Menu $menu): int => $menu->getId(),
            $menus
        );

        return $this->imageRepository->findByMenuIds($ids);
    }

    public function filterMenus(array $filters): array
    {
        $normalized = [];

        foreach (
            ['max_price', 'min_price', 'theme', 'dietary_regime', 'min_people']
            as $key
        ) {
            if (
                isset($filters[$key])
                && $filters[$key] !== ''
            ) {
                $normalized[$key] = $filters[$key];
            }
        }

        return $this->menuRepository->filter($normalized);
    }

    public function createMenu(array $data): int
    {
        $menu = $this->hydrateInput($data);
        $id = $this->menuRepository->create($menu);
        $this->clearCache($id);

        return $id;
    }

    public function updateMenu(int $id, array $data): void
    {
        $menu = $this->menuRepository->findById($id);

        if ($menu === null) {
            throw new \InvalidArgumentException('Menu non trouvé.');
        }

        if (isset($data['title']) && trim((string) $data['title']) !== '') {
            $menu->setTitle(trim((string) $data['title']));
        }

        if (isset($data['description']) && trim((string) $data['description']) !== '') {
            $menu->setDescription(trim((string) $data['description']));
        }

        if (isset($data['theme']) && trim((string) $data['theme']) !== '') {
            $menu->setTheme(trim((string) $data['theme']));
        }

        if (isset($data['dietary_regime']) && trim((string) $data['dietary_regime']) !== '') {
            $menu->setDietaryRegime((string) $data['dietary_regime']);
        }

        if (isset($data['min_people']) && is_numeric($data['min_people']) && (int) $data['min_people'] > 0) {
            $menu->setMinPeople((int) $data['min_people']);
        }

        if (isset($data['base_price']) && is_numeric($data['base_price']) && (float) $data['base_price'] >= 0) {
            $menu->setBasePrice((float) $data['base_price']);
        }

        if (isset($data['conditions']) && trim((string) $data['conditions']) !== '') {
            $menu->setConditions(trim((string) $data['conditions']));
        }

        if (isset($data['available_stock']) && is_numeric($data['available_stock']) && (int) $data['available_stock'] >= 0) {
            $menu->setAvailableStock((int) $data['available_stock']);
        }

        $this->menuRepository->update($menu);
        $this->clearCache($id);
    }

    public function deleteMenu(int $id): void
    {
        $this->menuRepository->delete($id);
        $this->clearCache($id);
    }

    private function hydrateInput(array $data): Menu
    {
        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $theme = trim((string) ($data['theme'] ?? ''));
        $conditions = trim((string) ($data['conditions'] ?? ''));

        if ($title === '') {
            throw new \InvalidArgumentException('Le titre est requis.');
        }

        if ($description === '') {
            throw new \InvalidArgumentException('La description est requise.');
        }

        if ($theme === '') {
            throw new \InvalidArgumentException('Le thème est requis.');
        }

        if ($conditions === '') {
            throw new \InvalidArgumentException('Les conditions sont requises.');
        }

        $minPeople = filter_var(
            $data['min_people'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $stock = filter_var(
            $data['available_stock'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );

        $price = filter_var(
            $data['base_price'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        if ($minPeople === false) {
            throw new \InvalidArgumentException(
                'Le nombre minimum de personnes est invalide.'
            );
        }

        if ($stock === false) {
            throw new \InvalidArgumentException(
                'Le stock disponible est invalide.'
            );
        }

        if ($price === false || $price < 0) {
            throw new \InvalidArgumentException(
                'Le prix de base est invalide.'
            );
        }

        $menu = new Menu();
        $menu->setTitle($title);
        $menu->setDescription($description);
        $menu->setTheme($theme);
        $menu->setDietaryRegime(
            (string) ($data['dietary_regime'] ?? 'classic')
        );
        $menu->setMinPeople($minPeople);
        $menu->setBasePrice($price);
        $menu->setConditions($conditions);
        $menu->setAvailableStock($stock);

        return $menu;
    }

    private function clearCache(int $id): void
    {
        $this->cacheService->delete('menus_all');
        $this->cacheService->delete('menu_' . $id);
    }
}
