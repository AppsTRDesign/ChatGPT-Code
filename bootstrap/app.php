<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\Migrator;
use App\Core\Router;

Config::init(__DIR__ . '/../config');
Database::init(Config::get('database'));
Migrator::run(__DIR__ . '/../database/migrations');

return new Router();
