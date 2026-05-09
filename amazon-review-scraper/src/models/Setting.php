<?php

declare(strict_types=1);

namespace App\Models;

class Setting extends BaseModel
{
    protected string $table = 'settings';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'description',
        'group_name',
    ];

    public const TYPE_STRING = 'string';
    public const TYPE_NUMBER = 'number';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_JSON = 'json';

    public function findByKey(string $key): ?array
    {
        $sql = 'SELECT * FROM `settings` WHERE `setting_key` = ? LIMIT 1';
        return $this->db->fetchOne($sql, [$key]);
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = $this->findByKey($key);

        if ($setting === null) {
            return $default;
        }

        return $this->castValue($setting['setting_value'], $setting['setting_type']);
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = $this->getValue($key, $default);
        return is_string($value) ? $value : $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->getValue($key, $default);
        return is_numeric($value) ? (int) $value : $default;
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        $value = $this->getValue($key, $default);
        return is_numeric($value) ? (float) $value : $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->getValue($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    public function getJson(string $key, array $default = []): array
    {
        $value = $this->getValue($key, json_encode($default));
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $default;
    }

    public function setValue(string $key, mixed $value, string $type = self::TYPE_STRING): bool
    {
        $existing = $this->findByKey($key);

        $storedValue = $value;
        if ($type === self::TYPE_BOOLEAN) {
            $storedValue = $value ? '1' : '0';
        } elseif ($type === self::TYPE_JSON) {
            $storedValue = is_string($value) ? $value : json_encode($value);
        } elseif (!is_string($value)) {
            $storedValue = (string) $value;
        }

        if ($existing !== null) {
            $this->update($existing['id'], [
                'setting_value' => $storedValue,
                'setting_type' => $type,
            ]);
        } else {
            $this->create([
                'setting_key' => $key,
                'setting_value' => $storedValue,
                'setting_type' => $type,
            ]);
        }

        return true;
    }

    public function setString(string $key, string $value, ?string $description = null): bool
    {
        if ($description !== null) {
            $existing = $this->findByKey($key);
            if ($existing !== null) {
                $this->update($existing['id'], ['description' => $description]);
            }
        }
        return $this->setValue($key, $value, self::TYPE_STRING);
    }

    public function setInt(string $key, int $value): bool
    {
        return $this->setValue($key, (string) $value, self::TYPE_NUMBER);
    }

    public function setBool(string $key, bool $value): bool
    {
        return $this->setValue($key, $value, self::TYPE_BOOLEAN);
    }

    public function setJson(string $key, array $value): bool
    {
        return $this->setValue($key, json_encode($value), self::TYPE_JSON);
    }

    public function deleteByKey(string $key): int
    {
        return $this->db->delete($this->table, '`setting_key` = ?', [$key]);
    }

    public function exists(string $key): bool
    {
        $sql = 'SELECT 1 FROM `settings` WHERE `setting_key` = ? LIMIT 1';
        return $this->db->fetchColumn($sql, [$key]) !== false;
    }

    public function findByGroup(string $groupName): array
    {
        return $this->findAll(['group_name' => $groupName], ['setting_key' => 'ASC']);
    }

    public function getAllGroups(): array
    {
        $sql = 'SELECT DISTINCT `group_name` FROM `settings` ORDER BY `group_name` ASC';
        return $this->db->fetchAll($sql);
    }

    public function getAllSettingsFlat(): array
    {
        $settings = $this->findAll([], ['group_name' => 'ASC', 'setting_key' => 'ASC']);
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $this->castValue(
                $setting['setting_value'],
                $setting['setting_type']
            );
        }
        return $result;
    }

    public function getScraperSettings(): array
    {
        return $this->findByGroup('scraper');
    }

    public function getStorageSettings(): array
    {
        return $this->findByGroup('storage');
    }

    protected function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            self::TYPE_NUMBER => is_numeric($value) ? (strpos($value, '.') !== false ? (float) $value : (int) $value) : $value,
            self::TYPE_BOOLEAN => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            self::TYPE_JSON => json_decode($value, true) ?? $value,
            default => $value,
        };
    }
}
