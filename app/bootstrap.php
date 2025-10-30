<?php
spl_autoload_register(function ($class) {
    $baseDir = __DIR__;
    $paths = [
        'controllers',
        'models',
        'middlewares',
        'utils',
    ];
    foreach ($paths as $path) {
        $file = $baseDir . '/' . $path . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$config = require __DIR__ . '/config/config.php';
$pdo = require __DIR__ . '/config/database.php';

$container = [
    'config' => $config,
    'db' => $pdo,
];
