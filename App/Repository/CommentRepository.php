<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\User;
use DateTimeImmutable;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

final class CommentRepository
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    public function findPending(): array
    {
        $cursor = Database::mongoDatabase()
            ->selectCollection('comments')
            ->find(
                ['isValidated' => false],
                ['sort' => ['createdAt' => -1]]
            );

        $reviews = [];

        foreach ($cursor as $document) {
            $user = $this->userRepository->findById((int) $document['userId']);

            $reviews[] = [
                'id' => (string) $document['_id'],
                'rating' => (int) $document['rating'],
                'comment' => (string) $document['comment'],
                'first_name' => $user?->getFirstName() ?? '',
                'last_name' => $user?->getLastName() ?? '',
                'created_at' => $this->dateFromMongo($document['createdAt'] ?? null),
            ];
        }

        return $reviews;
    }

    public function findAllValidated(): array
    {
        return $this->loadReviews(['isValidated' => true]);
    }

    public function getHomepageReviews(): array
    {
        return $this->loadReviews(
            ['isValidated' => true],
            ['sort' => ['createdAt' => -1], 'limit' => 3]
        );
    }

    public function findByOrderId(int $orderId): ?array
    {
        $document = Database::mongoDatabase()
            ->selectCollection('comments')
            ->findOne(['orderId' => $orderId]);

        if ($document === null) {
            return null;
        }

        return [
            'id' => (string) $document['_id'],
            'order_id' => (int) ($document['orderId'] ?? 0),
            'user_id' => (int) $document['userId'],
            'rating' => (int) $document['rating'],
            'comment' => (string) $document['comment'],
            'created_at' => $this->dateFromMongo($document['createdAt'] ?? null),
            'is_validated' => (bool) $document['isValidated'],
        ];
    }

    public function create(array $data): int
    {
        $result = Database::mongoDatabase()
            ->selectCollection('comments')
            ->insertOne([
                'userId' => (int) $data['user_id'],
                'menuId' => $data['menu_id'] ?? null,
                'orderId' => (int) $data['order_id'],
                'rating' => (int) $data['rating'],
                'comment' => trim((string) $data['comment']),
                'isValidated' => (bool) ($data['is_validated'] ?? false),
                'createdAt' => new UTCDateTime(new DateTimeImmutable()),
                'updatedAt' => new UTCDateTime(new DateTimeImmutable()),
            ]);

        return $result->getInsertedCount();
    }

    public function updateValidation(string $id, bool $isValidated): void
    {
        Database::mongoDatabase()
            ->selectCollection('comments')
            ->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => [
                    'isValidated' => $isValidated,
                    'updatedAt' => new UTCDateTime(new DateTimeImmutable()),
                ]]
            );
    }

    public function delete(string $id): void
    {
        Database::mongoDatabase()
            ->selectCollection('comments')
            ->deleteOne(['_id' => new ObjectId($id)]);
    }

    private function loadReviews(array $filter, array $options = []): array
    {
        $cursor = Database::mongoDatabase()
            ->selectCollection('comments')
            ->find($filter, $options);

        $reviews = [];

        foreach ($cursor as $document) {
            $user = $this->userRepository->findById((int) $document['userId']);

            $reviews[] = [
                'id' => (string) $document['_id'],
                'user_id' => (string) $document['userId'],
                'menu_id' => isset($document['menuId'])
                    ? (string) $document['menuId']
                    : null,
                'rating' => (int) $document['rating'],
                'comment' => (string) $document['comment'],
                'is_validated' => (bool) $document['isValidated'],
                'created_at' => $this->dateFromMongo($document['createdAt'] ?? null),
                'updated_at' => $this->dateFromMongo($document['updatedAt'] ?? null),
                'first_name' => $user?->getFirstName() ?? '',
                'last_name' => $user?->getLastName() ?? '',
                'user_name' => $user !== null
                    ? $user->getFirstName() . ' ' . $user->getLastName()
                    : (string) ($document['authorName'] ?? ''),
            ];
        }

        return $reviews;
    }

    private function dateFromMongo(mixed $value): ?DateTimeImmutable
    {
        if (!$value instanceof UTCDateTime) {
            return null;
        }

        return DateTimeImmutable::createFromMutable($value->toDateTime());
    }
}
