<?php

declare(strict_types=1);

function config(string $name): array
{
    static $cache = [];
    if (isset($cache[$name])) {
        return $cache[$name];
    }

    $path = __DIR__ . '/../Config/' . $name . '.php';
    if (!file_exists($path)) {
        return $cache[$name] = [];
    }

    $value = require $path;
    return $cache[$name] = is_array($value) ? $value : [];
}
