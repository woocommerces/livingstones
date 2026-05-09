<?php

namespace App\Controllers;

use App\Models\ScrapingTask;
use App\Models\Product;
use App\Models\OperationLog;
use App\Services\ScraperService;
use App\Utils\Router;
use Exception;

class TaskController
{
    private ScrapingTask $taskModel;
    private Product $productModel;
    private OperationLog $logModel;
    private ScraperService $scraperService;

    public function __construct()
    {
        $this->taskModel = new ScrapingTask();
        $this->productModel = new Product();
        $this->logModel = new OperationLog();
        $this->scraperService = new ScraperService();
    }

    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $status = $_GET['status'] ?? '';
        $productId = (int)($_GET['product_id'] ?? 0);

        $filters = [];
        if (!empty($status)) {
            $filters['status'] = $status;
        }
        if ($productId > 0) {
            $filters['product_id'] = $productId;
        }

        $result = $this->taskModel->paginate($page, $perPage, $filters, ['created_at' => 'DESC']);

        $tasks = array_map(function ($task) {
            $product = $this->productModel->findById($task['product_id']);
            $task['product_asin'] = $product['asin'] ?? 'N/A';
            $task['product_title'] = $product['title'] ?? 'N/A';
            $task['progress'] = $task['total_pages'] > 0
                ? round(($task['current_page'] / $task['total_pages']) * 100, 2)
                : 0;
            return $task;
        }, $result['data']);

        $stats = $this->taskModel->getStatistics();
        $products = $this->productModel->findAll([], ['created_at' => 'DESC'], 100);

        $router = new Router();
        $baseUrl = $router->generateUrl('/tasks');

