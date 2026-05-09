<?php

declare(strict_types=1);

namespace App\Models;

class ScrapingTask extends BaseModel
{
    protected string $table = 'scraping_tasks';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'product_id',
        'task_type',
        'status',
        'priority',
        'total_reviews',
        'scraped_reviews',
        'total_pages',
        'current_page',
        'error_message',
        'retry_count',
        'started_at',
        'completed_at',
    ];

    public const TYPE_FULL = 'full';
    public const TYPE_INCREMENTAL = 'incremental';
    public const TYPE_IMAGES_ONLY = 'images_only';

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PAUSED = 'paused';

    public function findByProductId(int $productId): array
    {
        return $this->findAll(['product_id' => $productId], ['created_at' => 'DESC']);
    }

    public function findByStatus(string $status): array
    {
        return $this->findAll(['status' => $status], ['priority' => 'DESC', 'created_at' => 'ASC']);
    }

    public function findPendingTasks(int $limit = 10): array
    {
        $sql = "SELECT * FROM `scraping_tasks` 
                WHERE `status` = 'pending' 
                ORDER BY `priority` DESC, `created_at` ASC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    public function findRunningTasks(): array
    {
        return $this->findByStatus(self::STATUS_RUNNING);
    }

    public function createTask(array $data): int
    {
        $defaults = [
            'status' => self::STATUS_PENDING,
            'task_type' => self::TYPE_FULL,
            'priority' => 5,
            'total_reviews' => 0,
            'scraped_reviews' => 0,
            'total_pages' => 0,
            'current_page' => 0,
            'retry_count' => 0,
        ];

        return $this->create(array_merge($defaults, $data));
    }

    public function start(int $id): int
    {
        return $this->update($id, [
            'status' => self::STATUS_RUNNING,
            'started_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function complete(int $id): int
    {
        return $this->update($id, [
            'status' => self::STATUS_COMPLETED,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function fail(int $id, string $errorMessage): int
    {
        return $this->update($id, [
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    public function pause(int $id): int
    {
        return $this->update($id, ['status' => self::STATUS_PAUSED]);
    }

    public function resume(int $id): int
    {
        return $this->update($id, ['status' => self::STATUS_RUNNING]);
    }

    public function incrementRetry(int $id): int
    {
        $sql = "UPDATE `scraping_tasks` SET `retry_count` = `retry_count` + 1 WHERE `id` = ?";
        $this->db->query($sql, [$id]);
        return 1;
    }

    public function updateProgress(int $id, int $currentPage, int $scrapedReviews): int
    {
        return $this->update($id, [
            'current_page' => $currentPage,
            'scraped_reviews' => $scrapedReviews,
        ]);
    }

    public function updateTotalPages(int $id, int $totalPages): int
    {
        return $this->update($id, ['total_pages' => $totalPages]);
    }

    public function countByStatus(string $status): int
    {
        return $this->count(['status' => $status]);
    }

    public function countPending(): int
    {
        return $this->countByStatus(self::STATUS_PENDING);
    }

    public function countRunning(): int
    {
        return $this->countByStatus(self::STATUS_RUNNING);
    }

    public function getActiveTasks(): array
    {
        return $this->findAll([
            'status' => [self::STATUS_RUNNING, self::STATUS_PENDING],
        ], ['priority' => 'DESC']);
    }

    public function getRecentTasks(int $limit = 20): array
    {
        return $this->findAll([], ['created_at' => 'DESC'], $limit);
    }

    public function getStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) as running,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = 'paused' THEN 1 ELSE 0 END) as paused,
                    SUM(scraped_reviews) as total_reviews_scraped
                FROM scraping_tasks";
        return $this->db->fetchOne($sql) ?: [];
    }
}
