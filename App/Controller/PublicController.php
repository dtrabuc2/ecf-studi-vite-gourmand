<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Repository\CommentRepository;
use App\Repository\MenuRepository;
use App\Repository\MongoMenuImageRepository;
use App\Service\CommentService;
use App\Service\MenuService;

final class PublicController extends BaseController
{
    private readonly MenuService $menuService;
    private readonly CommentService $commentService;

    public function __construct()
    {
        $this->menuService = new MenuService(
            new MenuRepository(),
            new MongoMenuImageRepository(),
            new \App\Service\CacheService()
        );
        $this->commentService = new CommentService(
            new CommentRepository()
        );
    }

    public function index(): void
    {
        try {
            $menus = array_slice(
                $this->menuService->getAllMenus(),
                0,
                3
            );
        } catch (\Throwable $exception) {
            error_log(
                'Impossible de charger les menus : '
                . $exception->getMessage()
            );
            $menus = [];
        }

        try {
            $reviews = $this->commentService->getHomepageReviews();
        } catch (\Throwable $exception) {
            error_log(
                'Impossible de charger les avis : '
                . $exception->getMessage()
            );
            $reviews = [];
        }

        $this->render('home/index', [
            'reviews' => $reviews,
            'menus' => $menus,
            'menuImages' => $this->menuService->getMenusImages($menus),
            'user' => Session::id(),
        ]);
    }

    public function menusPage(): void
    {
        try {
            $menus = $this->menuService->getAllMenus();
        } catch (\Throwable $exception) {
            error_log(
                'Impossible de charger le catalogue : '
                . $exception->getMessage()
            );
            $menus = [];
        }

        $this->render('home/menus', [
            'menus' => $menus,
            'menuDetails' => $this->loadDetails($menus),
            'menuImages' => $this->menuService->getMenusImages($menus),
            'user' => Session::id(),
        ]);
    }

    public function menuDetail(int $id): void
    {
        $menu = $this->menuService->getMenuById($id);

        if ($menu === null) {
            http_response_code(404);
            $this->render('home/menu_detail', ['menu' => null]);
            return;
        }

        $this->render('home/menu_detail', [
            'menu' => $menu,
            'details' => $this->menuService->getMenuDetails($id),
            'menuImages' => $this->menuService->getMenuImages($id),
            'user' => Session::id(),
        ]);
    }

    public function getMenus(): void
    {
        $this->json([
            'success' => true,
            'data' => $this->serializeMenus(
                $this->menuService->getAllMenus()
            ),
        ]);
    }

    public function getMenuById(int $id): void
    {
        $menu = $this->menuService->getMenuById($id);

        if ($menu === null) {
            $this->json([
                'success' => false,
                'error' => 'Menu introuvable.'
            ], 404);
        }

        $this->json([
            'success' => true,
            'data' => $this->serializeMenu($menu),
        ]);
    }

    public function filterMenus(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            $this->json([
                'success' => false,
                'error' => 'Méthode HTTP non autorisée.'
            ], 405);
        }

        $filters = [];

        foreach (
            ['max_price', 'min_price', 'theme', 'dietary_regime', 'min_people']
            as $key
        ) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $filters[$key] = $_GET[$key];
            }
        }

        $this->json([
            'success' => true,
            'data' => $this->serializeMenus(
                $this->menuService->filterMenus($filters)
            ),
        ]);
    }

    public function legal(): void
    {
        $this->render('home/legal');
    }

    public function cgv(): void
    {
        $this->render('home/cgv');
    }

    private function serializeMenus(array $menus): array
    {
        return array_map(
            fn ($menu): array => $this->serializeMenu($menu),
            $menus
        );
    }

    private function serializeMenu(\App\Entity\Menu $menu): array
    {
        return [
            'id' => $menu->getId(),
            'title' => $menu->getTitle(),
            'description' => $menu->getDescription(),
            'theme' => $menu->getTheme(),
            'dietary_regime' => $menu->getDietaryRegime(),
            'min_people' => $menu->getMinPeople(),
            'base_price' => $menu->getBasePrice(),
            'conditions' => $menu->getConditions(),
            'available_stock' => $menu->getAvailableStock(),
            'dishes' => $this->menuService->getMenuDetails(
                $menu->getId()
            )['dishes'] ?? [],
            'allergens' => $this->menuService->getMenuDetails(
                $menu->getId()
            )['allergens'] ?? [],
            'images' => $this->menuService->getMenuImages(
                $menu->getId()
            ),
        ];
    }

    private function loadDetails(array $menus): array
    {
        $details = [];

        foreach ($menus as $menu) {
            $details[$menu->getId()] = $this->menuService
                ->getMenuDetails($menu->getId());
        }

        return $details;
    }
}
