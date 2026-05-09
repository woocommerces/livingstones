<?php

namespace App\Models;

use App\Utils\Database;

class OperationLog extends BaseModel
{
    protected $table = 'operation_logs';

    protected $fillable = [
        'log_level',
        'operation_type',
        'operation_desc',
        'target_type',
        'target_id',
        'request_data',
        'response_data',
        'ip_address',
        'user_agent',
        'execution_time',
        'error_message',
    ];

    protected $casts = [
        'target_id' => 'integer',
        'execution_time' => 'float',
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_DEBUG = 'debug';

    public const TYPE_SCRAPE_PRODUCT = 'scrape_product';
    public const TYPE_ADD_PRODUCT = 'add_product';
    public const TYPE_DELETE_PRODUCT = 'delete_product';
    public const TYPE_EXPORT_DATA = 'export_data';
    public const TYPE_UPDATE_SETTINGS = 'update_settings';
    public const TYPE_DOWNLOAD_IMAGE = 'download_image';

    public function log(
        string $level,
        string $operationType,
        ?string $description = null,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $requestData = null,
        ?array $responseData = null,
        ?float $executionTime = null,
        ?string $errorMessage = null
    ): ?array {
        return $this->create([
            'log_level' => $level,
            'operation_type' => $operationType,
            'operation_desc' => $description,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'request_data' => $requestData ? json_encode($requestData) : null,
            'response_data' => $responseData ? json_encode($responseData) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'execution_time' => $executionTime,
            'error_message' => $errorMessage,
        ]);
    }

    public function info(string $operationType, ?string $description = null, ?array $context = null): ?array
    {
        return $this->log(self::LEVEL_INFO, $operationType, $description, null, null, $context);
    }

    public function error(string $operationType, string $errorMessage, ?array $context = null): ?array
    {
        return $this->log(self::LEVEL_ERROR, $operationType, $errorMessage, null, null, $context, null, null, $errorMessage);
    }

    public function findByLevel(string $level, int $limit = 100): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE log_level = :level ORDER BY created_at DESC LIMIT :limit";
        return $this->db->fetchAll($sql, ['level' => $level, 'limit' => $limit]);
    }

    public function findByTarget(string $targetType, int $targetId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE target_type = :target_type AND target_id = :target_id 
                ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, [
            'target_type' => $targetType,
            'target_id' => $targetId,
        ]);
    }

    public function findByDateRange(string $startDate, string $endDate, int $limit = 1000): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE created_at BETWEEN :start_date AND :end_date 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'limit' => $limit,
        ]);
    }

    public function getRecentLogs(int $limit = 50): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT :limit";
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }

    public function getErrorLogs(int $limit = 100): array
    {
        return $this->findByLevel(self::LEVEL_ERROR, $limit);
    }

    public function cleanOldLogs(int $daysToKeep = 30): int
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY) 
                AND log_level != 'error'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['days' => $daysToKeep]);
        
        return $stmt->rowCount();
    }

    public function getStatistics(): array
    {
        $sql = "SELECT 
                    log_level,
                    COUNT(*) as count
                FROM {$this->table} 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY log_level";
        
        $result = $this->db->fetchAll($sql);
        
        $stats = [
            'total' => 0,
            'info' => 0,
            'warning' => 0,
            'error' => 0,
            'debug' => 0,
        ];

        foreach ($result as $row) {
            $stats[$row['log_level']] = (int) $row['count'];
            $stats['total'] += (int) $row['count'];
        }

        return $stats;
    }
}
