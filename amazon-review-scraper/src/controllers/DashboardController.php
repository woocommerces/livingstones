<?php

namespace App\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\ScrapingTask;
use App\Models\OperationLog;
use App\Models\ReviewImage;
use App\Services\ScraperService;
use App\Utils\Router;
use Exception;

class DashboardController
{
    private Product $productModel;
    private Review $reviewModel;
    private ScrapingTask $taskModel;
    private OperationLog $logModel;
    private ReviewImage $imageModel;
    private ScraperService $scraperService;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->reviewModel = new Review();
        $this->taskModel = new ScrapingTask();
        $this->logModel = new OperationLog();
        $this->imageModel = new ReviewImage();
        $this->scraperService = new ScraperService();
    }

    public function index(): void
    {
        $stats = $this->getDashboardStats();
        $recentProducts = $this->productModel->findAll([], ['created_at' => 'DESC'], 5);
        $recentTasks = $this->taskModel->getRecentTasks(10);
        $recentLogs = $this->logModel->getRecentLogs(10);

        include __DIR__ . '/../../views/dashboard/index.php';
    }

    public function apiStats(): void
    {
        header('Content-Type: application/json');

        try {
            $stats = $this->getDashboardStats();
            echo json_encode([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiRecentProducts(): void
    {
        header('Content-Type: application/json');

        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $limit = max(1, min(50, $limit));

            $products = $this->productModel->findAll([], ['created_at' => 'DESC'], $limit);

            $data = array_map(function ($product) {
                $stats = $this->reviewModel->getStatisticsByProduct($product['id']);
                return [
                    'id' => $product['id'],
                    'asin' => $product['asin'],
                    'title' => $product['title'],
                    'status' => $product['status'],
                    'review_count' => $stats['total_reviews'] ?? 0,
                    'rating' => $product['rating'],
                    'created_at' => $product['created_at'],
                ];
            }, $products);

            echo json_encode([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiRecentTasks(): void
    {
        header('Content-Type: application/json');

        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $limit = max(1, min(50, $limit));

            $tasks = $this->taskModel->getRecentTasks($limit);

            $data = array_map(function ($task) {
                $product = $this->productModel->findById($task['product_id']);
                return [
                    'id' => $task['id'],
                    'product_id' => $task['product_id'],
                    'product_asin' => $product['asin'] ?? 'N/A',
                    'product_title' => $product['title'] ?? 'N/A',
                    'task_type' => $task['task_type'],
                    'status' => $task['status'],
                    'progress' => $task['total_pages'] > 0
                        ? round(($task['current_page'] / $task['total_pages']) * 100, 2)
                        : 0,
                    'scraped_reviews' => $task['scraped_reviews'],
                    'error_message' => $task['error_message'],
                    'created_at' => $task['created_at'],
                    'completed_at' => $task['completed_at'],
                ];
            }, $tasks);

            echo json_encode([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiChartData(): void
    {
        header('Content-Type: application/json');

        try {
            $type = $_GET['type'] ?? 'overview';

            $data = match ($type) {
                'rating_distribution' => $this->getRatingDistributionData(),
                'task_status' => $this->getTaskStatusData(),
                'product_status' => $this->getProductStatusData(),
                'scraping_activity' => $this->getScrapingActivityData(),
                default => $this->getOverviewChartData(),
            };

            echo json_encode([
                'success' => true,
                'type' => $type,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiRecentLogs(): void
    {
        header('Content-Type: application/json');

        try {
            $limit = (int)($_GET['limit'] ?? 20);
            $limit = max(1, min(100, $limit));

            $logs = $this->logModel->getRecentLogs($limit);

            $data = array_map(function ($log) {
                return [
                    'id' => $log['id'],
                    'level' => $log['log_level'],
                    'type' => $log['operation_type'],
                    'description' => $log['operation_desc'],
                    'target_type' => $log['target_type'],
                    'target_id' => $log['target_id'],
                    'execution_time' => $log['execution_time'],
                    'error_message' => $log['error_message'],
                    'created_at' => $log['created_at'],
                ];
            }, $logs);

            echo json_encode([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getDashboardStats(): array
    {
        $productStats = $this->productModel->getStatistics();
        $taskStats = $this->taskModel->getStatistics();
        $logStats = $this->logModel->getStatistics();
        $imageStats = $this->imageModel->getStorageStats();

        $totalReviews = 0;
        $products = $this->productModel->findAll([], ['id' => 'ASC'], 1000);
        foreach ($products as $product) {
            $reviewStats = $this->reviewModel->getStatisticsByProduct($product['id']);
            $totalReviews += (int)($reviewStats['total_reviews'] ?? 0);
        }

        return [
            'products' => [
                'total' => (int)($productStats['total'] ?? 0),
                'pending' => (int)($productStats['pending'] ?? 0),
                'scraping' => (int)($productStats['scraping'] ?? 0),
                'completed' => (int)($productStats['completed'] ?? 0),
                'failed' => (int)($productStats['failed'] ?? 0),
            ],
            'reviews' => [
                'total' => $totalReviews,
            ],
            'tasks' => [
                'total' => (int)($taskStats['total'] ?? 0),
                'pending' => (int)($taskStats['pending'] ?? 0),
                'running' => (int)($taskStats['running'] ?? 0),
                'completed' => (int)($taskStats['completed'] ?? 0),
                'failed' => (int)($taskStats['failed'] ?? 0),
                'paused' => (int)($taskStats['paused'] ?? 0),
                'total_scraped' => (int)($taskStats['total_reviews_scraped'] ?? 0),
            ],
            'logs' => [
                'total' => (int)($logStats['total'] ?? 0),
                'info' => (int)($logStats['info'] ?? 0),
                'warning' => (int)($logStats['warning'] ?? 0),
                'error' => (int)($logStats['error'] ?? 0),
            ],
            'storage' => [
                'total_images' => (int)($imageStats['images'] ?? 0),
                'total_videos' => (int)($imageStats['videos'] ?? 0),
                'total_size' => (int)($imageStats['total_size'] ?? 0),
            ],
        ];
    }

    private function getRatingDistributionData(): array
    {
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        $products = $this->productModel->findAll([], ['id' => 'ASC'], 1000);
        foreach ($products as $product) {
            $stats = $this->reviewModel->getStatisticsByProduct($product['id']);
            for ($rating = 1; $rating <= 5; $rating++) {
                $key = $rating . '_star';
                $distribution[$rating] += (int)($stats[$key] ?? 0);
            }
        }

        return [
            'labels' => ['1星', '2星', '3星', '4星', '5星'],
            'values' => array_values($distribution),
        ];
    }

    private function getTaskStatusData(): array
    {
        $stats = $this->taskModel->getStatistics();

        return [
            'labels' => ['待处理', '进行中', '已完成', '失败', '已暂停'],
            'values' => [
                (int)($stats['pending'] ?? 0),
                (int)($stats['running'] ?? 0),
                (int)($stats['completed'] ?? 0),
                (int)($stats['failed'] ?? 0),
                (int)($stats['paused'] ?? 0),
            ],
        ];
    }

    private function getProductStatusData(): array
    {
        $stats = $this->productModel->getStatistics();

        return [
            'labels' => ['待采集', '采集中', '已完成', '失败'],
            'values' => [
                (int)($stats['pending'] ?? 0),
                (int)($stats['scraping'] ?? 0),
                (int)($stats['completed'] ?? 0),
                (int)($stats['failed'] ?? 0),
            ],
        ];
    }

    private function getScrapingActivityData(): array
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('m/d', strtotime("-{$i} days"));

            $startDate = $date . ' 00:00:00';
            $endDate = $date . ' 23:59:59';

            $sql = "SELECT COUNT(*) as count FROM scraping_tasks
                    WHERE created_at BETWEEN ? AND ?";
            $result = $this->taskModel->db->fetchOne($sql, [$startDate, $endDate]);
            $values[] = (int)($result['count'] ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    private function getOverviewChartData(): array
    {
        return [
            'rating_distribution' => $this->getRatingDistributionData(),
            'task_status' => $this->getTaskStatusData(),
            'product_status' => $this->getProductStatusData(),
            'scraping_activity' => $this->getScrapingActivityData(),
        ];
    }
}
