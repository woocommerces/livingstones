<?php

declare(strict_types=1);

namespace App\Models;

class Product extends BaseModel
{
    protected string $table = 'products';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'asin',
        'title',
        'url',
        'image_url',
        'current_price',
        'currency',
        'rating',
        'review_count',
        'status',
        'last_scraped_at',
    ];

    public function findByAsin(string $asin): ?array
    {
        $sql = 'SELECT * FROM `products` WHERE `asin` = ? LIMIT 1';
        return $this->db->fetchOne($sql, [$asin]);
    }

    public function findByStatus(string $status, int $limit = 0, int $offset = 0): array
    {
        return $this->findAll(['status' => $status], ['created_at' => 'DESC'], $limit, $offset);
    }

    public function findPendingProducts(int $limit = 10): array
    {
        $sql = "SELECT * FROM `products` WHERE `status` = 'pending' ORDER BY `created_at` ASC LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    public function createProduct(array $data): int
    {
        $defaults = [
            'status' => 'pending',
            'currency' => 'USD',
            'review_count' => 0,
        ];

        return $this->create(array_merge($defaults, $data));
    }

    public function updateStatus(int $id, string $status): int
    {
        return $this->update($id, ['status' => $status]);
    }

    public function updateScrapedAt(int $id): int
    {
        return $this->update($id, ['last_scraped_at' => date('Y-m-d H:i:s')]);
    }

    public function updateRatingAndReviewCount(int $id, float $rating, int $reviewCount): int
    {
        return $this->update($id, [
            'rating' => $rating,
            'review_count' => $reviewCount,
        ]);
    }

    public function countByStatus(string $status): int
    {
        return $this->count(['status' => $status]);
    }

    public function countAll(): int
    {
        return $this->count();
    }

    public function getStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'scraping' THEN 1 ELSE 0 END) as scraping,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM `products`";
        return $this->db->fetchOne($sql) ?: [];
    }
}
