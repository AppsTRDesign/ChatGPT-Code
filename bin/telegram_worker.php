#!/usr/bin/env php
<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Services\TelegramService;
use App\Support\Migrator;

require __DIR__ . '/../bootstrap/autoload.php';

Config::loadDefaults([
    'app' => [
        'name' => 'Telegram Automation',
        'env' => 'production',
        'debug' => false,
        'key' => null,
        'base_url' => 'http://localhost',
    ],
    'database' => [
        'driver' => 'sqlite',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => database_path('database.sqlite'),
        'username' => '',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'mail' => [
        'host' => 'localhost',
        'port' => 25,
        'username' => '',
        'password' => '',
        'encryption' => '',
        'from_address' => 'bot@example.com',
        'from_name' => 'Telegram Automation',
    ],
    'rate_limits' => [
        'global' => 60,
        'per_phone' => 30,
        'per_channel' => 15,
    ],
    'telegram' => [
        'api_id' => '',
        'api_hash' => '',
        'session_dir' => storage_path('sessions'),
        'log_dir' => storage_path('logs'),
        'app' => [
            'device_model' => 'NoaSoft Automation Panel',
            'system_version' => 'AlmaLinux 8',
            'lang_code' => 'tr',
        ],
    ],
    'services' => Config::get('services', []),
]);

Config::loadFile();

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
