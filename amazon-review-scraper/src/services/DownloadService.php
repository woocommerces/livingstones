<?php

namespace App\Services;

use App\Models\ReviewImage;
use App\Models\Review;
use App\Models\Setting;
use App\Utils\Database;
use Exception;

class DownloadService
{
    private $reviewImageModel;
    private $reviewModel;
    private $settingModel;
    private $db;
    private $config = [];

    public function __construct()
    {
        $this->reviewImageModel = new ReviewImage();
        $this->reviewModel = new Review();
        $this->settingModel = new Setting();
        $this->db = Database::getInstance();
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        $this->config = [
            'storage_path' => $this->settingModel->getValue('storage_upload_path', 'uploads'),
            'images_path' => $this->settingModel->getValue('storage_images_path', 'uploads/images'),
            'videos_path' => $this->settingModel->getValue('storage_videos_path', 'uploads/videos'),
            'max_image_size' => (int) $this->settingModel->getValue('storage_max_image_size', 5242880),
            'max_video_size' => (int) $this->settingModel->getValue('storage_max_video_size', 104857600),
            'timeout' => (int) $this->settingModel->getValue('scraper_timeout', 30),
        ];
    }

    public function downloadImage(int $reviewId, string $url): ?array
    {
        $review = $this->reviewModel->findById($reviewId);
        if (!$review) {
            return null;
        }

        $existingImage = $this->reviewImageModel->findByOriginalUrl($url);
        if ($existingImage) {
            return $existingImage;
        }

        $imageData = $this->fetchFile($url);
        if (!$imageData) {
            $this->reviewImageModel->create([
                'review_id' => $reviewId,
                'original_url' => $url,
                'download_status' => 'failed',
            ]);
            return null;
        }

        if (strlen($imageData) > $this->config['max_image_size']) {
            $this->reviewImageModel->create([
                'review_id' => $reviewId,
                'original_url' => $url,
                'download_status' => 'failed',
            ]);
            return null;
        }

        $productId = $review['product_id'];
        $dateFolder = date('Y-m-d');
        $uploadDir = $this->config['images_path'] . "/{$productId}/{$dateFolder}";
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = $this->getImageExtension($url, $imageData);
        $fileName = $this->generateFileName($reviewId, 'img', $extension);
        $localPath = $uploadDir . '/' . $fileName;
        $thumbnailPath = $uploadDir . '/thumb_' . $fileName;

        if (file_put_contents($localPath, $imageData) === false) {
            return null;
        }

        $this->createThumbnail($localPath, $thumbnailPath);

        $mimeType = $this->getMimeType($imageData);

        $imageRecord = $this->reviewImageModel->create([
            'review_id' => $reviewId,
            'original_url' => $url,
            'local_path' => $localPath,
            'thumbnail_path' => file_exists($thumbnailPath) ? $thumbnailPath : null,
            'file_name' => $fileName,
            'file_size' => strlen($imageData),
            'mime_type' => $mimeType,
            'is_video' => false,
            'download_status' => 'completed',
        ]);

        return $imageRecord;
    }

    public function downloadVideo(int $reviewId, string $url): ?array
    {
        $review = $this->reviewModel->findById($reviewId);
        if (!$review) {
            return null;
        }

        $existingVideo = $this->reviewImageModel->findByOriginalUrl($url);
        if ($existingVideo) {
            return $existingVideo;
        }

        $videoData = $this->fetchFile($url);
        if (!$videoData) {
            $this->reviewImageModel->create([
                'review_id' => $reviewId,
                'original_url' => $url,
                'download_status' => 'failed',
            ]);
            return null;
        }

        if (strlen($videoData) > $this->config['max_video_size']) {
            $this->reviewImageModel->create([
                'review_id' => $reviewId,
                'original_url' => $url,
                'download_status' => 'failed',
            ]);
            return null;
        }

        $productId = $review['product_id'];
        $dateFolder = date('Y-m-d');
        $uploadDir = $this->config['videos_path'] . "/{$productId}/{$dateFolder}";
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = $this->getVideoExtension($url);
        $fileName = $this->generateFileName($reviewId, 'vid', $extension);
        $localPath = $uploadDir . '/' . $fileName;

        if (file_put_contents($localPath, $videoData) === false) {
            return null;
        }

        $mimeType = $this->getVideoMimeType($extension);

        $videoRecord = $this->reviewImageModel->create([
            'review_id' => $reviewId,
            'original_url' => $url,
            'local_path' => $localPath,
            'file_name' => $fileName,
            'file_size' => strlen($videoData),
            'mime_type' => $mimeType,
            'is_video' => true,
            'download_status' => 'completed',
        ]);

        return $videoRecord;
    }

