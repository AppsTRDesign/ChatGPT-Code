<?php
$config = require __DIR__ . '/config.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $relative = str_replace('App\\', '', $class);
        $relative = str_replace('\\', DIRECTORY_SEPARATOR, $relative);
        $path = __DIR__ . '/app/' . $relative . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

require __DIR__ . '/app/helpers.php';

App\Services\Container::init($config);
