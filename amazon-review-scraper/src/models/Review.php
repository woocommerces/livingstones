<?php

declare(strict_types=1);

namespace App\Models;

class Review extends BaseModel
{
    protected string $table = 'reviews';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'product_id',
        'reviewer_name',
        'reviewer_id',
        'rating',
        'title',
        'body',
        'review_date',
        'review_url',
        'helpful_votes',
        'verified_purchase',
        'variant_info',
        'image_count',
        'video_count',
        'is_scraped',
    ];

    public function findByProductId(int $productId, int $limit = 0, int $offset = 0): array
    {
        return $this->findAll(['product_id' => $productId], ['review_date' => 'DESC'], $limit, $offset);
    }

    public function findByRating(int $productId, int $rating): array
    {
        return $this->findAll([
            'product_id' => $productId,
            'rating' => $rating,
        ], ['review_date' => 'DESC']);
    }

    public function findVerifiedPurchases(int $productId): array
    {
        return $this->findAll([
            'product_id' => $productId,
            'verified_purchase' => true,
        ], ['review_date' => 'DESC']);
    }

    public function findByDateRange(int $productId, string $startDate, string $endDate): array
    {
        $sql = "SELECT * FROM `reviews` 
                WHERE `product_id` = ? 
                AND `review_date` >= ? 
                AND `review_date` <= ?
                ORDER BY `review_date` DESC";
        return $this->db->fetchAll($sql, [$productId, $startDate, $endDate]);
    }

    public function createReview(array $data): int
    {
        $defaults = [
            'helpful_votes' => 0,
            'verified_purchase' => false,
            'image_count' => 0,
            'video_count' => 0,
            'is_scraped' => true,
        ];

        return $this->create(array_merge($defaults, $data));
    }

    public function updateRatings(int $id, int $rating): int
    {
        return $this->update($id, ['rating' => $rating]);
    }

    public function incrementImageCount(int $id): int
    {
        $sql = "UPDATE `reviews` SET `image_count` = `image_count` + 1 WHERE `id` = ?";
        $this->db->query($sql, [$id]);
        return 1;
    }

    public function incrementVideoCount(int $id): int
    {
        $sql = "UPDATE `reviews` SET `video_count` = `video_count` + 1 WHERE `id` = ?";
        $this->db->query($sql, [$id]);
        return 1;
    }

    public function countByProduct(int $productId): int
    {
        return $this->count(['product_id' => $productId]);
    }

    public function countByRating(int $productId, int $rating): int
    {
        return $this->count([
            'product_id' => $productId,
            'rating' => $rating,
        ]);
    }

    public function getAverageRating(int $productId): ?float
    {
        $sql = 'SELECT AVG(`rating`) FROM `reviews` WHERE `product_id` = ? AND `rating` IS NOT NULL';
        return $this->db->fetchColumn($sql, [$productId]);
    }

    public function getStatisticsByProduct(int $productId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_reviews,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star,
                    SUM(CASE WHEN verified_purchase = 1 THEN 1 ELSE 0 END) as verified_count,
                    AVG(rating) as average_rating,
                    SUM(image_count) as total_images,
                    SUM(video_count) as total_videos
                FROM reviews
                WHERE product_id = ?";
        return $this->db->fetchOne($sql, [$productId]) ?: [];
    }

    public function existsByReviewerAndProduct(string $reviewerId, int $productId): bool
    {
        $sql = 'SELECT 1 FROM `reviews` WHERE `reviewer_id` = ? AND `product_id` = ? LIMIT 1';
        return $this->db->fetchColumn($sql, [$reviewerId, $productId]) !== false;
    }

    public function searchByKeyword(int $productId, string $keyword): array
    {
        $sql = "SELECT * FROM `reviews` 
                WHERE `product_id` = ? 
                AND (`title` LIKE ? OR `body` LIKE ?)
                ORDER BY `review_date` DESC";
        $likeKeyword = '%' . $keyword . '%';
        return $this->db->fetchAll($sql, [$productId, $likeKeyword, $likeKeyword]);
    }
}
