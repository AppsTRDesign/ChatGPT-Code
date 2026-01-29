<?php
require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $instance = null;

    public static function instance(): PDO
    {
        if (self::$instance === null) {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            // Ensure UTF-8 for MySQL connections
            if (str_starts_with(DB_DSN, 'mysql:')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8mb4';
            }

            self::$instance = new PDO(DB_DSN, DB_USER, DB_PASS, $options);
            self::migrate();
        }

        return self::$instance;
    }

    private static function migrate(): void
    {
        $db = self::$instance;
        $schemaFile = __DIR__ . '/schema.sql';
        if (!is_readable($schemaFile)) {
            throw new RuntimeException('Schema file missing: ' . $schemaFile);
        }

        $schemaSql = file_get_contents($schemaFile);
        if ($schemaSql === false) {
            throw new RuntimeException('Unable to read schema file.');
        }

        foreach (array_filter(array_map('trim', explode(';', $schemaSql))) as $statement) {
            if ($statement !== '') {
                $db->exec($statement);
            }
        }
    }
}
