<?php
// Frontend configuration for database access.
// Uses same environment variables as the ingestion API when available.

define('DB_DSN', getenv('MAPS_API_DSN') ?: 'mysql:host=localhost;dbname=maps;charset=utf8mb4');
define('DB_USER', getenv('MAPS_API_DB_USER') ?: 'maps_user');
define('DB_PASS', getenv('MAPS_API_DB_PASS') ?: 'change-me');

define('SITE_BASE', getenv('MAPS_FRONT_BASE') ?: 'https://maps.noasoft.org');
define('BASE_URL', SITE_BASE);

define('DEFAULT_PAGE_LIMIT', 20);
define('DEFAULT_MAP_LIMIT', 200);

define('HOME_RECENT_LIMIT', (int)(getenv('MAPS_HOME_RECENT_LIMIT') ?: 8));
define('HOME_SEARCH_LIMIT', (int)(getenv('MAPS_HOME_SEARCH_LIMIT') ?: 9));
define('LISTING_PAGE_LIMIT', (int)(getenv('MAPS_LISTING_PAGE_LIMIT') ?: 18));
define('DETAIL_REVIEW_LIMIT', (int)(getenv('MAPS_DETAIL_REVIEW_LIMIT') ?: 10));
define('SEARCH_PAGE_LIMIT', (int)(getenv('MAPS_SEARCH_PAGE_LIMIT') ?: HOME_SEARCH_LIMIT));
define('HOME_SEARCH_SECTION_LIMIT', HOME_SEARCH_LIMIT);
define('SITEMAP_PLACE_LIMIT', (int)(getenv('MAPS_SITEMAP_PLACE_LIMIT') ?: 200));

