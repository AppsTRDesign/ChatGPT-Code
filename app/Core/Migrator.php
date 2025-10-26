<?php

namespace App\Core;

use PDO;
use PDOException;

class Migrator
{
    public static function run(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $pdo = Database::pdo();
        self::ensureMigrationsTable($pdo);

        $applied = self::getAppliedMigrations($pdo);
        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.sql');
        sort($files);

        foreach ($files as $file) {
            $name = basename($file);
            if (isset($applied[$name])) {
                continue;
            }

            $sql = trim((string) file_get_contents($file));
            if ($sql === '') {
                self::markApplied($pdo, $name);
                continue;
            }

            try {
                $pdo->exec($sql);
            } catch (PDOException $exception) {
                throw new \RuntimeException(sprintf('Migration %s failed: %s', $name, $exception->getMessage()), 0, $exception);
            }

            self::markApplied($pdo, $name);
        }
    }

    protected static function ensureMigrationsTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(191) NOT NULL UNIQUE,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    protected static function getAppliedMigrations(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT name FROM migrations');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

        $applied = [];
        foreach ($rows as $name) {
            $applied[$name] = true;
        }

        return $applied;
    }

    protected static function markApplied(PDO $pdo, string $name): void
    {
        $stmt = $pdo->prepare('INSERT INTO migrations (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);
    }
}
