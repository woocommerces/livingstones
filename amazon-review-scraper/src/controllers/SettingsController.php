<?php

namespace App\Controllers;

use App\Models\Setting;
use App\Models\Proxy;
use App\Models\OperationLog;
use App\Services\ScraperService;
use App\Utils\Router;
use Exception;

class SettingsController
{
    private Setting $settingModel;
    private Proxy $proxyModel;
    private OperationLog $logModel;
    private ScraperService $scraperService;

    public function __construct()
    {
        $this->settingModel = new Setting();
        $this->proxyModel = new Proxy();
        $this->logModel = new OperationLog();
        $this->scraperService = new ScraperService();
    }

    public function index(): void
    {
        $scraperSettings = $this->settingModel->getScraperSettings();
        $storageSettings = $this->settingModel->getStorageSettings();
        $proxies = $this->proxyModel->findAll([]);
        $proxyStats = $this->getProxyStatistics();

        include __DIR__ . '/../../views/settings/index.php';
    }

    public function indexScraper(): void
    {
        $settings = $this->settingModel->getScraperSettings();
        $allSettings = $this->settingModel->getAllSettingsFlat();

        $scraperConfig = [
            'delay_min' => $this->settingModel->getInt('scraper_delay_min', 3),
            'delay_max' => $this->settingModel->getInt('scraper_delay_max', 8),
            'max_retries' => $this->settingModel->getInt('scraper_max_retries', 3),
            'timeout' => $this->settingModel->getInt('scraper_timeout', 30),
            'user_agent' => $this->settingModel->getString('scraper_user_agent', ''),
            'images_enabled' => $this->settingModel->getBool('scraper_images_enabled', true),
            'videos_enabled' => $this->settingModel->getBool('scraper_videos_enabled', true),
            'max_images_per_review' => $this->settingModel->getInt('scraper_max_images_per_review', 10),
            'concurrent_tasks' => $this->settingModel->getInt('scraper_concurrent_tasks', 2),
            'pagination_max_pages' => $this->settingModel->getInt('pagination_max_pages', 100),
        ];

        include __DIR__ . '/../../views/settings/scraper.php';
    }

    public function indexProxy(): void
    {
        $proxies = $this->proxyModel->findAll([]);
        $proxyStats = $this->getProxyStatistics();

        include __DIR__ . '/../../views/settings/proxy.php';
    }

