<?php

declare(strict_types=1);

namespace App;

use App\Controllers\ProductController;
use App\Controllers\ReviewController;
use App\Controllers\DashboardController;
use App\Controllers\SettingsController;
use App\Controllers\TaskController;
use App\Controllers\ExportController;
use App\Utils\Router;
use App\Utils\Database;
use Exception;

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/error.log');

try {
    Database::getInstance();
} catch (Exception $e) {
    die('数据库连接失败: ' . $e->getMessage());
}

$router = new Router();

$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/add', [ProductController::class, 'add']);
$router->post('/products/store', [ProductController::class, 'store']);
$router->post('/products/scrape', [ProductController::class, 'scrape']);
$router->post('/products/delete', [ProductController::class, 'delete']);
$router->get('/products/view/(\d+)', [ProductController::class, 'view']);

$router->get('/reviews', [ReviewController::class, 'index']);
$router->get('/reviews/view/(\d+)', [ReviewController::class, 'view']);
$router->post('/reviews/delete', [ReviewController::class, 'delete']);
$router->post('/reviews/export', [ReviewController::class, 'export']);

$router->get('/visualization', [DashboardController::class, 'visualization']);
$router->get('/visualization/ratings', [DashboardController::class, 'ratingsData']);
$router->get('/visualization/timeline', [DashboardController::class, 'timelineData']);
$router->get('/visualization/wordcloud', [DashboardController::class, 'wordcloudData']);

$router->get('/tasks', [TaskController::class, 'index']);
$router->get('/tasks/status/(\d+)', [TaskController::class, 'status']);
$router->post('/tasks/pause', [TaskController::class, 'pause']);
$router->post('/tasks/resume', [TaskController::class, 'resume']);
$router->post('/tasks/retry', [TaskController::class, 'retry']);
$router->post('/tasks/cancel', [TaskController::class, 'cancel']);

$router->get('/settings', [SettingsController::class, 'index']);
$router->post('/settings/save', [SettingsController::class, 'save']);
$router->get('/settings/proxies', [SettingsController::class, 'proxies']);
$router->post('/settings/proxies/add', [SettingsController::class, 'addProxy']);
$router->post('/settings/proxies/toggle', [SettingsController::class, 'toggleProxy']);
$router->post('/settings/proxies/delete', [SettingsController::class, 'deleteProxy']);

$router->get('/export', [ExportController::class, 'index']);
$router->post('/export/reviews', [ExportController::class, 'exportReviews']);
$router->post('/export/products', [ExportController::class, 'exportProducts']);

$router->get('/images', [ReviewController::class, 'images']);

$router->post('/api/products', [ProductController::class, 'apiStore']);
$router->get('/api/reviews', [ReviewController::class, 'apiList']);
$router->get('/api/dashboard/stats', [DashboardController::class, 'apiStats']);

$router->post('/upload/image', [ReviewController::class, 'uploadImage']);

$router->post('/logout', function() {
    session_destroy();
    header('Location: /');
    exit;
});

try {
    $router->dispatch();
} catch (Exception $e) {
    error_log($e->getMessage());
    
    http_response_code(500);
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    } else {
        include __DIR__ . '/views/errors/500.php';
    }
}
