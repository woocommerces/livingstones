<?php

namespace App\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\ReviewImage;
use App\Models\OperationLog;
use App\Utils\Router;
use Exception;

class ExportController
{
    private Review $reviewModel;
    private Product $productModel;
    private ReviewImage $imageModel;
    private OperationLog $logModel;

    public function __construct()
    {
        $this->reviewModel = new Review();
        $this->productModel = new Product();
        $this->imageModel = new ReviewImage();
        $this->logModel = new OperationLog();
    }

    public function index(): void
    {
        $products = $this->productModel->findAll([], ['created_at' => 'DESC'], 100);

        include __DIR__ . '/../../views/export/index.php';
    }

    public function apiExportReviews(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_GET;
        $format = strtolower(trim($input['format'] ?? 'json'));
        $productId = (int)($input['product_id'] ?? 0);
        $rating = (int)($input['rating'] ?? 0);
        $verified = $input['verified'] ?? null;
        $includeImages = isset($input['include_images']) && filter_var($input['include_images'], FILTER_VALIDATE_BOOLEAN);

        $allowedFormats = ['json', 'csv', 'excel'];
        if (!in_array($format, $allowedFormats)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '不支持的导出格式，允许的格式: ' . implode(', ', $allowedFormats),
            ]);
            return;
        }

        try {
            $filters = [];
            if ($productId > 0) {
                $filters['product_id'] = $productId;
            }
            if ($rating > 0) {
                $filters['rating'] = $rating;
            }
            if ($verified !== null && $verified !== '') {
                $filters['verified_purchase'] = filter_var($verified, FILTER_VALIDATE_BOOLEAN);
            }

            $reviews = $this->reviewModel->findAll($filters, ['review_date' => 'DESC']);

            $data = [];
            foreach ($reviews as $review) {
                $product = $this->productModel->findById($review['product_id']);
                $reviewData = [
                    'id' => $review['id'],
                    'product_asin' => $product['asin'] ?? '',
                    'product_title' => $product['title'] ?? '',
                    'reviewer_name' => $review['reviewer_name'],
                    'reviewer_id' => $review['reviewer_id'],
                    'rating' => (int)$review['rating'],
                    'title' => $review['title'],
                    'body' => $review['body'],
                    'review_date' => $review['review_date'],
                    'review_url' => $review['review_url'],
                    'helpful_votes' => (int)$review['helpful_votes'],
                    'verified_purchase' => (bool)$review['verified_purchase'],
                    'variant_info' => $review['variant_info'],
                    'image_count' => (int)$review['image_count'],
                    'video_count' => (int)$review['video_count'],
                    'created_at' => $review['created_at'],
                ];

                if ($includeImages) {
                    $images = $this->imageModel->findByReviewId($review['id']);
                    $reviewData['images'] = array_map(function ($image) {
                        return [
                            'original_url' => $image['original_url'],
                            'local_path' => $image['local_path'],
                        ];
                    }, $images);
                }

                $data[] = $reviewData;
            }

            $this->logModel->info(
                OperationLog::TYPE_EXPORT_DATA,
                "导出评论: " . count($data) . "条 ({$format})",
                'export',
                0,
                ['format' => $format, 'count' => count($data), 'filters' => $filters]
            );

            echo json_encode([
                'success' => true,
                'format' => $format,
                'total' => count($data),
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

    public function apiExportCsv(): void
    {
        $input = $_GET;
        $productId = (int)($input['product_id'] ?? 0);
        $rating = (int)($input['rating'] ?? 0);
        $verified = $input['verified'] ?? null;
        $includeImages = isset($input['include_images']) && filter_var($input['include_images'], FILTER_VALIDATE_BOOLEAN);

        try {
            $filters = [];
            if ($productId > 0) {
                $filters['product_id'] = $productId;
            }
            if ($rating > 0) {
                $filters['rating'] = $rating;
            }
            if ($verified !== null && $verified !== '') {
                $filters['verified_purchase'] = filter_var($verified, FILTER_VALIDATE_BOOLEAN);
            }

            $reviews = $this->reviewModel->findAll($filters, ['review_date' => 'DESC']);

            $filename = 'reviews_export_' . date('Ymd_His') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');

            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $headers = [
                'ID',
                '商品ASIN',
                '商品标题',
                '评论者',
                '评论者ID',
                '评分',
                '标题',
                '内容',
                '评论日期',
                '评论链接',
                '有帮助票数',
                '已验证购买',
                '变体信息',
                '图片数量',
                '视频数量',
                '创建时间',
            ];

            if ($includeImages) {
                $headers[] = '图片链接';
            }

            fputcsv($output, $headers);

            foreach ($reviews as $review) {
                $product = $this->productModel->findById($review['product_id']);

                $row = [
                    $review['id'],
                    $product['asin'] ?? '',
                    $product['title'] ?? '',
                    $review['reviewer_name'],
                    $review['reviewer_id'],
                    (int)$review['rating'],
                    $review['title'],
                    $review['body'],
                    $review['review_date'],
                    $review['review_url'],
                    (int)$review['helpful_votes'],
                    $review['verified_purchase'] ? '是' : '否',
                    $review['variant_info'],
                    (int)$review['image_count'],
                    (int)$review['video_count'],
                    $review['created_at'],
                ];

                if ($includeImages) {
                    $images = $this->imageModel->findByReviewId($review['id']);
                    $imageUrls = array_map(fn($img) => $img['original_url'], $images);
                    $row[] = implode('; ', $imageUrls);
                }

                fputcsv($output, $row);
            }

            fclose($output);

            $this->logModel->info(
                OperationLog::TYPE_EXPORT_DATA,
                "导出CSV: " . count($reviews) . "条",
                'export',
                0,
                ['format' => 'csv', 'count' => count($reviews)]
            );

        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiExportJson(): void
    {
        $input = $_GET;
        $productId = (int)($input['product_id'] ?? 0);
        $rating = (int)($input['rating'] ?? 0);
        $verified = $input['verified'] ?? null;
        $pretty = isset($input['pretty']) && filter_var($input['pretty'], FILTER_VALIDATE_BOOLEAN);
        $includeImages = isset($input['include_images']) && filter_var($input['include_images'], FILTER_VALIDATE_BOOLEAN);

        try {
            $filters = [];
            if ($productId > 0) {
                $filters['product_id'] = $productId;
            }
            if ($rating > 0) {
                $filters['rating'] = $rating;
            }
            if ($verified !== null && $verified !== '') {
                $filters['verified_purchase'] = filter_var($verified, FILTER_VALIDATE_BOOLEAN);
            }

            $reviews = $this->reviewModel->findAll($filters, ['review_date' => 'DESC']);

            $data = [];
            foreach ($reviews as $review) {
                $product = $this->productModel->findById($review['product_id']);
                $reviewData = [
                    'id' => $review['id'],
                    'product' => [
                        'asin' => $product['asin'] ?? '',
                        'title' => $product['title'] ?? '',
                    ],
                    'reviewer' => [
                        'name' => $review['reviewer_name'],
                        'id' => $review['reviewer_id'],
                    ],
                    'rating' => (int)$review['rating'],
                    'title' => $review['title'],
                    'body' => $review['body'],
                    'date' => $review['review_date'],
                    'url' => $review['review_url'],
                    'helpful_votes' => (int)$review['helpful_votes'],
                    'verified_purchase' => (bool)$review['verified_purchase'],
                    'variant_info' => $review['variant_info'],
                    'media' => [
                        'images' => (int)$review['image_count'],
                        'videos' => (int)$review['video_count'],
                    ],
                    'created_at' => $review['created_at'],
                ];

                if ($includeImages) {
                    $images = $this->imageModel->findByReviewId($review['id']);
                    $reviewData['images'] = array_map(fn($img) => [
                        'original_url' => $img['original_url'],
                        'local_path' => $img['local_path'],
                    ], $images);
                }

                $data[] = $reviewData;
            }

            $filename = 'reviews_export_' . date('Ymd_His') . '.json';

            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $json = $pretty ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : json_encode($data, JSON_UNESCAPED_UNICODE);

            echo $json;

            $this->logModel->info(
                OperationLog::TYPE_EXPORT_DATA,
                "导出JSON: " . count($data) . "条",
                'export',
                0,
                ['format' => 'json', 'count' => count($data)]
            );

        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiExportExcel(): void
    {
        $input = $_GET;
        $productId = (int)($input['product_id'] ?? 0);
        $rating = (int)($input['rating'] ?? 0);
        $verified = $input['verified'] ?? null;
        $includeImages = isset($input['include_images']) && filter_var($input['include_images'], FILTER_VALIDATE_BOOLEAN);

        try {
            $filters = [];
            if ($productId > 0) {
                $filters['product_id'] = $productId;
            }
            if ($rating > 0) {
                $filters['rating'] = $rating;
            }
            if ($verified !== null && $verified !== '') {
                $filters['verified_purchase'] = filter_var($verified, FILTER_VALIDATE_BOOLEAN);
            }

            $reviews = $this->reviewModel->findAll($filters, ['review_date' => 'DESC']);

            $filename = 'reviews_export_' . date('Ymd_His') . '.xls';

            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<ss:Workbook xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<ss:Worksheet ss:Name="Reviews">';
            echo '<ss:Table>';

            echo '<ss:Row>';
            $headers = [
                'ID',
                '商品ASIN',
                '商品标题',
                '评论者',
                '评分',
                '标题',
                '内容',
                '评论日期',
                '有帮助票数',
                '已验证购买',
                '图片数量',
                '视频数量',
            ];
            foreach ($headers as $header) {
                echo '<ss:Cell><ss:Data ss:Type="String">' . htmlspecialchars($header) . '</ss:Data></ss:Cell>';
            }
            echo '</ss:Row>';

            foreach ($reviews as $review) {
                $product = $this->productModel->findById($review['product_id']);

                echo '<ss:Row>';
                echo '<ss:Cell><ss:Data ss:Type="Number">' . $review['id'] . '</ss:Data></ss:Cell>';
                echo '<ss:Cell><ss:Data ss:Type="String">' . htmlspecialchars($product['asin'] ?? '') . '</ss:Data></ss:Cell>';
                echo '<ss:Cell><ss:Data ss:Type="String">' . htmlspecialchars($product['title'] ?? '') . '</ss:Data></ss:Cell>';
                echo '<ss:Cell><ss:Data ss:Type="String">' . htmlspecialchars($review['reviewer_name']) . '</ss:Data></ss:Cell>';
                echo '<ss:Cell><ss:Data ss:Type="Number">' . (int)$review['rating'] . '</ss:Data></ss:Cell>';
                echo '<ss:Cell><ss:Data ss:Type="String">' . htmlspecialchars($review['title']) . '</ss:Data></ss:Cell>';
                echo '<ss:Cell><ss:Data ss:Type="String">' . htmlspecialchars($review['body']) . '</ss:Data></ss:Cell>';
                echo '<ss:Cell><ss:Data ss:Type="String">' . htmlspecialchars($review['review_date'] ?? '') . '</ss:Data></ss:Cell>';
                echo '<ss:Cell><ss:Data ss:Type="Number">' . (int)$review['helpful_votes'] . '</ss:Data></ss:Cell>';
                echo '<ss:Cell><ss:Data ss:Type="String">' . ($review['verified_purchase'] ? '是' : '否') . '</ss:Data></ss:Cell>';
                echo '<ss:Cell><ss:Data ss:Type="Number">' . (int)$review['image_count'] . '</ss:Data></ss:Cell>';
                echo '<ss:Cell><ss:Data ss:Type="Number">' . (int)$review['video_count'] . '</ss:Data></ss:Cell>';
                echo '</ss:Row>';
            }

            echo '</ss:Table>';
            echo '</ss:Worksheet>';
            echo '</ss:Workbook>';

            $this->logModel->info(
                OperationLog::TYPE_EXPORT_DATA,
                "导出Excel: " . count($reviews) . "条",
                'export',
                0,
                ['format' => 'excel', 'count' => count($reviews)]
            );

        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiExportProducts(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_GET;
        $format = strtolower(trim($input['format'] ?? 'json'));

        $allowedFormats = ['json', 'csv'];
        if (!in_array($format, $allowedFormats)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => '不支持的导出格式',
            ]);
            return;
        }

        try {
            $products = $this->productModel->findAll([], ['created_at' => 'DESC']);

            $data = [];
            foreach ($products as $product) {
                $reviewStats = $this->reviewModel->getStatisticsByProduct($product['id']);
                $data[] = [
                    'id' => $product['id'],
                    'asin' => $product['asin'],
                    'title' => $product['title'],
                    'url' => $product['url'],
                    'image_url' => $product['image_url'],
                    'current_price' => $product['current_price'],
                    'currency' => $product['currency'],
                    'rating' => $product['rating'],
                    'review_count' => $product['review_count'],
                    'scraped_review_count' => (int)($reviewStats['total_reviews'] ?? 0),
                    'five_star_count' => (int)($reviewStats['five_star'] ?? 0),
                    'four_star_count' => (int)($reviewStats['four_star'] ?? 0),
                    'three_star_count' => (int)($reviewStats['three_star'] ?? 0),
                    'two_star_count' => (int)($reviewStats['two_star'] ?? 0),
                    'one_star_count' => (int)($reviewStats['one_star'] ?? 0),
                    'status' => $product['status'],
                    'last_scraped_at' => $product['last_scraped_at'],
                    'created_at' => $product['created_at'],
                ];
            }

            $this->logModel->info(
                OperationLog::TYPE_EXPORT_DATA,
                "导出商品: " . count($data) . "条 ({$format})",
                'export',
                0,
                ['format' => $format, 'count' => count($data), 'type' => 'products']
            );

            echo json_encode([
                'success' => true,
                'format' => $format,
                'total' => count($data),
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

    public function apiExportSummary(): void
    {
        header('Content-Type: application/json');

        try {
            $products = $this->productModel->findAll([], ['created_at' => 'DESC']);

            $summary = [];
            foreach ($products as $product) {
                $reviewStats = $this->reviewModel->getStatisticsByProduct($product['id']);
                $summary[] = [
                    'product' => [
                        'asin' => $product['asin'],
                        'title' => $product['title'],
                        'url' => $product['url'],
                    ],
                    'statistics' => [
                        'total_reviews' => (int)($reviewStats['total_reviews'] ?? 0),
                        'average_rating' => round((float)($reviewStats['average_rating'] ?? 0), 2),
                        'verified_count' => (int)($reviewStats['verified_count'] ?? 0),
                        'rating_distribution' => [
                            '5_star' => (int)($reviewStats['five_star'] ?? 0),
                            '4_star' => (int)($reviewStats['four_star'] ?? 0),
                            '3_star' => (int)($reviewStats['three_star'] ?? 0),
                            '2_star' => (int)($reviewStats['two_star'] ?? 0),
                            '1_star' => (int)($reviewStats['one_star'] ?? 0),
                        ],
                    ],
                    'media' => [
                        'total_images' => (int)($reviewStats['total_images'] ?? 0),
                        'total_videos' => (int)($reviewStats['total_videos'] ?? 0),
                    ],
                    'last_scraped' => $product['last_scraped_at'],
                ];
            }

            $this->logModel->info(
                OperationLog::TYPE_EXPORT_DATA,
                "导出汇总报告",
                'export',
                0,
                ['count' => count($summary), 'type' => 'summary']
            );

            echo json_encode([
                'success' => true,
                'total_products' => count($summary),
                'exported_at' => date('Y-m-d H:i:s'),
                'data' => $summary,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiDownloadImages(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_GET;
        $productId = (int)($input['product_id'] ?? 0);

        try {
            $filters = [];
            if ($productId > 0) {
                $filters['product_id'] = $productId;
            }

            $reviews = $this->reviewModel->findAll($filters, ['review_date' => 'DESC']);

            $imageUrls = [];
            foreach ($reviews as $review) {
                $images = $this->imageModel->findImagesByReviewId($review['id']);
                foreach ($images as $image) {
                    if (!empty($image['original_url'])) {
                        $imageUrls[] = [
                            'review_id' => $review['id'],
                            'url' => $image['original_url'],
                            'local_path' => $image['local_path'],
                        ];
                    }
                }
            }

            $this->logModel->info(
                OperationLog::TYPE_EXPORT_DATA,
                "导出图片列表: " . count($imageUrls) . "张",
                'export',
                0,
                ['count' => count($imageUrls), 'type' => 'images']
            );

            echo json_encode([
                'success' => true,
                'total' => count($imageUrls),
                'data' => $imageUrls,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiExportImagesCsv(): void
    {
        $input = $_GET;
        $productId = (int)($input['product_id'] ?? 0);

        try {
            $filters = [];
            if ($productId > 0) {
                $filters['product_id'] = $productId;
            }

            $reviews = $this->reviewModel->findAll($filters, ['review_date' => 'DESC']);

            $filename = 'review_images_' . date('Ymd_His') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');

            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($output, ['Review ID', '评论者', '评分', '图片URL', '本地路径']);

            foreach ($reviews as $review) {
                $product = $this->productModel->findById($review['product_id']);
                $images = $this->imageModel->findImagesByReviewId($review['id']);

                foreach ($images as $image) {
                    fputcsv($output, [
                        $review['id'],
                        $review['reviewer_name'],
                        (int)$review['rating'],
                        $image['original_url'] ?? '',
                        $image['local_path'] ?? '',
                    ]);
                }
            }

            fclose($output);

            $this->logModel->info(
                OperationLog::TYPE_EXPORT_DATA,
                "导出图片CSV",
                'export',
                0,
                ['type' => 'images_csv']
            );

        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiHistory(): void
    {
        header('Content-Type: application/json');

        try {
            $logs = $this->logModel->findByTarget('export', 0, [
                'limit' => 50,
                'offset' => 0,
            ]);

            $sql = "SELECT * FROM {$this->logModel->table}
                    WHERE operation_type = 'export_data'
                    ORDER BY created_at DESC
                    LIMIT 50";
            $exportLogs = $this->logModel->db->fetchAll($sql);

            $data = array_map(function ($log) {
                $requestData = [];
                if (!empty($log['request_data'])) {
                    $requestData = is_string($log['request_data'])
                        ? json_decode($log['request_data'], true)
                        : $log['request_data'];
                }

                return [
                    'id' => $log['id'],
                    'format' => $requestData['format'] ?? 'unknown',
                    'count' => $requestData['count'] ?? 0,
                    'type' => $requestData['type'] ?? 'reviews',
                    'created_at' => $log['created_at'],
                    'execution_time' => $log['execution_time'],
                ];
            }, $exportLogs);

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
}
