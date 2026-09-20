<?php
namespace App\Repository;

use App\Core\Database;

class MongoMenuImageRepository
{
    public function findByMenuId(int $menuId): array
    {
        if ($menuId < 1) {
            return [];
        }

        $collection = Database::getMongoDatabase()->selectCollection('menu_images');
        $cursor = $collection->find(
            ['menuId' => $menuId],
            ['sort' => ['position' => 1]]
        );

        $images = [];

        foreach ($cursor as $document) {
            $images[] = [
                'path' => (string) ($document['path'] ?? $document['url'] ?? ''),
                'url' => (string) ($document['url'] ?? $document['path'] ?? ''),
                'alt_text' => (string) ($document['altText'] ?? $document['alt_text'] ?? 'Image du menu'),
                'position' => (int) ($document['position'] ?? 1),
            ];
        }

        return $images;
    }

    public function findByMenuIds(array $menuIds): array
    {
        $menuIds = array_values(array_filter(
            array_map('intval', $menuIds),
            static fn (int $id): bool => $id > 0
        ));

        if ($menuIds === []) {
            return [];
        }

        $collection = Database::getMongoDatabase()->selectCollection('menu_images');
        $cursor = $collection->find(
            ['menuId' => ['$in' => $menuIds]],
            ['sort' => ['menuId' => 1, 'position' => 1]]
        );

        $images = [];

        foreach ($cursor as $document) {
            $menuId = (int) ($document['menuId'] ?? 0);
            if ($menuId < 1) {
                continue;
            }

            $images[$menuId][] = [
                'path' => (string) ($document['path'] ?? $document['url'] ?? ''),
                'url' => (string) ($document['url'] ?? $document['path'] ?? ''),
                'alt_text' => (string) ($document['altText'] ?? $document['alt_text'] ?? 'Image du menu'),
                'position' => (int) ($document['position'] ?? 1),
            ];
        }

        return $images;
    }
}
