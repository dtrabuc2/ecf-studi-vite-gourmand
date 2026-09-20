<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class MongoMenuImageRepository
{
    public function findByMenuId(int $menuId): array
    {
        if ($menuId < 1) {
            return [];
        }

        return $this->findByMenuIds([$menuId])[$menuId] ?? [];
    }

    public function findByMenuIds(array $menuIds): array
    {
        $menuIds = array_values(array_unique(array_filter(
            array_map('intval', $menuIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($menuIds === []) {
            return [];
        }

        $collection = Database::mongoDatabase()->selectCollection('menu_images');

        $cursor = $collection->find(
            ['menuId' => ['$in' => $menuIds]],
            [
                'sort' => ['menuId' => 1, 'position' => 1],
                'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
            ]
        );

        $images = [];

        foreach ($cursor as $document) {
            $menuId = (int) ($document['menuId'] ?? 0);
            $url = trim((string) ($document['url'] ?? $document['path'] ?? ''));

            if ($menuId < 1 || $url === '') {
                continue;
            }

            $images[$menuId][] = [
                'url' => $url,
                'path' => $url,
                'alt_text' => (string) ($document['altText'] ?? $document['alt_text'] ?? 'Image du menu'),
                'position' => (int) ($document['position'] ?? 1),
            ];
        }

        return $images;
    }
}