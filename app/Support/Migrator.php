<?php
declare(strict_types=1);

namespace App\Support;

use App\Core\Database;
use App\Models\Service;

final class Migrator
{
    public static function run(): void
    {
        $flag = storage_path('data/.migrated');
        $connection = Database::connection();

        if (!file_exists($flag)) {
            $sql = file_get_contents(database_path('migrations.sql'));
            if ($sql !== false) {
                self::executeSqlStatements($connection, $sql);
            }

            \App\Models\AdminUser::ensureDefaultAdmin();
            if (!is_dir(dirname($flag))) {
                mkdir(dirname($flag), 0775, true);
            }
            file_put_contents($flag, 'migrated: ' . date('c'));
        }

        self::ensureUpgrades($connection);
    }

    private static function ensureUpgrades(\PDO $connection): void
    {
        self::ensureTelegramAccountColumns($connection);
        self::ensureServiceColumns($connection);
        Service::ensureDefaults();
    }

    private static function ensureTelegramAccountColumns(\PDO $connection): void
    {
        $columns = array_flip(self::getTableColumns($connection, 'telegram_accounts'));

        if (!isset($columns['phone_code_hash'])) {
            $connection->exec('ALTER TABLE telegram_accounts ADD COLUMN phone_code_hash VARCHAR(150) NULL');
        }

        if (!isset($columns['two_factor_hint'])) {
            $connection->exec('ALTER TABLE telegram_accounts ADD COLUMN two_factor_hint VARCHAR(150) NULL');
        }

        if (!isset($columns['last_error'])) {
            $connection->exec('ALTER TABLE telegram_accounts ADD COLUMN last_error TEXT NULL');
        }
    }

    private static function ensureServiceColumns(\PDO $connection): void
    {
        $columns = array_flip(self::getTableColumns($connection, 'services'));

        if (!isset($columns['description'])) {
            $connection->exec('ALTER TABLE services ADD COLUMN description TEXT NULL AFTER status');
        }

        if (!isset($columns['command'])) {
            $connection->exec('ALTER TABLE services ADD COLUMN command TEXT NULL AFTER description');
        }
    }

    private static function getTableColumns(\PDO $connection, string $table): array
    {
        $driver = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $connection->query("PRAGMA table_info('" . $table . "')");
            if (!$stmt) {
                return [];
            }
            $result = $stmt->fetchAll();
            return array_map(static fn($column) => $column['name'] ?? '', $result);
        }

        try {
            $stmt = $connection->prepare('SHOW COLUMNS FROM `' . $table . '`');
            if ($stmt && $stmt->execute()) {
                $result = $stmt->fetchAll();
                return array_map(static fn($column) => $column['Field'] ?? '', $result);
            }
        } catch (\Throwable $e) {
            return [];
        }

        return [];
    }

    private static function executeSqlStatements(\PDO $connection, string $sql): void
    {
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
        if (!$statements) {
            return;
        }

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }

            $connection->exec($statement);
        }
    }
}
