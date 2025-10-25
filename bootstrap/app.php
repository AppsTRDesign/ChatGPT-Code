<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\Router;

Config::init(__DIR__ . '/../config');
Database::init(Config::get('database'));

return new Router();
