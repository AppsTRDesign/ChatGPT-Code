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

    if ($db['driver'] === 'sqlite') {
        $pdo = new PDO('sqlite:' . $db['sqlite_path']);
    } else {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $db['driver'],
            $db['host'],
            $db['port'],
            $db['database'],
            $db['charset']
        );
        $pdo = new PDO($dsn, $db['username'], $db['password']);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    initialize_schema($pdo);

    return $pdo;
}

function initialize_schema(PDO $pdo): void
{
    $config = require __DIR__ . '/config.php';
    if ($config['db']['driver'] !== 'sqlite') {
        return;
    }

    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='settings'");
    $exists = $result && $result->fetchColumn();

    if ($exists) {
        return;
    }

    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    $pdo->exec($schema);
}
