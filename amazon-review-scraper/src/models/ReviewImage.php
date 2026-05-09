<?php

declare(strict_types=1);

namespace App\Models;

class ReviewImage extends BaseModel
{
    protected string $table = 'review_images';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'review_id',
        'original_url',
        'local_path',
        'thumbnail_path',
        'file_name',
        'file_size',
        'mime_type',
        'is_video',
        'download_status',
    ];

    public function findByReviewId(int $reviewId): array
    {
        return $this->findAll(['review_id' => $reviewId], ['created_at' => 'ASC']);
    }

    public function findImagesByReviewId(int $reviewId): array
    {
        return $this->findAll([
            'review_id' => $reviewId,
            'is_video' => false,
        ], ['created_at' => 'ASC']);
    }

    public function findVideosByReviewId(int $reviewId): array
    {
        return $this->findAll([
            'review_id' => $reviewId,
            'is_video' => true,
        ], ['created_at' => 'ASC']);
    }

    public function findPendingDownloads(): array
    {
        return $this->findAll(['download_status' => 'pending'], ['created_at' => 'ASC']);
    }

    public function createImage(array $data): int
    {
        $defaults = [
            'is_video' => false,
            'download_status' => 'pending',
        ];

        return $this->create(array_merge($defaults, $data));
    }

    public function updateDownloadStatus(int $id, string $status): int
    {
        return $this->update($id, ['download_status' => $status]);
    }

    public function updateLocalPath(int $id, string $localPath, ?string $thumbnailPath = null): int
    {
        $data = ['local_path' => $localPath];
        if ($thumbnailPath !== null) {
            $data['thumbnail_path'] = $thumbnailPath;
        }
        return $this->update($id, $data);
    }

    public function markAsDownloaded(int $id, string $localPath, int $fileSize): int
    {
        return $this->update($id, [
            'local_path' => $localPath,
            'file_size' => $fileSize,
            'download_status' => 'completed',
        ]);
    }

    public function markAsFailed(int $id): int
    {
        return $this->update($id, ['download_status' => 'failed']);
    }

    public function countByReview(int $reviewId): int
    {
        return $this->count(['review_id' => $reviewId]);
    }

    public function countImagesByReview(int $reviewId): int
    {
        return $this->count([
            'review_id' => $reviewId,
            'is_video' => false,
        ]);
    }

    public function countVideosByReview(int $reviewId): int
    {
        return $this->count([
            'review_id' => $reviewId,
            'is_video' => true,
        ]);
    }

    public function getStorageStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_video = 0 THEN 1 ELSE 0 END) as images,
                    SUM(CASE WHEN is_video = 1 THEN 1 ELSE 0 END) as videos,
                    SUM(CASE WHEN download_status = 'completed' THEN file_size ELSE 0 END) as total_size
                FROM review_images";
        return $this->db->fetchOne($sql) ?: [];
    }

    public function deleteByReviewId(int $reviewId): int
    {
        return $this->db->delete($this->table, '`review_id` = ?', [$reviewId]);
    }
}
