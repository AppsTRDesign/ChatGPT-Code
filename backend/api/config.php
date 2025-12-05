<?php
// Basic configuration for the JSON ingestion API.
// Adjust API_TOKEN and DB credentials for your environment.

define('API_TOKEN', getenv('MAPS_API_TOKEN') ?: 'change-me');
// By default we ship an SQLite DSN for portability. Replace with MySQL DSN if desired.
// Example MySQL DSN: 'mysql:host=localhost;dbname=maps;charset=utf8mb4'
define('DB_DSN', getenv('MAPS_API_DSN') ?: 'sqlite:' . __DIR__ . '/../data/maps.db');
define('DB_USER', getenv('MAPS_API_DB_USER') ?: null);
define('DB_PASS', getenv('MAPS_API_DB_PASS') ?: null);
