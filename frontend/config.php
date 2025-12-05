<?php
// Frontend configuration for database access.
// Uses same environment variables as the ingestion API when available.

define('DB_DSN', getenv('MAPS_API_DSN') ?: 'mysql:host=localhost;dbname=maps;charset=utf8mb4');
define('DB_USER', getenv('MAPS_API_DB_USER') ?: 'maps_user');
define('DB_PASS', getenv('MAPS_API_DB_PASS') ?: 'change-me');

define('SITE_BASE', getenv('MAPS_FRONT_BASE') ?: 'https://maps.noasoft.org');

define('DEFAULT_PAGE_LIMIT', 20);

define('DEFAULT_MAP_LIMIT', 200);

