<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Proxy;
use Exception;

class AmazonAPI
{
    private $baseUrl = 'https://www.amazon.com';
    private $reviewsPath = '/product-reviews';
    private $config = [];
    private $proxyModel;
    private $currentProxy = null;

    private $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ];

    public function __construct()
    {
        $this->proxyModel = new Proxy();
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        $settingModel = new Setting();
        $this->config = [
            'timeout' => (int) $settingModel->getValue('scraper_timeout', 30),
            'use_proxy' => (bool) $settingModel->getValue('enable_proxy', false),
            'delay_min' => (int) $settingModel->getValue('scraper_delay_min', 3),
            'delay_max' => (int) $settingModel->getValue('scraper_delay_max', 8),
        ];
    }

    public function fetchProductInfo(string $asin): ?array
    {
        $url = $this->buildProductUrl($asin);
        
        $html = $this->request($url);
        if (!$html) {
            return null;
        }

        return $this->parseProductInfo($html, $asin);
    }

    public function fetchReviews(string $asin, int $page = 1): array
    {
        $url = $this->buildReviewsUrl($asin, $page);
        $html = $this->request($url);
        
        if (!$html) {
            return [];
        }

        return $this->parseReviews($html, $asin);
    }

    private function buildProductUrl(string $asin): string
    {
        return "{$this->baseUrl}/dp/{$asin}";
    }

    private function buildReviewsUrl(string $asin, int $page = 1): string
    {
        return "{$this->baseUrl}{$this->reviewsPath}/{$asin}/?pageNumber={$page}";
    }

    private function request(string $url, array $options = []): ?string
    {
        $ch = curl_init();
        
        $headers = $this->buildHeaders();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_ENCODING => 'gzip, deflate',
        ]);

        if ($this->config['use_proxy'] && $this->currentProxy) {
            curl_setopt($ch, CURLOPT_PROXY, $this->currentProxy['proxy_host']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->currentProxy['proxy_port']);
            
            if (!empty($this->currentProxy['proxy_user'])) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, 
                    "{$this->currentProxy['proxy_user']}:{$this->currentProxy['proxy_password']}");
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($httpCode !== 200 || $error) {
            if ($this->currentProxy) {
                $this->proxyModel->incrementFailCount($this->currentProxy['id']);
            }
            return null;
        }

        if ($this->currentProxy) {
            $this->proxyModel->incrementSuccessCount($this->currentProxy['id']);
        }

        return $response;
    }

    private function buildHeaders(): array
    {
        return [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Encoding: gzip, deflate, br',
            'DNT: 1',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Cache-Control: max-age=0',
            'User-Agent: ' . $this->userAgents[array_rand($this->userAgents)],
        ];
    }

    private function parseProductInfo(string $html, string $asin): ?array
    {
        $data = [
            'asin' => $asin,
            'title' => $this->extractText($html, 'productTitle', '#productTitle'),
            'image_url' => $this->extractImage($html, '#landingImage'),
            'rating' => $this->extractRating($html),
            'review_count' => $this->extractReviewCount($html),
            'current_price' => $this->extractPrice($html),
            'url' => $this->buildProductUrl($asin),
        ];

        return !empty($data['title']) ? $data : null;
    }

    private function parseReviews(string $html, string $asin): array
    {
        $reviews = [];
        
        $reviewBlocks = $this->extractReviewBlocks($html);
        
        foreach ($reviewBlocks as $block) {
            $review = $this->parseReviewBlock($block, $asin);
            if ($review) {
                $reviews[] = $review;
            }
        }

        return $reviews;
    }

    private function extractReviewBlocks(string $html): array
    {
        $blocks = [];
        
        if (preg_match_all('/<div[^>]*data-hook="review"[^>]*>(.*?)<\/div>\s*<\/div>/si', $html, $matches)) {
            $blocks = $matches[0];
        }
        
        if (empty($blocks) && preg_match_all('/<div[^>]*class="[^"]*review[^"]*"[^>]*>(.*?)<\/div>\s*<div/si', $html, $matches)) {
            $blocks = $matches[0];
        }

        return $blocks;
    }

    private function parseReviewBlock(string $block, string $asin): ?array
    {
        $reviewerName = $this->extractReviewField($block, ['a[data-hook="review-author"', '.a-profile-name']);
        $rating = $this->extractRatingFromBlock($block);
        $title = $this->extractReviewField($block, ['a[data-hook="review-title"', '.review-title']);
        $body = $this->extractReviewBody($block);
        $reviewDate = $this->extractDate($block);
        $helpfulVotes = $this->extractHelpfulVotes($block);
        $verifiedPurchase = $this->isVerifiedPurchase($block);
        $variantInfo = $this->extractVariantInfo($block);
        $reviewUrl = $this->extractReviewUrl($block, $asin);
        $reviewerId = $this->extractReviewerId($block);
        $images = $this->extractImages($block);
        $video = $this->extractVideo($block);

        if (empty($body) && empty($title)) {
            return null;
        }

        return [
            'reviewer_name' => $reviewerName,
            'reviewer_id' => $reviewerId,
            'rating' => $rating,
            'title' => trim($title),
            'body' => trim($body),
            'review_date' => $reviewDate,
            'review_url' => $reviewUrl,
            'helpful_votes' => $helpfulVotes,
            'verified_purchase' => $verifiedPurchase,
            'variant_info' => $variantInfo,
            'images' => $images,
            'video' => $video,
        ];
    }

    private function extractText(string $html, string $dataHook, string $cssSelector): ?string
    {
        if (preg_match('/data-hook="' . preg_quote($dataHook, '/') . '"[^>]*>([^<]+)<\/span>/i', $html, $matches)) {
            return trim($matches[1]);
        }
        
        if (preg_match('/<' . str_replace('/', '\/', $cssSelector) . '[^>]*id="' . preg_quote($dataHook, '/') . '"[^>]*>([^<]+)<\/span>/i', $html, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function extractImage(string $html, string $selector): ?string
    {
        if (preg_match('/id="' . preg_quote($selector, '/') . '"[^>]*src="([^"]+)"/i', $html, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/id="' . preg_quote($selector, '/') . '"[^>]*data-a-dynamic-image="([^"]+)"/i', $html, $matches)) {
            $images = json_decode(html_entity_decode($matches[1]), true);
            if ($images && is_array($images)) {
                return array_key_first($images);
            }
        }

        return null;
    }

    private function extractRating(string $html): ?float
    {
        if (preg_match('/data-hook="average-stars-rating"[^>]*>([^<]+)<\/span>/i', $html, $matches)) {
            $rating = trim($matches[1]);
            if (preg_match('/(\d+\.?\d*)/', $rating, $stars)) {
                return (float) $stars[1];
            }
        }
        
        if (preg_match('/class="[^"]*rating[^"]*"[^>]*>.*?(\d+\.?\d*).*?out.*?of.*?5/is', $html, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    private function extractReviewCount(string $html): int
    {
        if (preg_match('/data-hook="total-review-count"[^>]*>([^<]+)<\/span>/i', $html, $matches)) {
            $count = trim($matches[1]);
            return (int) preg_replace('/[^0-9]/', '', $count);
        }
        
        if (preg_match('/class="[^"]*review-count[^"]*"[^>]*>([^<]+)<\/span>/i', $html, $matches)) {
            $count = trim($matches[1]);
            return (int) preg_replace('/[^0-9]/', '', $count);
        }

        return 0;
    }

    private function extractPrice(string $html): ?float
    {
        if (preg_match('/class="[^"]*price-to-buy[^"]*"[^>]*>.*?\$([0-9,]+\.?[0-9]*)/is', $html, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }
        
        if (preg_match('/data-a-color="price"[^>]*>.*?\$([0-9,]+\.?[0-9]*)/is', $html, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        return null;
    }

    private function extractReviewField(string $block, array $selectors): ?string
    {
        foreach ($selectors as $selector) {
            if (strpos($selector, 'data-hook') !== false) {
                $pattern = '/' . str_replace('/', '\/', $selector) . '[^>]*>([^<]+)<\/a>/i';
            } else {
                $pattern = '/' . preg_quote($selector, '/') . '[^>]*>([^<]+)<\/[^>]+>/i';
            }
            
            if (preg_match($pattern, $block, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    private function extractRatingFromBlock(string $block): int
    {
        if (preg_match('/class="[^"]*star-rating[^"]*"[^>]*>.*?aria-label="([^"]*)"/is', $block, $matches)) {
            if (preg_match('/(\d+)/', $matches[1], $stars)) {
                return (int) $stars[1];
            }
        }
        
        if (preg_match('/class="[^"]*rating-number[^"]*"[^>]*>\((\d+)\)/i', $block, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/<i[^>]*class="[^"]*fa-star[^"]*"[^>]*>/i', $block, $matches)) {
            $stars = substr_count($block, 'fa-star') - substr_count($block, 'fa-star-o');
            return max(1, min(5, $stars));
        }

        return 0;
    }

    private function extractReviewBody(string $block): ?string
    {
        $patterns = [
            '/data-hook="review-body"[^>]*>([^<]+(?:<[^>]+>[^<]*)*)/i',
            '/class="[^"]*review-text[^"]*"[^>]*>([^<]+(?:<[^>]+>[^<]*)*)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $block, $matches)) {
                $body = strip_tags($matches[1], '<br><p>');
                $body = html_entity_decode(trim($body));
                $body = preg_replace('/\s+/', ' ', $body);
                if (!empty($body)) {
                    return $body;
                }
            }
        }

        return null;
    }

    private function extractDate(string $block): ?string
    {
        $patterns = [
            '/data-hook="review-date"[^>]*>([^<]+)<\/span>/i',
            '/class="[^"]*review-date[^"]*"[^>]*>([^<]+)<\/span>/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $block, $matches)) {
                $dateStr = trim($matches[1]);
                return $this->parseAmazonDate($dateStr);
            }
        }

        return null;
    }

    private function parseAmazonDate(string $dateStr): ?string
    {
        $dateStr = preg_replace('/on\s+/i', '', $dateStr);
        
        $patterns = [
            'F j, Y' => '/([A-Za-z]+ \d{1,2}, \d{4})/',
            'M j, Y' => '/([A-Za-z]+ \d{1,2}, \d{4})/',
            'j F Y' => '/(\d{1,2} [A-Za-z]+ \d{4})/',
            'Y-m-d' => '/(\d{4}-\d{2}-\d{2})/',
        ];

        foreach ($patterns as $format => $pattern) {
            if (preg_match($pattern, $dateStr, $matches)) {
                $date = \DateTime::createFromFormat($format, $matches[1]);
                if ($date) {
                    return $date->format('Y-m-d');
                }
            }
        }

        return null;
    }

    private function extractHelpfulVotes(string $block): int
    {
        $patterns = [
            '/data-hook="helpful-votes-styles"[^>]*>([^<]+)<\/span>/i',
            '/class="[^"]*helpful-votes[^"]*"[^>]*>([^<]+)<\/span>/i',
            '/(\d+)\s*people found this helpful/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $block, $matches)) {
                $votes = trim($matches[1]);
                return (int) preg_replace('/[^0-9]/', '', $votes);
            }
        }

        return 0;
    }

    private function isVerifiedPurchase(string $block): bool
    {
        return (bool) preg_match('/data-hook="avp-badge"[^>]*>|class="[^"]*avp-badge[^"]*"|<span[^>]*>Verified Purchase<\/span>/i', $block);
    }

    private function extractVariantInfo(string $block): ?string
    {
        if (preg_match('/data-hook="format-strip"[^>]*>([^<]+)<\/span>/i', $block, $matches)) {
            return trim($matches[1]);
        }
        
        if (preg_match('/class="[^"]*review-format-strip[^"]*"[^>]*>([^<]+)<\/span>/i', $block, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function extractReviewUrl(string $block, string $asin): ?string
    {
        if (preg_match('/href="(\/gp\/rc\/[^"]+)"[^>]*>/i', $block, $matches)) {
            return $this->baseUrl . $matches[1];
        }
        
        if (preg_match('/data-hook="review-title"[^>]*href="([^"]+)"/i', $block, $matches)) {
            return $this->baseUrl . $matches[1];
        }

        return "{$this->baseUrl}/gp/customer-reviews/{$asin}";
    }

    private function extractReviewerId(string $block): ?string
    {
        if (preg_match('/href="\/gp\/profile\/([^"]+)"/i', $block, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/data-hook="review-author"[^>]*href="[^/]+\/([^"]+)"/i', $block, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractImages(string $block): array
    {
        $images = [];
        
        if (preg_match_all('/data-hook="review-image"[^>]*src="([^"]+)"/i', $block, $matches)) {
            foreach ($matches[1] as $src) {
                $images[] = $this->convertToHighResImage($src);
            }
        }
        
        if (preg_match_all('/class="[^"]*review-image-tile[^"]*"[^>]*src="([^"]+)"/i', $block, $matches)) {
            foreach ($matches[1] as $src) {
                $images[] = $this->convertToHighResImage($src);
            }
        }

        return array_unique($images);
    }

    private function extractVideo(string $block): ?string
    {
        if (preg_match('/data-hook="review-video"[^>]*src="([^"]+)"/i', $block, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/<video[^>]*src="([^"]+)"/i', $block, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/data-video-url="([^"]+)"/i', $block, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function convertToHighResImage(string $url): string
    {
        $url = str_replace(['_SL75_', '_SL150_', '_AC_US75_'], ['_SL500_', '_SL500_', '_AC_US500_'], $url);
        $url = str_replace('/images/I/', '/images/I/', $url);
        
        return $url;
    }

    public function fetchReviewImages(string $reviewUrl): array
    {
        $html = $this->request($reviewUrl);
        if (!$html) {
            return [];
        }

        $images = [];
        if (preg_match_all('/data-src="([^"]+\.(?:jpg|jpeg|png|gif))"/i', $html, $matches)) {
            $images = $matches[1];
        }
        
        if (preg_match_all('/src="([^"]+\.(?:jpg|jpeg|png|gif))"/i', $html, $matches)) {
            $images = array_merge($images, $matches[1]);
        }

        return array_unique($images);
    }

    public function setProxy(?array $proxy = null): void
    {
        $this->currentProxy = $proxy;
    }

    public function getRandomProxy(): ?array
    {
        return $this->proxyModel->getRandomActiveProxy();
    }

    private function randomDelay(): void
    {
        $delay = rand($this->config['delay_min'], $this->config['delay_max']);
        sleep($delay);
    }

    public function validateAsin(string $input): ?string
    {
        $patterns = [
            '/(B[0-9]{2}[0-9A-Z]{7})/' => 10,
            '/dp\/([A-Z0-9]{10})/' => 10,
            '/\/([A-Z0-9]{10})\??/' => 10,
        ];

        foreach ($patterns as $pattern => $length) {
            if (preg_match($pattern, $input, $matches)) {
                $asin = strlen($matches[1]) === $length ? $matches[1] : null;
                if ($asin) {
                    return $asin;
                }
            }
        }

        if (preg_match('/^[A-Z0-9]{10}$/', strtoupper($input))) {
            return strtoupper($input);
        }

        return null;
    }
}
