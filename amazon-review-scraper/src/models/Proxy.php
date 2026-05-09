<?php

namespace App\Models;

use App\Utils\Database;

class Proxy extends BaseModel
{
    protected $table = 'proxies';

    protected $fillable = [
        'proxy_host',
        'proxy_port',
        'proxy_user',
        'proxy_password',
        'proxy_type',
        'is_active',
        'success_count',
        'fail_count',
        'last_used_at',
    ];

    protected $casts = [
        'proxy_port' => 'integer',
        'is_active' => 'boolean',
        'success_count' => 'integer',
        'fail_count' => 'integer',
    ];

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = :is_active";
            $params['is_active'] = $filters['is_active'];
        }

        $sql .= " ORDER BY success_count DESC, fail_count ASC";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params['limit'] = $filters['limit'];
        }

        return $this->db->fetchAll($sql, $params);
    }

    public function getRandomActiveProxy(): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                AND (success_count + fail_count) < 10 
                ORDER BY RAND() LIMIT 1";
        
        return $this->db->fetchOne($sql);
    }

    public function create(array $data): ?array
    {
        $required = ['proxy_host', 'proxy_port'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return null;
            }
        }

        $data['is_active'] = $data['is_active'] ?? true;
        $data['success_count'] = 0;
        $data['fail_count'] = 0;

        return parent::create($data);
    }

    public function incrementSuccessCount(int $id): bool
    {
        $sql = "UPDATE {$this->table} 
                SET success_count = success_count + 1, 
                    last_used_at = NOW() 
                WHERE id = :id";
        
        return $this->db->query($sql, ['id' => $id]) !== false;
    }

    public function incrementFailCount(int $id): bool
    {
        $sql = "UPDATE {$this->table} 
                SET fail_count = fail_count + 1, 
                    last_used_at = NOW() 
                WHERE id = :id";
        
        return $this->db->query($sql, ['id' => $id]) !== false;
    }

    public function toggleActive(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET is_active = NOT is_active WHERE id = :id";
        return $this->db->query($sql, ['id' => $id]) !== false;
    }

    public function resetStats(int $id): bool
    {
        $sql = "UPDATE {$this->table} 
                SET success_count = 0, 
                    fail_count = 0 
                WHERE id = :id";
        
        return $this->db->query($sql, ['id' => $id]) !== false;
    }

    public function getProxyFormated(int $id): ?string
    {
        $proxy = $this->findById($id);
        if (!$proxy) {
            return null;
        }

        $auth = '';
        if (!empty($proxy['proxy_user'])) {
            $auth = "{$proxy['proxy_user']}:{$proxy['proxy_password']}@";
        }

        return "{$proxy['proxy_type']}://{$auth}{$proxy['proxy_host']}:{$proxy['proxy_port']}";
    }

    public function getSuccessRate(int $id): ?float
    {
        $proxy = $this->findById($id);
        if (!$proxy) {
            return null;
        }

        $total = $proxy['success_count'] + $proxy['fail_count'];
        if ($total === 0) {
            return null;
        }

        return round(($proxy['success_count'] / $total) * 100, 2);
    }

    public function disableIfFailing(int $id, int $threshold = 5): bool
    {
        $proxy = $this->findById($id);
        if (!$proxy) {
            return false;
        }

        if ($proxy['fail_count'] >= $threshold && $proxy['fail_count'] > $proxy['success_count'] * 2) {
            $sql = "UPDATE {$this->table} SET is_active = 0 WHERE id = :id";
            return $this->db->query($sql, ['id' => $id]) !== false;
        }

        return false;
    }
}
