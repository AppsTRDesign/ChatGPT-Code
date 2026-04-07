<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\MapModel;

final class GeoService
{
    private array $cfg;

    public function __construct(private readonly MapModel $mapModel = new MapModel())
    {
        $this->cfg = config('geo');
    }

    public function resolveClientIp(): ?string
    {
        $trustedProxies = $this->cfg['trusted_proxies'] ?? [];
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        $isTrustedProxy = in_array($remoteAddr, $trustedProxies, true);

        $candidates = [];
        if ($isTrustedProxy && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $candidates[] = trim((string) $_SERVER['HTTP_CF_CONNECTING_IP']);
        }

        if ($isTrustedProxy && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = array_map('trim', explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']));
            foreach ($parts as $part) {
                $candidates[] = $part;
            }
        }

        if ($remoteAddr !== '') {
            $candidates[] = $remoteAddr;
        }

        foreach ($candidates as $ip) {
            if ($this->isPublicIp($ip)) {
                return $ip;
            }
        }

        return null;
    }

    public function detectFromIP(?string $ip): ?array
    {
        if (!$ip) {
            return null;
        }

        $cached = $this->cacheGet($ip);
        if ($cached) {
            return $cached;
        }

        $url = sprintf((string) ($this->cfg['ip_api_url'] ?? ''), rawurlencode($ip));
        if (($this->cfg['ip_api_key'] ?? '') !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'key=' . rawurlencode((string) $this->cfg['ip_api_key']);
        }

        $response = @file_get_contents($url);
        if (!$response) {
            return null;
        }

        $json = json_decode($response, true);
        if (!is_array($json)) {
            return null;
        }

        $result = [
            'country_code' => strtoupper((string) ($json['countryCode'] ?? '')),
            'city' => trim((string) ($json['city'] ?? '')),
            'lat' => isset($json['lat']) ? (float) $json['lat'] : null,
            'lng' => isset($json['lon']) ? (float) $json['lon'] : null,
            'source' => 'ip_api',
        ];

        if ($result['country_code'] === '') {
            return null;
        }

        $this->cacheSet($ip, $result);
        return $result;
    }

    public function detectFromMaxMind(?string $ip): ?array
    {
        if (!$ip || !class_exists('GeoIp2\\Database\\Reader')) {
            return null;
        }

        $dbPath = (string) ($this->cfg['maxmind_db_path'] ?? '');
        if ($dbPath === '' || !file_exists($dbPath)) {
            return null;
        }

        try {
            $reader = new \GeoIp2\Database\Reader($dbPath);
            $city = $reader->city($ip);
            return [
                'country_code' => strtoupper((string) ($city->country->isoCode ?? '')),
                'city' => (string) ($city->city->name ?? ''),
                'lat' => isset($city->location->latitude) ? (float) $city->location->latitude : null,
                'lng' => isset($city->location->longitude) ? (float) $city->location->longitude : null,
                'source' => 'maxmind',
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public function resolveCountry(?string $iso2): ?array
    {
        if (!$iso2) {
            return null;
        }

        $stmt = Database::connection()->prepare('SELECT * FROM regions WHERE region_type = "country" AND (country_code = :iso OR UPPER(country_code) = :iso) LIMIT 1');
        $stmt->execute(['iso' => strtoupper($iso2)]);
        return $stmt->fetch() ?: null;
    }

    public function resolveRegion(int $countryRegionId, ?string $city, ?float $lat, ?float $lng): ?array
    {
        if ($city) {
            $stmt = Database::connection()->prepare('SELECT * FROM regions WHERE (id = :country_id OR parent_country_region_id = :country_id) AND LOWER(name) = LOWER(:city) LIMIT 1');
            $stmt->execute(['country_id' => $countryRegionId, 'city' => $city]);
            $exact = $stmt->fetch();
            if ($exact) {
                return $exact;
            }
        }

        if ($lat !== null && $lng !== null) {
            $sql = 'SELECT *, (6371 * acos(cos(radians(:lat)) * cos(radians(lat)) * cos(radians(lng) - radians(:lng)) + sin(radians(:lat)) * sin(radians(lat)))) AS distance
                    FROM regions WHERE id = :country_id OR parent_country_region_id = :country_id ORDER BY distance ASC LIMIT 1';
            $stmt = Database::connection()->prepare($sql);
            $stmt->execute(['lat' => $lat, 'lng' => $lng, 'country_id' => $countryRegionId]);
            $nearest = $stmt->fetch();
            if ($nearest) {
                return $nearest;
            }
        }

        $stmt = Database::connection()->prepare('SELECT * FROM regions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $countryRegionId]);
        $capital = $stmt->fetch();
        if ($capital) {
            return $capital;
        }

        $regions = $this->mapModel->regions();
        return $regions[0] ?? null;
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function cacheGet(string $ip): ?array
    {
        $path = $this->cacheFile($ip);
        if (!file_exists($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (!is_array($json)) {
            return null;
        }

        if ((int) ($json['expires_at'] ?? 0) < time()) {
            @unlink($path);
            return null;
        }

        return $json['payload'] ?? null;
    }

    private function cacheSet(string $ip, array $payload): void
    {
        $ttl = (int) ($this->cfg['cache_ttl_seconds'] ?? 900);
        $body = json_encode(['expires_at' => time() + $ttl, 'payload' => $payload]);
        @file_put_contents($this->cacheFile($ip), (string) $body);
    }

    private function cacheFile(string $ip): string
    {
        return __DIR__ . '/../cache/geo_' . sha1($ip) . '.json';
    }
}
