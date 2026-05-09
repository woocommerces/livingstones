<?php

namespace App\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\ReviewImage;
use App\Models\OperationLog;
use App\Utils\Router;
use Exception;

class ReviewController
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
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $productId = (int)($_GET['product_id'] ?? 0);
        $rating = (int)($_GET['rating'] ?? 0);
        $verified = $_GET['verified'] ?? '';
        $search = $_GET['search'] ?? '';

        $filters = [];
        if ($productId > 0) {
            $filters['product_id'] = $productId;
        }
        if ($rating > 0) {
            $filters['rating'] = $rating;
        }
        if ($verified !== '') {
            $filters['verified_purchase'] = $verified === 'true' || $verified === '1';
        }

        $conditions = $filters;
        if (!empty($search)) {
            $conditions['search'] = $search;
        }

        $products = $this->productModel->findAll([], ['created_at' => 'DESC'], 100);
        $result = $this->reviewModel->paginate($page, $perPage, $conditions, ['review_date' => 'DESC']);

        $router = new Router();
        $baseUrl = $router->generateUrl('/reviews');

        include __DIR__ . '/../../views/reviews/index.php';
    }

    public function view(): void
    {
        global $matches;
        $reviewId = (int)($matches[1] ?? 0);

        if (!$reviewId) {
            $router = new Router();
            $router->redirect('/reviews');
            return;
        }

        $review = $this->reviewModel->findById($reviewId);
        if (!$review) {
            $router = new Router();
            $router->redirect('/reviews');
            return;
        }

        $product = $this->productModel->findById($review['product_id']);
        $images = $this->imageModel->findByReviewId($reviewId);
        $reviewStats = $this->reviewModel->getStatisticsByProduct($review['product_id']);

        include __DIR__ . '/../../views/reviews/view.php';
    }

    public function apiList(): void
    {
        header('Content-Type: application/json');

        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
            $productId = (int)($_GET['product_id'] ?? 0);
            $rating = (int)($_GET['rating'] ?? 0);
            $verified = $_GET['verified'] ?? null;
            $search = $_GET['search'] ?? '';

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

            $result = $this->reviewModel->paginate($page, $perPage, $filters, ['review_date' => 'DESC']);

            $data = array_map(function ($review) {
                return $this->formatReviewResponse($review);
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
        $reviewId = (int)($matches[1] ?? 0);

        if (!$reviewId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的评论ID']);
            return;
        }

        try {
            $review = $this->reviewModel->findById($reviewId);
            if (!$review) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '评论不存在']);
                return;
            }

            $product = $this->productModel->findById($review['product_id']);
            $images = $this->imageModel->findByReviewId($reviewId);

            $response = $this->formatReviewResponse($review);
            $response['product'] = $product ? [
                'id' => $product['id'],
                'asin' => $product['asin'],
                'title' => $product['title'],
            ] : null;
            $response['images'] = array_map(function ($image) {
                return [
                    'id' => $image['id'],
                    'original_url' => $image['original_url'],
                    'local_path' => $image['local_path'],
                    'thumbnail_path' => $image['thumbnail_path'],
                    'is_video' => (bool)$image['is_video'],
                    'download_status' => $image['download_status'],
                ];
            }, $images);

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

    public function delete(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $reviewId = (int)($input['review_id'] ?? 0);

        if (!$reviewId) {
            echo json_encode(['success' => false, 'error' => '无效的评论ID']);
            return;
        }

        try {
            $review = $this->reviewModel->findById($reviewId);
            if (!$review) {
                echo json_encode(['success' => false, 'error' => '评论不存在']);
                return;
            }

            $this->imageModel->deleteByReviewId($reviewId);

            $deleted = $this->reviewModel->delete($reviewId);

            if ($deleted) {
                $this->logModel->info(
                    OperationLog::TYPE_DELETE_PRODUCT,
                    "删除评论: {$reviewId}",
                    'review',
                    $reviewId
                );

                echo json_encode(['success' => true]);
            } else {
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

    public function apiDelete(): void
    {
        header('Content-Type: application/json');

        global $matches;
        $reviewId = (int)($matches[1] ?? 0);

        if (!$reviewId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的评论ID']);
            return;
        }

        try {
            $review = $this->reviewModel->findById($reviewId);
            if (!$review) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '评论不存在']);
                return;
            }

            $this->imageModel->deleteByReviewId($reviewId);
            $deleted = $this->reviewModel->delete($reviewId);

            if ($deleted) {
                $this->logModel->info(
                    OperationLog::TYPE_DELETE_PRODUCT,
                    "删除评论: {$reviewId}",
                    'review',
                    $reviewId
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

    public function apiBulkDelete(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $reviewIds = $input['review_ids'] ?? [];

        if (empty($reviewIds) || !is_array($reviewIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '请提供有效的评论ID列表']);
            return;
        }

        try {
            $deletedCount = 0;
            foreach ($reviewIds as $reviewId) {
                $id = (int)$reviewId;
                if ($id > 0) {
                    $this->imageModel->deleteByReviewId($id);
                    if ($this->reviewModel->delete($id)) {
                        $deletedCount++;
                    }
                }
            }

            $this->logModel->info(
                OperationLog::TYPE_DELETE_PRODUCT,
                "批量删除评论: {$deletedCount}条",
                'review',
                0,
                ['review_ids' => $reviewIds]
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

    public function apiSearch(): void
    {
        header('Content-Type: application/json');

        try {
            $keyword = trim($_GET['keyword'] ?? '');
            $productId = (int)($_GET['product_id'] ?? 0);
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));

            if (empty($keyword)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '搜索关键词不能为空']);
                return;
            }

            $filters = [];
            if ($productId > 0) {
                $filters['product_id'] = $productId;
            }

            $results = $this->reviewModel->searchByKeyword($productId, $keyword);

            $data = array_map(function ($review) {
                return $this->formatReviewResponse($review);
            }, array_slice($results, 0, $limit));

            echo json_encode([
                'success' => true,
                'data' => $data,
                'total' => count($results),
                'returned' => count($data),
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiImages(): void
    {
        header('Content-Type: application/json');

        global $matches;
        $reviewId = (int)($matches[1] ?? 0);

        if (!$reviewId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的评论ID']);
            return;
        }

        try {
            $review = $this->reviewModel->findById($reviewId);
            if (!$review) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '评论不存在']);
                return;
            }

            $images = $this->imageModel->findByReviewId($reviewId);

            $data = array_map(function ($image) {
                return [
                    'id' => $image['id'],
                    'original_url' => $image['original_url'],
                    'local_path' => $image['local_path'],
                    'thumbnail_path' => $image['thumbnail_path'],
                    'file_name' => $image['file_name'],
                    'file_size' => $image['file_size'],
                    'mime_type' => $image['mime_type'],
                    'is_video' => (bool)$image['is_video'],
                    'download_status' => $image['download_status'],
                    'created_at' => $image['created_at'],
                ];
            }, $images);

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

    public function apiGallery(): void
    {
        header('Content-Type: application/json');

        try {
            $productId = (int)($_GET['product_id'] ?? 0);
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));

            if ($productId > 0) {
                $reviews = $this->reviewModel->findByProductId($productId, [
                    'has_images' => true,
                    'limit' => $limit,
                    'offset' => $offset,
                ]);
            } else {
                $sql = "SELECT DISTINCT r.* FROM reviews r
                        INNER JOIN review_images ri ON r.id = ri.review_id
                        WHERE ri.download_status = 'completed'
                        ORDER BY r.review_date DESC
                        LIMIT ? OFFSET ?";
                $reviews = $this->reviewModel->db->fetchAll($sql, [$limit, $offset]);
            }

            $gallery = [];
            foreach ($reviews as $review) {
                $images = $this->imageModel->findImagesByReviewId($review['id']);
                if (!empty($images)) {
                    $gallery[] = [
                        'review_id' => $review['id'],
                        'reviewer_name' => $review['reviewer_name'],
                        'rating' => $review['rating'],
                        'review_date' => $review['review_date'],
                        'images' => array_map(function ($image) {
                            return [
                                'id' => $image['id'],
                                'thumbnail_path' => $image['thumbnail_path'] ?: $image['local_path'],
                                'original_url' => $image['original_url'],
                                'local_path' => $image['local_path'],
                            ];
                        }, $images),
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $gallery,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiStatistics(): void
    {
        header('Content-Type: application/json');

        try {
            $productId = (int)($_GET['product_id'] ?? 0);

            if ($productId > 0) {
                $product = $this->productModel->findById($productId);
                if (!$product) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => '商品不存在']);
                    return;
                }

                $stats = $this->reviewModel->getStatisticsByProduct($productId);
            } else {
                $totalReviews = 0;
                $stats = [
                    'total_reviews' => 0,
                    'five_star' => 0,
                    'four_star' => 0,
                    'three_star' => 0,
                    'two_star' => 0,
                    'one_star' => 0,
                    'verified_count' => 0,
                    'average_rating' => 0,
                    'total_images' => 0,
                    'total_videos' => 0,
                ];

                $products = $this->productModel->findAll([], ['id' => 'ASC'], 1000);
                foreach ($products as $product) {
                    $productStats = $this->reviewModel->getStatisticsByProduct($product['id']);
                    $totalReviews += (int)($productStats['total_reviews'] ?? 0);
                    $stats['total_images'] += (int)($productStats['total_images'] ?? 0);
                    $stats['total_videos'] += (int)($productStats['total_videos'] ?? 0);
                    $stats['verified_count'] += (int)($productStats['verified_count'] ?? 0);

                    for ($rating = 1; $rating <= 5; $rating++) {
                        $key = $rating . '_star';
                        $stats[$key] += (int)($productStats[$key] ?? 0);
                    }
                }
                $stats['total_reviews'] = $totalReviews;
                $stats['average_rating'] = $totalReviews > 0
                    ? round(($stats['five_star'] * 5 + $stats['four_star'] * 4 + $stats['three_star'] * 3 + $stats['two_star'] * 2 + $stats['one_star']) / $totalReviews, 2)
                    : 0;
            }

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

    public function apiByRating(): void
    {
        header('Content-Type: application/json');

        try {
            $productId = (int)($_GET['product_id'] ?? 0);
            $rating = (int)($_GET['rating'] ?? 0);

            if ($productId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '无效的商品ID']);
                return;
            }

            if ($rating < 1 || $rating > 5) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '评分必须是1-5之间的数字']);
                return;
            }

            $reviews = $this->reviewModel->findByRating($productId, $rating);

            $data = array_map(function ($review) {
                return $this->formatReviewResponse($review);
            }, $reviews);

            echo json_encode([
                'success' => true,
                'data' => $data,
                'total' => count($reviews),
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiVerified(): void
    {
        header('Content-Type: application/json');

        try {
            $productId = (int)($_GET['product_id'] ?? 0);
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));

            if ($productId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '无效的商品ID']);
                return;
            }

            $filters = ['product_id' => $productId, 'verified_purchase' => true];
            $result = $this->reviewModel->paginate($page, $perPage, $filters, ['review_date' => 'DESC']);

            $data = array_map(function ($review) {
                return $this->formatReviewResponse($review);
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

    private function formatReviewResponse(array $review): array
    {
        $product = $this->productModel->findById($review['product_id']);
        $imageCount = $this->imageModel->countByReview($review['id']);
        $videoCount = $this->imageModel->countVideosByReview($review['id']);

        return [
            'id' => $review['id'],
            'product_id' => $review['product_id'],
            'product_asin' => $product['asin'] ?? null,
            'product_title' => $product['title'] ?? null,
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
            'is_scraped' => (bool)$review['is_scraped'],
            'created_at' => $review['created_at'],
        ];
    }
}
