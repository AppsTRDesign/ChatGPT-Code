<?php

declare(strict_types=1);

$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require $composerAutoload;
    return;
}

spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $path = __DIR__ . '/src/' . str_replace('App\\', '', $class) . '.php';
    $path = str_replace('\\', '/', $path);
    if (file_exists($path)) {
        require $path;
    }
});
