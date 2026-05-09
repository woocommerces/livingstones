<?php

namespace App\Controllers;

use App\Services\ScraperService;
use App\Services\AmazonAPI;
use App\Models\Product;
use App\Models\Review;
use App\Models\ScrapingTask;
use App\Models\OperationLog;
use App\Utils\Router;
use Exception;

class ProductController
{
    private $productModel;
    private $reviewModel;
    private $scraperService;
    private $amazonApi;
    private $logModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->reviewModel = new Review();
        $this->scraperService = new ScraperService();
        $this->amazonApi = new AmazonAPI();
        $this->logModel = new OperationLog();
    }

    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';

        $filters = [];
        if ($search) {
            $filters['search'] = $search;
        }
        if ($status) {
            $filters['status'] = $status;
        }

        $products = $this->productModel->paginate($page, $perPage, $filters);
        $totalPages = ceil($products['total'] / $perPage);

        $stats = $this->scraperService->getGlobalStatistics();

        $router = new Router();
        $baseUrl = $router->generateUrl('/products');

        include __DIR__ . '/../../views/products/index.php';
    }

    public function add(): void
    {
        include __DIR__ . '/../../views/products/add.php';
    }

    public function store(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $asinInput = trim($input['asin'] ?? '');

        if (empty($asinInput)) {
            echo json_encode(['success' => false, 'error' => '请输入ASIN或商品链接']);
            return;
        }

        $asins = array_filter(array_map('trim', explode("\n", $asinInput)));
        $results = [];
        $errors = [];

        foreach ($asins as $asinInput) {
            $asin = $this->amazonApi->validateAsin($asinInput);
            
            if (!$asin) {
                $errors[] = "无效的ASIN或链接: {$asinInput}";
                continue;
            }

            $existingProduct = $this->productModel->findByAsin($asin);
            if ($existingProduct) {
                $errors[] = "ASIN {$asin} 已存在";
                continue;
            }

            try {
                $productInfo = $this->amazonApi->fetchProductInfo($asin);
                
                $productData = [
                    'asin' => $asin,
                    'title' => $productInfo['title'] ?? '未知商品',
                    'url' => $productInfo['url'] ?? "https://www.amazon.com/dp/{$asin}",
                    'image_url' => $productInfo['image_url'] ?? null,
                    'current_price' => $productInfo['current_price'] ?? null,
                    'rating' => $productInfo['rating'] ?? null,
                    'review_count' => $productInfo['review_count'] ?? 0,
                    'status' => 'pending',
                ];

                $product = $this->productModel->create($productData);

                if ($product) {
                    $results[] = [
                        'success' => true,
                        'asin' => $asin,
                        'product_id' => $product['id'],
                        'title' => $product['title'],
                    ];

                    $this->logModel->info(
                        OperationLog::TYPE_ADD_PRODUCT,
                        "添加商品: {$asin}",
                        'product',
                        $product['id']
                    );
                } else {
                    $errors[] = "无法创建商品: {$asin}";
                }
            } catch (Exception $e) {
                $errors[] = "获取商品信息失败 {$asin}: " . $e->getMessage();
            }

            sleep(1);
        }

        echo json_encode([
            'success' => empty($errors),
            'added' => $results,
            'errors' => $errors,
        ]);
    }

    public function scrape(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $productId = (int)($input['product_id'] ?? 0);
        $maxPages = (int)($input['max_pages'] ?? 10);
        $taskType = $input['task_type'] ?? 'full';

        if (!$productId) {
            echo json_encode(['success' => false, 'error' => '无效的商品ID']);
            return;
        }

        $product = $this->productModel->findById($productId);
        if (!$product) {
            echo json_encode(['success' => false, 'error' => '商品不存在']);
            return;
        }

        if ($product['status'] === 'scraping') {
            echo json_encode(['success' => false, 'error' => '该商品正在采集中']);
            return;
        }

        $startTime = microtime(true);

        try {
            $result = $this->scraperService->scrapeProduct($productId, [
                'max_pages' => $maxPages,
                'task_type' => $taskType,
            ]);

            $executionTime = (microtime(true) - $startTime) * 1000;

            $this->logModel->log(
                $result['success'] ? OperationLog::LEVEL_INFO : OperationLog::LEVEL_ERROR,
                OperationLog::TYPE_SCRAPE_PRODUCT,
                "采集商品评论: {$product['asin']}",
                'product',
                $productId,
                ['max_pages' => $maxPages, 'task_type' => $taskType],
                $result,
                $executionTime,
                $result['error'] ?? null
            );

            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function delete(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $productId = (int)($input['product_id'] ?? 0);

        if (!$productId) {
            echo json_encode(['success' => false, 'error' => '无效的商品ID']);
            return;
        }

        $product = $this->productModel->findById($productId);
        if (!$product) {
            echo json_encode(['success' => false, 'error' => '商品不存在']);
            return;
        }

        $deleted = $this->productModel->delete($productId);

        if ($deleted) {
            $this->logModel->info(
                OperationLog::TYPE_DELETE_PRODUCT,
                "删除商品: {$product['asin']}",
                'product',
                $productId
            );

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => '删除失败']);
        }
    }

    public function view(): void
    {
        global $matches;
        $productId = (int)($matches[1] ?? 0);

        if (!$productId) {
            header('Location: /products');
            return;
        }

        $product = $this->productModel->findById($productId);
        if (!$product) {
            header('Location: /products');
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $reviews = $this->reviewModel->findByProductId($productId, [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $reviewStats = $this->reviewModel->getStatisticsByProduct($productId);
        $totalPages = ceil($reviewStats['total_reviews'] / $perPage);

        $router = new Router();
        $baseUrl = $router->generateUrl("/products/view/{$productId}");

        include __DIR__ . '/../../views/products/view.php';
    }

    public function apiStore(): void
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $asin = trim($input['asin'] ?? '');

        if (empty($asin)) {
            http_response_code(400);
            echo json_encode(['error' => 'ASIN is required']);
            return;
        }

        $asin = $this->amazonApi->validateAsin($asin);
        if (!$asin) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid ASIN or URL']);
            return;
        }

        $existingProduct = $this->productModel->findByAsin($asin);
        if ($existingProduct) {
            http_response_code(409);
            echo json_encode(['error' => 'Product already exists', 'product' => $existingProduct]);
            return;
        }

        try {
            $productInfo = $this->amazonApi->fetchProductInfo($asin);
            
            $productData = [
                'asin' => $asin,
                'title' => $productInfo['title'] ?? 'Unknown Product',
                'url' => $productInfo['url'] ?? "https://www.amazon.com/dp/{$asin}",
                'image_url' => $productInfo['image_url'] ?? null,
                'current_price' => $productInfo['current_price'] ?? null,
                'rating' => $productInfo['rating'] ?? null,
                'review_count' => $productInfo['review_count'] ?? 0,
                'status' => 'pending',
            ];

            $product = $this->productModel->create($productData);

            if ($product) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'product' => $product,
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create product']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
