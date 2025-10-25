<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    protected static ?PDO $pdo = null;

    public static function init(array $config): void
    {
        if (static::$pdo !== null) {
            return;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'] ?? 'localhost',
            $config['port'] ?? 3306,
            $config['database'] ?? ''
        );

        $options = $config['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            static::$pdo = new PDO($dsn, $config['username'] ?? 'root', $config['password'] ?? '', $options);
        } catch (PDOException $exception) {
            error_log('Database connection failed: ' . $exception->getMessage());
            throw $exception;
        }
    }

    public static function pdo(): PDO
    {
        if (static::$pdo === null) {
            throw new \RuntimeException('Database connection not initialized.');
        }

        return static::$pdo;
    }
}
