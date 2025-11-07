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
        self::ensureDispatchJobColumns($connection);
        self::ensureAudienceTables($connection);
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

    private static function ensureDispatchJobColumns(\PDO $connection): void
    {
        $columns = array_flip(self::getTableColumns($connection, 'dispatch_jobs'));

        if (!isset($columns['action'])) {
            $connection->exec("ALTER TABLE dispatch_jobs ADD COLUMN action VARCHAR(50) NOT NULL DEFAULT 'send_message' AFTER name");
        }

        if (!isset($columns['metadata'])) {
            $connection->exec('ALTER TABLE dispatch_jobs ADD COLUMN metadata TEXT NULL AFTER target_value');
        }

        if (!isset($columns['template_id'])) {
            $connection->exec('ALTER TABLE dispatch_jobs ADD COLUMN template_id INT UNSIGNED NULL AFTER action');
        }
    }

    private static function ensureAudienceTables(\PDO $connection): void
    {
        self::ensureTableExists($connection, 'audience_templates', "CREATE TABLE IF NOT EXISTS audience_templates (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(150) NOT NULL,
            entity_type VARCHAR(32) NOT NULL,
            description TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY unique_template_name (name, entity_type),
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::ensureTableExists($connection, 'audience_template_members', "CREATE TABLE IF NOT EXISTS audience_template_members (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            template_id INT UNSIGNED NOT NULL,
            member_id INT UNSIGNED NOT NULL,
            created_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_template_member (template_id, member_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::ensureTableExists($connection, 'channel_targets', "CREATE TABLE IF NOT EXISTS channel_targets (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            telegram_id VARCHAR(64) NOT NULL,
            access_hash VARCHAR(128) NULL,
            username VARCHAR(150) NULL,
            title VARCHAR(255) NULL,
            type VARCHAR(32) NOT NULL,
            is_public TINYINT(1) NOT NULL DEFAULT 0,
            extra TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY unique_channel (telegram_id, type),
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::ensureTableExists($connection, 'audience_template_channels', "CREATE TABLE IF NOT EXISTS audience_template_channels (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            template_id INT UNSIGNED NOT NULL,
            channel_id INT UNSIGNED NOT NULL,
            created_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_template_channel (template_id, channel_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private static function ensureTableExists(\PDO $connection, string $table, string $createSql): void
    {
        $driver = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $stmt = $connection->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:table");
            $stmt->execute(['table' => $table]);
            $exists = $stmt->fetch();
        } else {
            $stmt = $connection->prepare('SHOW TABLES LIKE :table');
            $stmt->execute(['table' => $table]);
            $exists = $stmt->fetch();
        }

        if (!$exists) {
            $connection->exec($createSql);
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
