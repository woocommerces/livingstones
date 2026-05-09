<?php

declare(strict_types=1);

namespace App\Config;

class Database
{
    public const DRIVER = 'mysql';
    public const HOST = 'localhost';
    public const PORT = 3306;
    public const DATABASE = 'amazon_reviews';
    public const USERNAME = 'root';
    public const PASSWORD = '';
    public const CHARSET = 'utf8mb4';
    public const COLLATION = 'utf8mb4_unicode_ci';
    public const PREFIX = '';

    public static function getConfig(): array
    {
        return [
            'driver' => getenv('DB_DRIVER') ?: self::DRIVER,
            'host' => getenv('DB_HOST') ?: self::HOST,
            'port' => (int) (getenv('DB_PORT') ?: self::PORT),
            'database' => getenv('DB_DATABASE') ?: self::DATABASE,
            'username' => getenv('DB_USERNAME') ?: self::USERNAME,
            'password' => getenv('DB_PASSWORD') ?: self::PASSWORD,
            'charset' => self::CHARSET,
            'collation' => self::COLLATION,
            'prefix' => self::PREFIX,
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
                \PDO::MYSQL_ATTR_FOUND_ROWS => true,
            ],
        ];
    }
}
