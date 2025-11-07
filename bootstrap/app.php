<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Router;
use App\Core\View;
use App\Core\Database;
use App\Support\Migrator;
use App\Support\Session;

require __DIR__ . '/autoload.php';

Session::start();

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
    'services' => [
        'telegram-worker' => [
            'name' => 'Telegram Kuyruk İşleyici',
            'command' => 'php ' . base_path('bin/telegram_worker.php'),
            'description' => 'Kuyruktaki MTProto mesajlarını gönderir, planlı gönderimleri ve davet işlemlerini yürütür.',
            'status' => 'stopped',
        ],
    ],
]);

Config::loadFile();

Database::boot(Config::get('database'));
Migrator::run();
View::setBasePath(resource_path('views'));

$router = new Router();
require base_path('routes/web.php');

$router->dispatch();
