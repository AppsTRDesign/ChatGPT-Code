<?php

declare(strict_types=1);

use App\Support\Config;

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function asset(string $path): string
{
    $assetBase = rtrim((string) Config::get('app.asset_url', '/assets'), '/');
    return $assetBase . '/' . ltrim($path, '/');
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) Config::get('app.base_url', ''), '/');
    return $base . '/' . ltrim($path, '/');
}
