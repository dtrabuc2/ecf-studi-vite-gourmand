<?php
namespace App\Controller;

use App\Service\MenuService;
use App\Service\CommentService;
use App\Repository\MenuRepository;
use App\Repository\CommentRepository;
use App\Repository\MongoMenuImageRepository;

class PublicController extends BaseController
{
    private MenuService $menuService;
    private CommentService $commentService;
    private MongoMenuImageRepository $menuImageRepository;

    public function __construct()
    {
        $this->menuService = new MenuService(new MenuRepository());
        $this->commentService = new CommentService(new CommentRepository());
        $this->menuImageRepository = new MongoMenuImageRepository();
    }

    public function index(): void
    {
        try {
            $reviews = $this->commentService->getHomepageReviews();
        } catch (\Throwable $exception) {
            error_log('Impossible de charger les avis : ' . $exception->getMessage());
            $reviews = [];
        }

        try {
            $menus = array_slice($this->menuService->getAllMenus(), 0, 3);
        } catch (\Throwable $exception) {
            error_log('Impossible de charger les menus : ' . $exception->getMessage());
            $menus = [];
        }

        $this->render('home/index', [
            'reviews' => $reviews,
            'menus' => $menus,
            'menuDetails' => $this->loadMenuDetails($menus),
            'menuImages' => $this->loadMenuImages($menus),
            'user' => $_SESSION['user_id'] ?? null,
        ]);
    }

    public function menusPage(): void
    {
        try {
            $menus = $this->menuService->getAllMenus();
        } catch (\Throwable $exception) {
            error_log('Impossible de charger le catalogue : ' . $exception->getMessage());
            $menus = [];
        }

        $this->render('home/menus', [
            'menus' => $menus,
            'menuDetails' => $this->loadMenuDetails($menus),
            'menuImages' => $this->loadMenuImages($menus),
            'user' => $_SESSION['user_id'] ?? null,
        ]);
    }

    public function menuDetail(int $id): void
    {
        $menu = $id > 0 ? $this->menuService->getMenuById($id) : null;
        if ($menu === null) {
            http_response_code(404);
            $this->render('home/menu_detail', ['menu' => null]);
            return;
        }
        try {
            $details = (new MenuRepository())->findDetails($id);
        } catch (\Throwable $exception) {
            error_log('Impossible de charger les détails du menu : ' . $exception->getMessage());
            $details = ['dishes' => [], 'allergens' => []];
        }

        try {
            $menuImages = $this->menuImageRepository->findByMenuId($id);
        } catch (\Throwable $exception) {
            error_log('Erreur images MongoDB : ' . $exception->getMessage());
            $menuImages = [];
        }

        $this->render('home/menu_detail', [
            'menu' => $menu,
            'details' => $details,
            'menuImages' => $menuImages,
            'user' => $_SESSION['user_id'] ?? null,
        ]);
    }

    public function getMenus(): void
    {
        try {
            $this->jsonMenus($this->menuService->getAllMenus());
        } catch (\Throwable $exception) {
            error_log('Erreur API menus : ' . $exception->getMessage());
            echo $this->jsonError('Impossible de charger les menus.', 500);
        }
    }

    public function getMenuById(int $id): void
    {
        if ($id <= 0) { http_response_code(400); echo $this->jsonError('Identifiant invalide'); return; }
        $menu = $this->menuService->getMenuById($id);
        if ($menu === null) {
            http_response_code(404);
            echo $this->jsonError('Menu introuvable');
            return;
        }

        try {
            $details = (new MenuRepository())->findDetails($id);
        } catch (\Throwable $exception) {
            error_log('Erreur détails menu API : ' . $exception->getMessage());
            $details = ['dishes' => [], 'allergens' => []];
        }

        header('Content-Type: application/json');
        echo $this->jsonSuccess($this->menuToArray($menu, $details));
    }

    public function filterMenus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo $this->jsonError('Method Not Allowed');
            return;
        }

        $filters = [];
        foreach (['max_price', 'min_price', 'theme', 'dietary_regime', 'min_people'] as $key) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $filters[$key] = $_GET[$key];
            }
        }

        try {
            $menus = $this->menuService->filterMenus($filters);
            $this->jsonMenus($menus);
        } catch (\Throwable $exception) {
            error_log('Erreur API filtre menus : ' . $exception->getMessage());
            echo $this->jsonError('Impossible de filtrer les menus.', 500);
        }
    }

    private function jsonMenus(array $menus): void
    {
        $repository = new MenuRepository();
        $payload = [];

        foreach ($menus as $menu) {
            try {
                $details = $repository->findDetails($menu->getId());
                $payload[] = $this->menuToArray($menu, $details);
            } catch (\Throwable $exception) {
                error_log('Erreur détails menu #' . $menu->getId() . ' : ' . $exception->getMessage());
                $payload[] = $this->menuToArray($menu);
            }
        }

        header('Content-Type: application/json');
        echo $this->jsonSuccess($payload);
    }

    private function loadMenuDetails(array $menus): array
    {
        $repository = new MenuRepository();
        $details = [];

        foreach ($menus as $menu) {
            try {
                $details[$menu->getId()] = $repository->findDetails($menu->getId());
            } catch (\Throwable $exception) {
                error_log('Erreur détails menu #' . $menu->getId() . ' : ' . $exception->getMessage());
                $details[$menu->getId()] = ['dishes' => [], 'allergens' => []];
            }
        }

        return $details;
    }

    private function loadMenuImages(array $menus): array
    {
        $ids = array_map(static fn ($menu): int => $menu->getId(), $menus);

        try {
            return $this->menuImageRepository->findByMenuIds($ids);
        } catch (\Throwable $exception) {
            error_log('Erreur images MongoDB : ' . $exception->getMessage());
            return [];
        }
    }

    public function legal(): void
    {
        $this->render('home/legal');
    }

    public function cgv(): void
    {
        $this->render('home/cgv');
    }

    private function menuToArray(\App\Entity\Menu $menu, array $details = []): array
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
            'dishes' => $details['dishes'] ?? [],
            'allergens' => $details['allergens'] ?? [],
            'images' => $this->menuImageRepository->findByMenuId($menu->getId()),
        ];
    }
}
