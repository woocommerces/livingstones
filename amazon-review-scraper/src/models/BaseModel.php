<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Database;
use RuntimeException;

abstract class BaseModel
{
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(array $conditions, array $orderBy = [], int $limit = 0, int $offset = 0): ?array
    {
        $where = $this->buildWhereClause($conditions);
        $params = $this->extractParams($conditions);

        $sql = sprintf('SELECT * FROM `%s`', $this->table);

        if (!empty($where)) {
            $sql .= ' WHERE ' . $where;
        }

        if (!empty($orderBy)) {
            $sql .= ' ORDER BY ' . $this->buildOrderByClause($orderBy);
        }

        if ($limit > 0) {
            $sql .= sprintf(' LIMIT %d', $limit);
            if ($offset > 0) {
                $sql .= sprintf(' OFFSET %d', $offset);
            }
        }

        $sql .= ' LIMIT 1';

        return $this->db->fetchOne($sql, $params);
    }

    public function findAll(array $conditions = [], array $orderBy = [], int $limit = 0, int $offset = 0): array
    {
        $where = $this->buildWhereClause($conditions);
        $params = $this->extractParams($conditions);

        $sql = sprintf('SELECT * FROM `%s`', $this->table);

        if (!empty($where)) {
            $sql .= ' WHERE ' . $where;
        }

        if (!empty($orderBy)) {
            $sql .= ' ORDER BY ' . $this->buildOrderByClause($orderBy);
        }

        if ($limit > 0) {
            $sql .= sprintf(' LIMIT %d', $limit);
            if ($offset > 0) {
                $sql .= sprintf(' OFFSET %d', $offset);
            }
        }

        return $this->db->fetchAll($sql, $params);
    }

    public function findById(int $id): ?array
    {
        $sql = sprintf('SELECT * FROM `%s` WHERE `%s` = ? LIMIT 1', $this->table, $this->primaryKey);
        return $this->db->fetchOne($sql, [$id]);
    }

    public function create(array $data): int
    {
        if (empty($data)) {
            throw new RuntimeException('创建记录时数据不能为空');
        }

        $this->filterFillable($data);

        return $this->db->insert($this->table, $data);
    }

    public function update(int $id, array $data): int
    {
        if (empty($data)) {
            throw new RuntimeException('更新记录时数据不能为空');
        }

        $this->filterFillable($data);

        $sql = sprintf('`%s` = ?', $this->primaryKey);
        return $this->db->update($this->table, $data, $sql, [$id]);
    }

    public function delete(int $id): int
    {
        $sql = sprintf('`%s` = ?', $this->primaryKey);
        return $this->db->delete($this->table, $sql, [$id]);
    }

    public function count(array $conditions = []): int
    {
        if (empty($conditions)) {
            $sql = sprintf('SELECT COUNT(*) FROM `%s`', $this->table);
            return (int) $this->db->fetchColumn($sql);
        }

        $where = $this->buildWhereClause($conditions);
        $params = $this->extractParams($conditions);

        $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', $this->table, $where);
        return (int) $this->db->fetchColumn($sql, $params);
    }

    public function paginate(int $page = 1, int $perPage = 20, array $conditions = [], array $orderBy = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $total = $this->count($conditions);
        $totalPages = (int) ceil($total / $perPage);

        $items = $this->findAll($conditions, $orderBy, $perPage, $offset);

        return [
            'data' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages,
            ],
        ];
    }

    protected function buildWhereClause(array $conditions): string
    {
        if (empty($conditions)) {
            return '';
        }

        $clauses = [];
        foreach ($conditions as $column => $value) {
            if (is_null($value)) {
                $clauses[] = sprintf('`%s` IS NULL', $column);
            } elseif (is_array($value)) {
                $placeholders = implode(', ', array_fill(0, count($value), '?'));
                $clauses[] = sprintf('`%s` IN (%s)', $column, $placeholders);
            } else {
                $clauses[] = sprintf('`%s` = ?', $column);
            }
        }

        return implode(' AND ', $clauses);
    }

    protected function extractParams(array $conditions): array
    {
        $params = [];
        foreach ($conditions as $value) {
            if (is_array($value)) {
                $params = array_merge($params, $value);
            } elseif (!is_null($value)) {
                $params[] = $value;
            }
        }
        return $params;
    }

    protected function buildOrderByClause(array $orderBy): string
    {
        $clauses = [];
        foreach ($orderBy as $column => $direction) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $clauses[] = sprintf('`%s` %s', $column, $direction);
        }
        return implode(', ', $clauses);
    }

    protected function filterFillable(array &$data): void
    {
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }
    }

    public function exists(int $id): bool
    {
        $sql = sprintf('SELECT 1 FROM `%s` WHERE `%s` = ? LIMIT 1', $this->table, $this->primaryKey);
        return $this->db->fetchColumn($sql, [$id]) !== false;
    }

    public function getLastInsertId(): string
    {
        return $this->db->getLastInsertId();
    }

    public function transaction(callable $callback): mixed
    {
        return $this->db->transaction($callback);
    }
}
