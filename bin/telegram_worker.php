#!/usr/bin/env php
<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Services\TelegramService;
use App\Support\Migrator;

require __DIR__ . '/../bootstrap/autoload.php';

Config::loadEnv();
Config::loadDefaults([
    'app.name' => 'Telegram Automation',
    'app.env' => Config::env('APP_ENV', 'production'),
    'app.debug' => filter_var(Config::env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'app.key' => Config::env('APP_KEY'),
    'app.base_url' => rtrim(Config::env('BASE_URL', ''), '/') ?: 'http://localhost',
    'database' => [
        'driver' => Config::env('DB_CONNECTION', 'sqlite'),
        'host' => Config::env('DB_HOST', '127.0.0.1'),
        'port' => (int) Config::env('DB_PORT', 3306),
        'database' => Config::env('DB_DATABASE', database_path('database.sqlite')),
        'username' => Config::env('DB_USERNAME', ''),
        'password' => Config::env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'rate_limits' => [
        'global' => (int) Config::env('RATE_LIMIT_GLOBAL', 60),
        'per_phone' => (int) Config::env('RATE_LIMIT_PER_PHONE', 30),
        'per_channel' => (int) Config::env('RATE_LIMIT_PER_CHANNEL', 15),
    ],
]);

Database::boot(Config::get('database'));
Migrator::run();

$service = new TelegramService();

try {
    $service->runDueDispatchJobs();
    echo '[' . date('c') . "] Gönderim kuyruğu işlendi.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, '[' . date('c') . '] Hata: ' . $e->getMessage() . "\n");
    exit(1);
}
