<?php

class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;

    private function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->pdo = new \PDO($dsn, $config['username'], $config['password'], $options);
        if (!empty($config['collation'])) {
            $this->pdo->exec('SET NAMES ' . $config['charset'] . ' COLLATE ' . $config['collation']);
        }
    }

    public static function getInstance(): Database
    {
        if (!self::$instance) {
            $config = require __DIR__ . '/../config.php';
            self::$instance = new self($config['db']);
        }
        return self::$instance;
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }
}
