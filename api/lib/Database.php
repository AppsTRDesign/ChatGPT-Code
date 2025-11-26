<?php
class Database
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $this->pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
