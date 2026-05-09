<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\ScrapingTask;
use App\Models\Setting;
use App\Utils\Database;
use Exception;

class ScraperService
{
    private $db;
    private $productModel;
    private $reviewModel;
    private $imageModel;
    private $taskModel;
    private $settingModel;
    private $amazonApi;
    private $downloadService;
    private $config = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->productModel = new Product();
        $this->reviewModel = new Review();
        $this->imageModel = new ReviewImage();
        $this->taskModel = new ScrapingTask();
        $this->settingModel = new Setting();
        $this->amazonApi = new AmazonAPI();
        $this->downloadService = new DownloadService();
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        $this->config = [
            'delay_min' => (int) $this->settingModel->getValue('scraper_delay_min', 3),
            'delay_max' => (int) $this->settingModel->getValue('scraper_delay_max', 8),
            'max_retries' => (int) $this->settingModel->getValue('scraper_max_retries', 3),
            'timeout' => (int) $this->settingModel->getValue('scraper_timeout', 30),
            'user_agent' => $this->settingModel->getValue('scraper_user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
            'images_enabled' => (bool) $this->settingModel->getValue('scraper_images_enabled', true),
            'videos_enabled' => (bool) $this->settingModel->getValue('scraper_videos_enabled', true),
            'max_images_per_review' => (int) $this->settingModel->getValue('scraper_max_images_per_review', 10),
            'concurrent_tasks' => (int) $this->settingModel->getValue('scraper_concurrent_tasks', 2),
            'pagination_max_pages' => (int) $this->settingModel->getValue('pagination_max_pages', 100),
        ];
    }

