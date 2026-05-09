#!/bin/bash

# Amazon Review Scraper - Cron Job Script
# Install: crontab -e
# Add: */30 * * * * /usr/bin/php /path/to/amazon-review-scraper/cron.php >> /var/log/amazon-cron.log 2>&1

$basePath = '/var/www/amazon-review-scraper';
chdir($basePath);

require_once $basePath . '/vendor/autoload.php';

use App\Models\Product;
use App\Models\ScrapingTask;
use App\Models\Setting;
use App\Services\ScraperService;
use App\Utils\Database;

$startTime = microtime(true);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', $basePath . '/logs/cron.log');

try {
    Database::getInstance();
    
    $settingModel = new Setting();
    
    if (!$settingModel->getValue('enable_cron', false)) {
        echo "[" . date('Y-m-d H:i:s') . "] Cron is disabled in settings.\n";
        exit(0);
    }

    $productModel = new Product();
    $scraperService = new ScraperService();

    $pendingProducts = $productModel->findByStatus('pending');
    $failedProducts = $productModel->findByStatus('failed');

    $maxTasks = (int) $settingModel->getValue('scraper_concurrent_tasks', 2);
    $currentTasks = (new ScrapingTask())->countByStatus('running');
    
    $availableSlots = max(0, $maxTasks - $currentTasks);

    if ($availableSlots <= 0) {
        echo "[" . date('Y-m-d H:i:s') . "] No available task slots.\n";
        exit(0);
    }

    $productsToProcess = array_merge(
        array_slice($failedProducts, 0, (int)($availableSlots / 2)),
        array_slice($pendingProducts, 0, $availableSlots)
    );

    $processed = 0;
    foreach ($productsToProcess as $product) {
        $productModel->update($product['id'], ['status' => 'pending']);
        
        $result = $scraperService->scrapeProduct($product['id'], [
            'max_pages' => (int) $settingModel->getValue('pagination_max_pages', 100),
            'task_type' => 'incremental',
        ]);

        $status = $result['success'] ? 'SUCCESS' : 'FAILED';
        echo "[" . date('Y-m-d H:i:s') . "] {$status}: ASIN {$product['asin']} - {$result['total_reviews']} reviews\n";
        
        $processed++;
        
        if ($processed >= $availableSlots) {
            break;
        }
        
        sleep(5);
    }

    $executionTime = round((microtime(true) - $startTime) * 1000, 2);
    echo "[" . date('Y-m-d H:i:s') . "] Cron completed. Processed {$processed} products in {$executionTime}ms\n";

} catch (Exception $e) {
    error_log("Cron Error: " . $e->getMessage());
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
