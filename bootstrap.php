<?php
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/';
    $class = ltrim($class, '\\');
    $file = $baseDir . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use Core\\Config;

Config::load(__DIR__ . '/config/config.php');

define('LANG_PATH', __DIR__ . '/languages');
define('STORAGE_PATH', __DIR__ . '/storage');