    public function scrapeProduct(int $productId, array $options = []): array
    {
        $product = $this->productModel->findById($productId);
        if (!$product) {
            throw new Exception("商品不存在: ID {$productId}");
        }

        $taskType = $options['task_type'] ?? 'full';
        $maxPages = min($options['max_pages'] ?? $this->config['pagination_max_pages'], $this->config['pagination_max_pages']);
        $startPage = $options['start_page'] ?? 1;

        $task = $this->taskModel->create([
            'product_id' => $productId,
            'task_type' => $taskType,
            'status' => ScrapingTask::STATUS_RUNNING,
            'priority' => $options['priority'] ?? 5,
            'total_pages' => $maxPages,
        ]);

        $this->productModel->update($productId, ['status' => Product::STATUS_SCRAPING]);

        try {
            $totalReviews = 0;
            $currentPage = $startPage;

            while ($currentPage <= $maxPages) {
                $reviewsData = $this->amazonApi->fetchReviews($product['asin'], $currentPage);
                
                if (empty($reviewsData)) {
                    break;
                }

                foreach ($reviewsData as $reviewData) {
                    $review = $this->processReview($productId, $reviewData);
                    if ($review) {
                        $totalReviews++;
                    }
                }

                $this->taskModel->update($task['id'], [
                    'scraped_reviews' => $totalReviews,
                    'current_page' => $currentPage,
                ]);

                if (count($reviewsData) < 10) {
                    break;
                }

                $currentPage++;
                $this->randomDelay();
            }

            $this->productModel->update($productId, [
                'status' => Product::STATUS_COMPLETED,
                'last_scraped_at' => date('Y-m-d H:i:s'),
            ]);

            $this->taskModel->update($task['id'], [
                'status' => ScrapingTask::STATUS_COMPLETED,
                'total_reviews' => $totalReviews,
                'scraped_reviews' => $totalReviews,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            return [
                'success' => true,
                'product_id' => $productId,
                'task_id' => $task['id'],
                'total_reviews' => $totalReviews,
                'pages_scraped' => $currentPage - $startPage,
            ];

        } catch (Exception $e) {
            $this->productModel->update($productId, ['status' => Product::STATUS_FAILED]);
            $this->taskModel->update($task['id'], [
                'status' => ScrapingTask::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            return [
                'success' => false,
                'product_id' => $productId,
                'task_id' => $task['id'],
                'error' => $e->getMessage(),
            ];
        }
    }

    private function processReview(int $productId, array $reviewData): ?array
    {
        $existingReview = $this->reviewModel->findByReviewUrl($reviewData['review_url'] ?? '');
        if ($existingReview) {
            return $existingReview;
        }

        $review = $this->reviewModel->create([
            'product_id' => $productId,
            'reviewer_name' => $reviewData['reviewer_name'] ?? '',
            'reviewer_id' => $reviewData['reviewer_id'] ?? '',
            'rating' => $reviewData['rating'] ?? 0,
            'title' => $reviewData['title'] ?? '',
            'body' => $reviewData['body'] ?? '',
            'review_date' => $reviewData['review_date'] ?? null,
            'review_url' => $reviewData['review_url'] ?? '',
            'helpful_votes' => $reviewData['helpful_votes'] ?? 0,
            'verified_purchase' => $reviewData['verified_purchase'] ?? false,
            'variant_info' => $reviewData['variant_info'] ?? '',
        ]);

        if (!$review) {
            return null;
        }

        $imageCount = 0;
        $videoCount = 0;

        if ($this->config['images_enabled'] && !empty($reviewData['images'])) {
            $images = array_slice($reviewData['images'], 0, $this->config['max_images_per_review']);
            foreach ($images as $imageUrl) {
                $this->downloadService->downloadImage($review['id'], $imageUrl);
                $imageCount++;
            }
        }

        if ($this->config['videos_enabled'] && !empty($reviewData['video'])) {
            $this->downloadService->downloadVideo($review['id'], $reviewData['video']);
            $videoCount++;
        }

        $this->reviewModel->update($review['id'], [
            'image_count' => $imageCount,
            'video_count' => $videoCount,
        ]);

        return $review;
    }

    public function scrapeMultipleProducts(array $productIds, array $options = []): array
    {
        $results = [];
        $concurrentLimit = $this->config['concurrent_tasks'];

        $chunks = array_chunk($productIds, $concurrentLimit);
        
        foreach ($chunks as $chunk) {
            foreach ($chunk as $productId) {
                $results[] = $this->scrapeProduct($productId, $options);
            }
        }

        return $results;
    }

    public function scrapeImagesOnly(int $productId): array
    {
        $reviews = $this->reviewModel->findByProductId($productId, [
            'has_images' => false,
            'limit' => 100,
        ]);

        $downloaded = 0;
        foreach ($reviews as $review) {
            $images = $this->amazonApi->fetchReviewImages($review['review_url']);
            foreach ($images as $imageUrl) {
                if ($this->downloadService->downloadImage($review['id'], $imageUrl)) {
                    $downloaded++;
                }
            }
        }

        return [
            'success' => true,
            'product_id' => $productId,
            'downloaded_images' => $downloaded,
        ];
    }

    public function getScrapingProgress(int $taskId): array
    {
        $task = $this->taskModel->findById($taskId);
        if (!$task) {
            return ['error' => 'Task not found'];
        }

        $progress = 0;
        if ($task['total_pages'] > 0) {
            $progress = round(($task['current_page'] / $task['total_pages']) * 100, 2);
        }

        return [
            'task_id' => $taskId,
            'status' => $task['status'],
            'progress' => $progress,
            'current_page' => $task['current_page'],
            'total_pages' => $task['total_pages'],
            'scraped_reviews' => $task['scraped_reviews'],
            'total_reviews' => $task['total_reviews'],
            'error_message' => $task['error_message'],
            'started_at' => $task['started_at'],
            'completed_at' => $task['completed_at'],
        ];
    }

    public function pauseTask(int $taskId): bool
    {
        return $this->taskModel->update($taskId, [
            'status' => ScrapingTask::STATUS_PAUSED,
        ]) !== false;
    }

    public function resumeTask(int $taskId): bool
    {
        $task = $this->taskModel->findById($taskId);
        if (!$task) {
            return false;
        }

        $this->taskModel->update($taskId, [
            'status' => ScrapingTask::STATUS_RUNNING,
        ]);

        return $this->scrapeProduct($task['product_id'], [
            'start_page' => $task['current_page'] + 1,
            'max_pages' => $task['total_pages'],
            'task_type' => $task['task_type'],
        ]);
    }

    public function retryFailedTask(int $taskId): array
    {
        $task = $this->taskModel->findById($taskId);
        if (!$task) {
            return ['success' => false, 'error' => 'Task not found'];
        }

        $this->taskModel->update($taskId, [
            'status' => ScrapingTask::STATUS_RUNNING,
            'error_message' => null,
            'retry_count' => $task['retry_count'] + 1,
        ]);

        return $this->scrapeProduct($task['product_id'], [
            'start_page' => $task['current_page'] + 1,
            'max_pages' => $task['total_pages'],
            'task_type' => $task['task_type'],
        ]);
    }

    private function randomDelay(): void
    {
        $delay = rand($this->config['delay_min'], $this->config['delay_max']);
        sleep($delay);
    }

    public function getStatistics(int $productId): array
    {
        return $this->reviewModel->getStatisticsByProduct($productId);
    }

    public function getGlobalStatistics(): array
    {
        $products = $this->productModel->findAll();
        $totalProducts = count($products);
        $totalReviews = 0;
        $ratingDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $verifiedCount = 0;
        $totalImages = 0;
        $totalVideos = 0;

        foreach ($products as $product) {
            $stats = $this->getStatistics($product['id']);
            $totalReviews += $stats['total_reviews'];
            foreach ([1, 2, 3, 4, 5] as $rating) {
                $ratingDistribution[$rating] += $stats["rating_{$rating}_count"];
            }
            $verifiedCount += $stats['verified_count'];
            $totalImages += $stats['total_images'];
            $totalVideos += $stats['total_videos'];
        }

        return [
            'total_products' => $totalProducts,
            'total_reviews' => $totalReviews,
            'rating_distribution' => $ratingDistribution,
            'verified_count' => $verifiedCount,
            'total_images' => $totalImages,
            'total_videos' => $totalVideos,
            'average_rating' => $totalReviews > 0 ? round(array_sum(array_map(fn($k, $v) => $k * $v, array_keys($ratingDistribution), $ratingDistribution)) / $totalReviews, 2) : 0,
        ];
    }
}
