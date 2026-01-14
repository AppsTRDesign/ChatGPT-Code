<?php

$config = require __DIR__ . '/config.php';

date_default_timezone_set($config['app']['timezone']);

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $db = $config['db'];

    $dsn = sprintf(
        '%s:host=%s;port=%s;dbname=%s;charset=%s',
        $db['driver'],
        $db['host'],
        $db['port'],
        $db['database'],
        $db['charset']
    );
    $pdo = new PDO($dsn, $db['username'], $db['password']);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $pdo;
}
