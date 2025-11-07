<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function boot(array $config): void
    {
        if (self::$pdo instanceof PDO) {
            return;
        }

        $driver = $config['driver'] ?? 'sqlite';

        try {
            switch ($driver) {
                case 'mysql':
                    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
                        $config['host'] ?? '127.0.0.1',
                        $config['port'] ?? 3306,
                        $config['database'] ?? 'telegrambot',
                        $config['charset'] ?? 'utf8mb4'
                    );
                    break;
                case 'sqlite':
                default:
                    $database = $config['database'] ?? database_path('database.sqlite');
                    if (!file_exists($database)) {
                        $directory = dirname($database);
                        if (!is_dir($directory)) {
                            mkdir($directory, 0775, true);
                        }
                        touch($database);
                    }
                    $dsn = 'sqlite:' . $database;
                    break;
            }

            self::$pdo = new PDO($dsn, $config['username'] ?? null, $config['password'] ?? null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            if ($driver === 'sqlite') {
                self::$pdo->exec('PRAGMA foreign_keys = ON');
            }
        } catch (PDOException $exception) {
            http_response_code(500);
            echo 'Database connection failed: ' . htmlspecialchars($exception->getMessage());
            exit;
        }
    }

    public static function connection(): PDO
    {
        if (!self::$pdo) {
            throw new PDOException('Database not initialised.');
        }

        return self::$pdo;
    }
}
