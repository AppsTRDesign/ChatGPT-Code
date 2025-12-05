<?php
require_once __DIR__ . '/config.php';

use PDO;

class Database
{
    private static ?PDO $instance = null;

    public static function instance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = new PDO(DB_DSN, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::migrate();
        }
        return self::$instance;
    }

    private static function migrate(): void
    {
        $db = self::$instance;
        $db->exec(
            'CREATE TABLE IF NOT EXISTS submissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                source TEXT NOT NULL,
                payload TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }
}
