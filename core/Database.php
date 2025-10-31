<?php

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $config = Config::get('db');
            $dsn = sprintf('%s:host=%s;dbname=%s;charset=%s', $config['driver'], $config['host'], $config['database'], $config['charset']);

            try {
                self::$pdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $exception) {
                throw new \RuntimeException('Database connection failed: ' . $exception->getMessage());
            }
        }

        return self::$pdo;
    }
}
