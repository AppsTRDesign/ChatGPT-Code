<?php
use Core\Config;

$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

spl_autoload_register(function (string $class): void {
    $baseDir = __DIR__ . '/';
    $class = ltrim($class, '\\');

    $namespaceMap = [
        'App\\' => 'app/',
        'Core\\' => 'core/',
        'Helpers\\' => 'helpers/',
    ];

    foreach ($namespaceMap as $prefix => $directory) {
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . $directory . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
            return;
        }
    }

    $file = $baseDir . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

Config::load(__DIR__ . '/config/config.php');

date_default_timezone_set(Config::get('timezone', 'Europe/Istanbul'));

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('LANG_PATH', __DIR__ . '/languages');
define('STORAGE_PATH', __DIR__ . '/storage');
define('BASE_URL', Config::get('base_url', 'https://qrmenu.noasoft.org'));
