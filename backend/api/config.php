<?php
// Basic configuration for the JSON ingestion API.
// Adjust API_TOKEN and DB credentials for your environment.

define('API_TOKEN', getenv('MAPS_API_TOKEN') ?: 'change-me');

// MySQL DSN example (default): 'mysql:host=localhost;dbname=maps;charset=utf8mb4'
// Set MAPS_API_DSN / MAPS_API_DB_USER / MAPS_API_DB_PASS as environment variables or
// edit the values below for your hosting environment.
define('DB_DSN', getenv('MAPS_API_DSN') ?: 'mysql:host=localhost;dbname=maps;charset=utf8mb4');
define('DB_USER', getenv('MAPS_API_DB_USER') ?: 'maps_user');
define('DB_PASS', getenv('MAPS_API_DB_PASS') ?: 'change-me');
