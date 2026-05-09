<?php

declare(strict_types=1);

return [
    'name' => getenv('APP_NAME') ?: 'Amazon Review Scraper',
    'version' => '1.0.0',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => (bool) (getenv('APP_DEBUG') ?: false),
    'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Shanghai',
    'locale' => getenv('APP_LOCALE') ?: 'zh-CN',
    'charset' => 'UTF-8',

    'paths' => [
        'root' => dirname(__DIR__, 2),
        'src' => dirname(__DIR__, 2) . '/src',
        'config' => dirname(__DIR__) . '/config',
        'utils' => dirname(__DIR__) . '/utils',
        'models' => dirname(__DIR__) . '/models',
        'storage' => dirname(__DIR__, 2) . '/storage',
        'uploads' => dirname(__DIR__, 2) . '/storage/uploads',
        'images' => dirname(__DIR__, 2) . '/storage/uploads/images',
        'videos' => dirname(__DIR__, 2) . '/storage/uploads/videos',
        'logs' => dirname(__DIR__, 2) . '/storage/logs',
    ],

    'scraper' => [
        'delay_min' => 3,
        'delay_max' => 8,
        'max_retries' => 3,
        'timeout' => 30,
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'images_enabled' => true,
        'videos_enabled' => true,
        'max_images_per_review' => 10,
        'concurrent_tasks' => 2,
        'max_pages' => 100,
    ],

    'storage' => [
        'upload_path' => 'uploads',
        'images_path' => 'uploads/images',
        'videos_path' => 'uploads/videos',
        'max_image_size' => 5242880,
        'max_video_size' => 104857600,
    ],

    'display' => [
        'items_per_page' => 20,
        'chart_days' => 30,
    ],
];
