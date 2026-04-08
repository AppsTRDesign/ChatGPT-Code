<?php

declare(strict_types=1);

return [
    'ip_api_url' => env('GEO_IP_API_URL', 'http://ip-api.com/json/%s?fields=status,countryCode,city,lat,lon,query'),
    'ip_api_key' => env('GEO_IP_API_KEY', ''),
    'maxmind_db_path' => env('GEO_MAXMIND_DB_PATH', '/geoDB/GeoLite2-City.mmdb'),
    'fallback_country_id' => (int) env('GEO_FALLBACK_COUNTRY_ID', '1'),
    'trusted_proxies' => array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', '127.0.0.1,::1')))),
    'cache_ttl_seconds' => (int) env('GEO_CACHE_TTL', '900'),
];