        include __DIR__ . '/../../views/tasks/index.php';
    }

    public function apiList(): void
    {
        header('Content-Type: application/json');

        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
            $status = $_GET['status'] ?? '';
            $productId = (int)($_GET['product_id'] ?? 0);
            $includeCompleted = isset($_GET['include_completed']) && filter_var($_GET['include_completed'], FILTER_VALIDATE_BOOLEAN);

            $filters = [];
            if (!empty($status)) {
                $filters['status'] = $status;
            }
            if ($productId > 0) {
                $filters['product_id'] = $productId;
            }
            if (!$includeCompleted) {
                $filters['status'] = [
                    ScrapingTask::STATUS_PENDING,
                    ScrapingTask::STATUS_RUNNING,
                    ScrapingTask::STATUS_PAUSED,
                    ScrapingTask::STATUS_FAILED,
                ];
            }

            $result = $this->taskModel->paginate($page, $perPage, $filters, ['priority' => 'DESC', 'created_at' => 'DESC']);

            $data = array_map(function ($task) {
                $product = $this->productModel->findById($task['product_id']);
                return [
                    'id' => $task['id'],
                    'product_id' => $task['product_id'],
                    'product_asin' => $product['asin'] ?? null,
                    'product_title' => $product['title'] ?? null,
                    'task_type' => $task['task_type'],
                    'status' => $task['status'],
                    'priority' => (int)$task['priority'],
                    'total_pages' => (int)$task['total_pages'],
                    'current_page' => (int)$task['current_page'],
                    'scraped_reviews' => (int)$task['scraped_reviews'],
                    'total_reviews' => (int)$task['total_reviews'],
                    'progress' => $task['total_pages'] > 0
                        ? round(($task['current_page'] / $task['total_pages']) * 100, 2)
                        : 0,
                    'error_message' => $task['error_message'],
                    'retry_count' => (int)$task['retry_count'],
                    'started_at' => $task['started_at'],
                    'completed_at' => $task['completed_at'],
                    'created_at' => $task['created_at'],
                ];
            }, $result['data']);

            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => $result['pagination'],
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiShow(): void
    {
        header('Content-Type: application/json');

        global $matches;
        $taskId = (int)($matches[1] ?? 0);

        if (!$taskId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的任务ID']);
            return;
        }

        try {
            $task = $this->taskModel->findById($taskId);

            if (!$task) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '任务不存在']);
                return;
            }

            $product = $this->productModel->findById($task['product_id']);
            $progress = $this->scraperService->getScrapingProgress($taskId);

            $response = [
                'id' => $task['id'],
                'product_id' => $task['product_id'],
                'product_asin' => $product['asin'] ?? null,
                'product_title' => $product['title'] ?? null,
                'task_type' => $task['task_type'],
                'status' => $task['status'],
                'priority' => (int)$task['priority'],
                'total_pages' => (int)$task['total_pages'],
                'current_page' => (int)$task['current_page'],
                'scraped_reviews' => (int)$task['scraped_reviews'],
                'total_reviews' => (int)$task['total_reviews'],
                'progress' => $progress['progress'] ?? 0,
                'error_message' => $task['error_message'],
                'retry_count' => (int)$task['retry_count'],
                'started_at' => $task['started_at'],
                'completed_at' => $task['completed_at'],
                'created_at' => $task['created_at'],
            ];

            echo json_encode([
                'success' => true,
                'data' => $response,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiProgress(): void
    {
        header('Content-Type: application/json');

        global $matches;
        $taskId = (int)($matches[1] ?? 0);

        if (!$taskId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的任务ID']);
            return;
        }

        try {
            $progress = $this->scraperService->getScrapingProgress($taskId);

            if (isset($progress['error']) && $progress['error'] === 'Task not found') {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '任务不存在']);
                return;
            }

            echo json_encode([
                'success' => true,
                'data' => $progress,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiStatus(): void
    {
        header('Content-Type: application/json');

        try {
            $stats = $this->taskModel->getStatistics();
            $runningTasks = $this->taskModel->findRunningTasks();
            $pendingTasks = $this->taskModel->findPendingTasks(5);

            $data = [
                'statistics' => [
                    'total' => (int)($stats['total'] ?? 0),
                    'pending' => (int)($stats['pending'] ?? 0),
                    'running' => (int)($stats['running'] ?? 0),
                    'completed' => (int)($stats['completed'] ?? 0),
                    'failed' => (int)($stats['failed'] ?? 0),
                    'paused' => (int)($stats['paused'] ?? 0),
                    'total_reviews_scraped' => (int)($stats['total_reviews_scraped'] ?? 0),
                ],
                'running_tasks' => array_map(function ($task) {
                    $product = $this->productModel->findById($task['product_id']);
                    $progress = $this->scraperService->getScrapingProgress($task['id']);
                    return [
                        'id' => $task['id'],
                        'product_asin' => $product['asin'] ?? null,
                        'progress' => $progress['progress'] ?? 0,
                        'current_page' => $progress['current_page'] ?? 0,
                        'total_pages' => $progress['total_pages'] ?? 0,
                        'scraped_reviews' => $progress['scraped_reviews'] ?? 0,
                    ];
                }, $runningTasks),
                'pending_tasks' => array_map(function ($task) {
                    $product = $this->productModel->findById($task['product_id']);
                    return [
                        'id' => $task['id'],
                        'product_asin' => $product['asin'] ?? null,
                        'priority' => (int)$task['priority'],
                    ];
                }, $pendingTasks),
            ];

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

    public function apiPause(): void
    {
        header('Content-Type: application/json');

        global $matches;
        $taskId = (int)($matches[1] ?? 0);

        if (!$taskId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的任务ID']);
            return;
        }

        try {
            $task = $this->taskModel->findById($taskId);

            if (!$task) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '任务不存在']);
                return;
            }

            if ($task['status'] !== ScrapingTask::STATUS_RUNNING) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => '只有正在运行的任务才能暂停',
                    'current_status' => $task['status'],
                ]);
                return;
            }

            $success = $this->scraperService->pauseTask($taskId);

            if ($success) {
                $this->logModel->info(
                    OperationLog::TYPE_SCRAPE_PRODUCT,
                    "暂停任务: {$taskId}",
                    'task',
                    $taskId
                );

                echo json_encode([
                    'success' => true,
                    'message' => '任务已暂停',
                    'task_id' => $taskId,
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => '暂停任务失败']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiResume(): void
    {
        header('Content-Type: application/json');

        global $matches;
        $taskId = (int)($matches[1] ?? 0);

        if (!$taskId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的任务ID']);
            return;
        }

        try {
            $task = $this->taskModel->findById($taskId);

            if (!$task) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '任务不存在']);
                return;
            }

            if (!in_array($task['status'], [ScrapingTask::STATUS_PAUSED, ScrapingTask::STATUS_PENDING])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => '只能恢复已暂停或待处理的任务',
                    'current_status' => $task['status'],
                ]);
                return;
            }

            $result = $this->scraperService->resumeTask($taskId);

            $this->logModel->info(
                OperationLog::TYPE_SCRAPE_PRODUCT,
                "恢复任务: {$taskId}",
                'task',
                $taskId,
                ['result' => $result]
            );

            echo json_encode([
                'success' => true,
                'message' => '任务已恢复',
                'task_id' => $taskId,
                'result' => $result,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiRetry(): void
    {
        header('Content-Type: application/json');

        global $matches;
        $taskId = (int)($matches[1] ?? 0);

        if (!$taskId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的任务ID']);
            return;
        }

        try {
            $task = $this->taskModel->findById($taskId);

            if (!$task) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '任务不存在']);
                return;
            }

            if ($task['status'] !== ScrapingTask::STATUS_FAILED) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => '只能重试失败的任务',
                    'current_status' => $task['status'],
                ]);
                return;
            }

            $result = $this->scraperService->retryFailedTask($taskId);

            $this->logModel->info(
                OperationLog::TYPE_SCRAPE_PRODUCT,
                "重试任务: {$taskId}",
                'task',
                $taskId,
                ['result' => $result]
            );

            echo json_encode([
                'success' => true,
                'message' => '任务已重新开始',
                'task_id' => $taskId,
                'result' => $result,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiCancel(): void
    {
        header('Content-Type: application/json');

        global $matches;
        $taskId = (int)($matches[1] ?? 0);

        if (!$taskId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的任务ID']);
            return;
        }

        try {
            $task = $this->taskModel->findById($taskId);

            if (!$task) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '任务不存在']);
                return;
            }

            if (in_array($task['status'], [ScrapingTask::STATUS_COMPLETED])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => '无法取消已完成的任务',
                    'current_status' => $task['status'],
                ]);
                return;
            }

            $this->taskModel->update($taskId, [
                'status' => ScrapingTask::STATUS_FAILED,
                'error_message' => '用户取消任务',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            if ($task['product_id']) {
                $this->productModel->updateStatus($task['product_id'], 'completed');
            }

            $this->logModel->info(
                OperationLog::TYPE_SCRAPE_PRODUCT,
                "取消任务: {$taskId}",
                'task',
                $taskId
            );

            echo json_encode([
                'success' => true,
                'message' => '任务已取消',
                'task_id' => $taskId,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiCancelBulk(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $taskIds = $input['task_ids'] ?? [];

        if (empty($taskIds) || !is_array($taskIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '请提供有效的任务ID列表']);
            return;
        }

        try {
            $cancelledCount = 0;
            foreach ($taskIds as $taskId) {
                $id = (int)$taskId;
                if ($id > 0) {
                    $task = $this->taskModel->findById($id);
                    if ($task && !in_array($task['status'], [ScrapingTask::STATUS_COMPLETED])) {
                        $this->taskModel->update($id, [
                            'status' => ScrapingTask::STATUS_FAILED,
                            'error_message' => '用户批量取消',
                            'completed_at' => date('Y-m-d H:i:s'),
                        ]);

                        if ($task['product_id']) {
                            $this->productModel->updateStatus($task['product_id'], 'completed');
                        }

                        $cancelledCount++;
                    }
                }
            }

            $this->logModel->info(
                OperationLog::TYPE_SCRAPE_PRODUCT,
                "批量取消任务: {$cancelledCount}个",
                'task',
                0,
                ['task_ids' => $taskIds]
            );

            echo json_encode([
                'success' => true,
                'cancelled_count' => $cancelledCount,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiRetryBulk(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $taskIds = $input['task_ids'] ?? [];

        if (empty($taskIds) || !is_array($taskIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '请提供有效的任务ID列表']);
            return;
        }

        try {
            $retryCount = 0;
            $errors = [];

            foreach ($taskIds as $taskId) {
                $id = (int)$taskId;
                if ($id > 0) {
                    $task = $this->taskModel->findById($id);
                    if ($task && $task['status'] === ScrapingTask::STATUS_FAILED) {
                        try {
                            $result = $this->scraperService->retryFailedTask($id);
                            if ($result['success']) {
                                $retryCount++;
                            } else {
                                $errors[] = "任务 {$id} 重试失败: " . ($result['error'] ?? '未知错误');
                            }
                        } catch (Exception $e) {
                            $errors[] = "任务 {$id} 重试异常: " . $e->getMessage();
                        }
                    }
                }
            }

            $this->logModel->info(
                OperationLog::TYPE_SCRAPE_PRODUCT,
                "批量重试任务: {$retryCount}个",
                'task',
                0,
                ['task_ids' => $taskIds]
            );

            echo json_encode([
                'success' => empty($errors),
                'retry_count' => $retryCount,
                'errors' => $errors,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiDelete(): void
    {
        header('Content-Type: application/json');

        global $matches;
        $taskId = (int)($matches[1] ?? 0);

        if (!$taskId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的任务ID']);
            return;
        }

        try {
            $task = $this->taskModel->findById($taskId);

            if (!$task) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '任务不存在']);
                return;
            }

            $deleted = $this->taskModel->delete($taskId);

            if ($deleted) {
                $this->logModel->info(
                    OperationLog::TYPE_SCRAPE_PRODUCT,
                    "删除任务: {$taskId}",
                    'task',
                    $taskId
                );

                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => '删除失败']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiDeleteCompleted(): void
    {
        header('Content-Type: application/json');

        try {
            $sql = "DELETE FROM {$this->taskModel->table} WHERE status = ?";
            $result = $this->taskModel->db->query($sql, [ScrapingTask::STATUS_COMPLETED]);
            $deletedCount = $result ? $result->rowCount() : 0;

            $this->logModel->info(
                OperationLog::TYPE_SCRAPE_PRODUCT,
                "删除已完成任务: {$deletedCount}个",
                'task',
                0
            );

            echo json_encode([
                'success' => true,
                'deleted_count' => $deletedCount,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiRunning(): void
    {
        header('Content-Type: application/json');

        try {
            $tasks = $this->taskModel->findRunningTasks();

            $data = array_map(function ($task) {
                $product = $this->productModel->findById($task['product_id']);
                $progress = $this->scraperService->getScrapingProgress($task['id']);
                return [
                    'id' => $task['id'],
                    'product_id' => $task['product_id'],
                    'product_asin' => $product['asin'] ?? null,
                    'product_title' => $product['title'] ?? null,
                    'task_type' => $task['task_type'],
                    'progress' => $progress['progress'] ?? 0,
                    'current_page' => $progress['current_page'] ?? 0,
                    'total_pages' => $progress['total_pages'] ?? 0,
                    'scraped_reviews' => $progress['scraped_reviews'] ?? 0,
                    'started_at' => $task['started_at'],
                ];
            }, $tasks);

            echo json_encode([
                'success' => true,
                'data' => $data,
                'count' => count($data),
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiPending(): void
    {
        header('Content-Type: application/json');

        try {
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
            $tasks = $this->taskModel->findPendingTasks($limit);

            $data = array_map(function ($task) {
                $product = $this->productModel->findById($task['product_id']);
                return [
                    'id' => $task['id'],
                    'product_id' => $task['product_id'],
                    'product_asin' => $product['asin'] ?? null,
                    'product_title' => $product['title'] ?? null,
                    'task_type' => $task['task_type'],
                    'priority' => (int)$task['priority'],
                    'total_pages' => (int)$task['total_pages'],
                    'created_at' => $task['created_at'],
                ];
            }, $tasks);

            echo json_encode([
                'success' => true,
                'data' => $data,
                'count' => count($data),
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiClearFailed(): void
    {
        header('Content-Type: application/json');

        try {
            $sql = "DELETE FROM {$this->taskModel->table} WHERE status = ?";
            $result = $this->taskModel->db->query($sql, [ScrapingTask::STATUS_FAILED]);
            $deletedCount = $result ? $result->rowCount() : 0;

            $this->logModel->info(
                OperationLog::TYPE_SCRAPE_PRODUCT,
                "清空失败任务: {$deletedCount}个",
                'task',
                0
            );

            echo json_encode([
                'success' => true,
                'deleted_count' => $deletedCount,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