    private function fetchFile(string $url, int $timeout = 60): ?string
    {
        $ch = curl_init();
        
        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Referer: https://www.amazon.com/',
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_ENCODING => '',
        ]);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($httpCode !== 200 || $error || empty($data)) {
            return null;
        }

        return $data;
    }

    private function createThumbnail(string $sourcePath, string $thumbPath, int $maxWidth = 200, int $maxHeight = 200): bool
    {
        if (!extension_loaded('gd')) {
            return false;
        }

        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $mimeType = $imageInfo['mime'];
        $sourceImage = null;

        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
        }

        if (!$sourceImage) {
            return false;
        }

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        
        if ($ratio >= 1) {
            return copy($sourcePath, $thumbPath);
        }

        $thumbWidth = (int) round($sourceWidth * $ratio);
        $thumbHeight = (int) round($sourceHeight * $ratio);

        $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        if ($mimeType === 'image/png') {
            imagealphablending($thumbImage, false);
            imagesavealpha($thumbImage, true);
        }

        imagecopyresampled($thumbImage, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $sourceWidth, $sourceHeight);

        $result = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($thumbImage, $thumbPath, 85);
                break;
            case 'image/png':
                $result = imagepng($thumbImage, $thumbPath);
                break;
            case 'image/gif':
                $result = imagegif($thumbImage, $thumbPath);
                break;
            case 'image/webp':
                $result = imagewebp($thumbImage, $thumbPath, 85);
                break;
        }

        imagedestroy($sourceImage);
        imagedestroy($thumbImage);

        return $result;
    }

    private function generateFileName(int $reviewId, string $prefix, string $extension): string
    {
        $uniqueId = substr(md5(uniqid(mt_rand(), true)), 0, 8);
        return "{$prefix}_{$reviewId}_{$uniqueId}.{$extension}";
    }

    private function getImageExtension(string $url, string $data): string
    {
        if (preg_match('/\.(\w+)(?:\?|$)/', $url, $matches)) {
            $ext = strtolower($matches[1]);
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return $ext;
            }
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($data);
        
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $mimeToExt[$mimeType] ?? 'jpg';
    }

    private function getVideoExtension(string $url): string
    {
        if (preg_match('/\.(\w+)(?:\?|$)/', $url, $matches)) {
            $ext = strtolower($matches[1]);
            if (in_array($ext, ['mp4', 'webm', 'mov', 'avi', 'mkv'])) {
                return $ext;
            }
        }

        return 'mp4';
    }

    private function getMimeType(string $data): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($data) ?? 'image/jpeg';
    }

    private function getVideoMimeType(string $extension): string
    {
        $extToMime = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska',
        ];

        return $extToMime[$extension] ?? 'video/mp4';
    }

    public function deleteImage(int $imageId): bool
    {
        $image = $this->reviewImageModel->findById($imageId);
        if (!$image) {
            return false;
        }

        if (!empty($image['local_path']) && file_exists($image['local_path'])) {
            unlink($image['local_path']);
        }

        if (!empty($image['thumbnail_path']) && file_exists($image['thumbnail_path'])) {
            unlink($image['thumbnail_path']);
        }

        return $this->reviewImageModel->delete($imageId);
    }

    public function batchDownloadImages(int $reviewId, array $urls): array
    {
        $results = [];
        foreach ($urls as $url) {
            $result = $this->downloadImage($reviewId, $url);
            $results[] = [
                'url' => $url,
                'success' => $result !== null,
                'image_id' => $result['id'] ?? null,
            ];
        }
        return $results;
    }

    public function cleanupOrphanedFiles(): int
    {
        $deletedCount = 0;
        $uploadsDir = $this->config['storage_path'];
        
        if (!is_dir($uploadsDir)) {
            return 0;
        }

        $allLocalPaths = $this->reviewImageModel->getAllLocalPaths();
        $validPaths = array_column($allLocalPaths, 'local_path');
        $validPaths = array_merge($validPaths, array_filter(array_column($allLocalPaths, 'thumbnail_path')));

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($uploadsDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $realPath = $file->getRealPath();
                if (!in_array($realPath, $validPaths)) {
                    if (unlink($realPath)) {
                        $deletedCount++;
                    }
                }
            }
        }

        return $deletedCount;
    }

    public function getStorageStats(): array
    {
        $uploadsDir = $this->config['storage_path'];
        
        $totalSize = 0;
        $imageCount = 0;
        $videoCount = 0;

        if (is_dir($uploadsDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($uploadsDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $totalSize += $file->getSize();
                    $extension = strtolower($file->getExtension());
                    
                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $imageCount++;
                    } elseif (in_array($extension, ['mp4', 'webm', 'mov', 'avi', 'mkv'])) {
                        $videoCount++;
                    }
                }
            }
        }

        return [
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'image_count' => $imageCount,
            'video_count' => $videoCount,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