    public function apiList(): void
    {
        header('Content-Type: application/json');

        try {
            $groups = $_GET['groups'] ?? null;

            if ($groups) {
                $groupList = array_filter(array_map('trim', explode(',', $groups)));
                $settings = [];
                foreach ($groupList as $group) {
                    $settings = array_merge($settings, $this->settingModel->findByGroup($group));
                }
            } else {
                $settings = $this->settingModel->findAll([], ['group_name' => 'ASC', 'setting_key' => 'ASC']);
            }

            $data = array_map(function ($setting) {
                return [
                    'id' => $setting['id'],
                    'key' => $setting['setting_key'],
                    'value' => $this->settingModel->castValue($setting['setting_value'], $setting['setting_type']),
                    'type' => $setting['setting_type'],
                    'group' => $setting['group_name'],
                    'description' => $setting['description'],
                ];
            }, $settings);

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

    public function apiGet(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_GET;
        $key = trim($input['key'] ?? '');

        if (empty($key)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '设置键不能为空']);
            return;
        }

        try {
            $setting = $this->settingModel->findByKey($key);

            if (!$setting) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '设置不存在']);
                return;
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'key' => $setting['setting_key'],
                    'value' => $this->settingModel->castValue($setting['setting_value'], $setting['setting_type']),
                    'type' => $setting['setting_type'],
                    'group' => $setting['group_name'],
                    'description' => $setting['description'],
                ],
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiSave(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        if (empty($input) || !is_array($input)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的请求数据']);
            return;
        }

        try {
            $saved = 0;
            $errors = [];

            foreach ($input as $key => $data) {
                if (!is_array($data)) {
                    continue;
                }

                $settingKey = trim($data['key'] ?? $key);
                $value = $data['value'] ?? null;
                $type = $data['type'] ?? Setting::TYPE_STRING;
                $group = $data['group'] ?? 'general';
                $description = $data['description'] ?? null;

                $existing = $this->settingModel->findByKey($settingKey);

                if ($existing !== null) {
                    $this->settingModel->update($existing['id'], [
                        'setting_value' => $this->prepareValue($value, $type),
                        'setting_type' => $type,
                    ]);
                } else {
                    $this->settingModel->create([
                        'setting_key' => $settingKey,
                        'setting_value' => $this->prepareValue($value, $type),
                        'setting_type' => $type,
                        'group_name' => $group,
                        'description' => $description,
                    ]);
                }

                $saved++;
            }

            $this->logModel->info(
                OperationLog::TYPE_UPDATE_SETTINGS,
                "保存设置: {$saved}项",
                'setting',
                0,
                ['keys' => array_keys($input)]
            );

            echo json_encode([
                'success' => true,
                'saved_count' => $saved,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiSaveScraperSettings(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        try {
            $settingsToSave = [
                'scraper_delay_min' => ['value' => $input['delay_min'] ?? 3, 'type' => Setting::TYPE_NUMBER],
                'scraper_delay_max' => ['value' => $input['delay_max'] ?? 8, 'type' => Setting::TYPE_NUMBER],
                'scraper_max_retries' => ['value' => $input['max_retries'] ?? 3, 'type' => Setting::TYPE_NUMBER],
                'scraper_timeout' => ['value' => $input['timeout'] ?? 30, 'type' => Setting::TYPE_NUMBER],
                'scraper_user_agent' => ['value' => $input['user_agent'] ?? '', 'type' => Setting::TYPE_STRING],
                'scraper_images_enabled' => ['value' => $input['images_enabled'] ?? true, 'type' => Setting::TYPE_BOOLEAN],
                'scraper_videos_enabled' => ['value' => $input['videos_enabled'] ?? true, 'type' => Setting::TYPE_BOOLEAN],
                'scraper_max_images_per_review' => ['value' => $input['max_images_per_review'] ?? 10, 'type' => Setting::TYPE_NUMBER],
                'scraper_concurrent_tasks' => ['value' => $input['concurrent_tasks'] ?? 2, 'type' => Setting::TYPE_NUMBER],
                'pagination_max_pages' => ['value' => $input['pagination_max_pages'] ?? 100, 'type' => Setting::TYPE_NUMBER],
            ];

            foreach ($settingsToSave as $key => $data) {
                $this->settingModel->setValue($key, $data['value'], $data['type']);
            }

            $this->logModel->info(
                OperationLog::TYPE_UPDATE_SETTINGS,
                '保存爬虫设置',
                'setting',
                0,
                ['keys' => array_keys($settingsToSave)]
            );

            echo json_encode([
                'success' => true,
                'message' => '爬虫设置已保存',
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiSaveProxy(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $required = ['proxy_host', 'proxy_port'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "字段 {$field} 是必需的"]);
                return;
            }
        }

        try {
            $proxyData = [
                'proxy_host' => trim($input['proxy_host']),
                'proxy_port' => (int)$input['proxy_port'],
                'proxy_user' => trim($input['proxy_user'] ?? ''),
                'proxy_password' => trim($input['proxy_password'] ?? ''),
                'proxy_type' => trim($input['proxy_type'] ?? 'http'),
                'is_active' => true,
            ];

            $proxyId = $this->proxyModel->create($proxyData);

            if ($proxyId) {
                $this->logModel->info(
                    OperationLog::TYPE_UPDATE_SETTINGS,
                    "添加代理: {$proxyData['proxy_host']}:{$proxyData['proxy_port']}",
                    'proxy',
                    $proxyId
                );

                echo json_encode([
                    'success' => true,
                    'message' => '代理添加成功',
                    'proxy_id' => $proxyId,
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => '代理创建失败']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiUpdateProxy(): void
    {
        header('Content-Type: application/json');

        global $matches;
        $proxyId = (int)($matches[1] ?? 0);

        if (!$proxyId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的代理ID']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        try {
            $proxy = $this->proxyModel->findById($proxyId);
            if (!$proxy) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '代理不存在']);
                return;
            }

            $updateData = [];

            if (isset($input['proxy_host'])) {
                $updateData['proxy_host'] = trim($input['proxy_host']);
            }
            if (isset($input['proxy_port'])) {
                $updateData['proxy_port'] = (int)$input['proxy_port'];
            }
            if (isset($input['proxy_user'])) {
                $updateData['proxy_user'] = trim($input['proxy_user']);
            }
            if (isset($input['proxy_password'])) {
                $updateData['proxy_password'] = trim($input['proxy_password']);
            }
            if (isset($input['proxy_type'])) {
                $updateData['proxy_type'] = trim($input['proxy_type']);
            }

            if (!empty($updateData)) {
                $this->proxyModel->update($proxyId, $updateData);
            }

            $this->logModel->info(
                OperationLog::TYPE_UPDATE_SETTINGS,
                "更新代理: {$proxyId}",
                'proxy',
                $proxyId,
                $updateData
            );

            echo json_encode([
                'success' => true,
                'message' => '代理更新成功',
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiDeleteProxy(): void
    {
        header('Content-Type: application/json');

        global $matches;
        $proxyId = (int)($matches[1] ?? 0);

        if (!$proxyId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的代理ID']);
            return;
        }

        try {
            $proxy = $this->proxyModel->findById($proxyId);
            if (!$proxy) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '代理不存在']);
                return;
            }

            $deleted = $this->proxyModel->delete($proxyId);

            if ($deleted) {
                $this->logModel->info(
                    OperationLog::TYPE_UPDATE_SETTINGS,
                    "删除代理: {$proxy['proxy_host']}:{$proxy['proxy_port']}",
                    'proxy',
                    $proxyId
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

    public function apiToggleProxy(): void
    {
        header('Content-Type: application/json');

        global $matches;
        $proxyId = (int)($matches[1] ?? 0);

        if (!$proxyId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的代理ID']);
            return;
        }

        try {
            $proxy = $this->proxyModel->findById($proxyId);
            if (!$proxy) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '代理不存在']);
                return;
            }

            $this->proxyModel->toggleActive($proxyId);
            $updatedProxy = $this->proxyModel->findById($proxyId);

            echo json_encode([
                'success' => true,
                'is_active' => $updatedProxy['is_active'],
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiResetProxyStats(): void
    {
        header('Content-Type: application/json');

        global $matches;
        $proxyId = (int)($matches[1] ?? 0);

        if (!$proxyId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的代理ID']);
            return;
        }

        try {
            $proxy = $this->proxyModel->findById($proxyId);
            if (!$proxy) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => '代理不存在']);
                return;
            }

            $this->proxyModel->resetStats($proxyId);

            $this->logModel->info(
                OperationLog::TYPE_UPDATE_SETTINGS,
                "重置代理统计: {$proxyId}",
                'proxy',
                $proxyId
            );

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiProxyList(): void
    {
        header('Content-Type: application/json');

        try {
            $activeOnly = isset($_GET['active_only']) && filter_var($_GET['active_only'], FILTER_VALIDATE_BOOLEAN);

            $filters = [];
            if ($activeOnly) {
                $filters['is_active'] = true;
            }

            $proxies = $this->proxyModel->findAll($filters);

            $data = array_map(function ($proxy) {
                return [
                    'id' => $proxy['id'],
                    'proxy_host' => $proxy['proxy_host'],
                    'proxy_port' => $proxy['proxy_port'],
                    'proxy_user' => $proxy['proxy_user'],
                    'proxy_type' => $proxy['proxy_type'],
                    'is_active' => (bool)$proxy['is_active'],
                    'success_count' => (int)$proxy['success_count'],
                    'fail_count' => (int)$proxy['fail_count'],
                    'success_rate' => $this->proxyModel->getSuccessRate($proxy['id']),
                    'last_used_at' => $proxy['last_used_at'],
                    'created_at' => $proxy['created_at'],
                ];
            }, $proxies);

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

    public function apiProxyStats(): void
    {
        header('Content-Type: application/json');

        try {
            $stats = $this->getProxyStatistics();

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

    public function apiExport(): void
    {
        header('Content-Type: application/json');

        try {
            $format = $_GET['format'] ?? 'json';

            $settings = $this->settingModel->getAllSettingsFlat();

            if ($format === 'json') {
                echo json_encode([
                    'success' => true,
                    'settings' => $settings,
                    'exported_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => '不支持的导出格式']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function apiImport(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input) || !isset($input['settings']) || !is_array($input['settings'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '无效的导入数据']);
            return;
        }

        try {
            $imported = 0;
            $errors = [];

            foreach ($input['settings'] as $key => $value) {
                try {
                    if (is_bool($value)) {
                        $this->settingModel->setBool($key, $value);
                    } elseif (is_int($value)) {
                        $this->settingModel->setInt($key, $value);
                    } elseif (is_array($value)) {
                        $this->settingModel->setJson($key, $value);
                    } else {
                        $this->settingModel->setString($key, (string)$value);
                    }
                    $imported++;
                } catch (Exception $e) {
                    $errors[] = "设置 {$key} 导入失败: " . $e->getMessage();
                }
            }

            $this->logModel->info(
                OperationLog::TYPE_UPDATE_SETTINGS,
                "导入设置: {$imported}项",
                'setting',
                0,
                ['imported' => $imported]
            );

            echo json_encode([
                'success' => empty($errors),
                'imported_count' => $imported,
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

    public function apiReset(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $confirm = trim($input['confirm'] ?? '');

        if ($confirm !== 'RESET') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '请确认重置操作']);
            return;
        }

        try {
            $sql = "DELETE FROM {$this->settingModel->table} WHERE 1=1";
            $this->settingModel->db->query($sql);

            $this->settingModel->setInt('scraper_delay_min', 3);
            $this->settingModel->setInt('scraper_delay_max', 8);
            $this->settingModel->setInt('scraper_max_retries', 3);
            $this->settingModel->setInt('scraper_timeout', 30);
            $this->settingModel->setBool('scraper_images_enabled', true);
            $this->settingModel->setBool('scraper_videos_enabled', true);
            $this->settingModel->setInt('scraper_max_images_per_review', 10);
            $this->settingModel->setInt('scraper_concurrent_tasks', 2);
            $this->settingModel->setInt('pagination_max_pages', 100);

            $this->logModel->info(
                OperationLog::TYPE_UPDATE_SETTINGS,
                '重置所有设置为默认值',
                'setting',
                0
            );

            echo json_encode([
                'success' => true,
                'message' => '设置已重置为默认值',
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getProxyStatistics(): array
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
                    SUM(success_count) as total_success,
                    SUM(fail_count) as total_fail
                FROM proxies";

        $stats = $this->proxyModel->db->fetchOne($sql) ?: [];

        return [
            'total' => (int)($stats['total'] ?? 0),
            'active' => (int)($stats['active'] ?? 0),
            'inactive' => (int)($stats['inactive'] ?? 0),
            'total_success' => (int)($stats['total_success'] ?? 0),
            'total_fail' => (int)($stats['total_fail'] ?? 0),
        ];
    }

    private function prepareValue(mixed $value, string $type): string
    {
        if ($type === Setting::TYPE_BOOLEAN) {
            return $value ? '1' : '0';
        } elseif ($type === Setting::TYPE_JSON) {
            return is_string($value) ? $value : json_encode($value);
        } elseif (!is_string($value)) {
            return (string)$value;
        }
        return $value;
    }
}
