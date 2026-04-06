<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use PDO;

final class GeoService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::connection();
    }

    public function detectCountryCode(string $ip, array $server = []): string
    {
        $normalizedIp = $this->normalizeIp($ip);
        if ($normalizedIp === null) {
            return $this->fallbackByLanguage($server);
        }

        if ($this->isPrivateIp($normalizedIp)) {
            return $this->fallbackByLanguage($server);
        }

        $cached = $this->cachedCountryCode($normalizedIp);
        if ($cached !== null) {
            return $cached;
        }

        $providers = [
            ['name' => 'ip-api', 'url' => 'http://ip-api.com/json/%s?fields=status,countryCode'],
            ['name' => 'ipwhois', 'url' => 'https://ipwho.is/%s'],
        ];

        foreach ($providers as $provider) {
            $code = $this->fetchCountryCode($provider['url'], $normalizedIp);
            if ($code !== null) {
                $this->storeCache($normalizedIp, $code, $provider['name'], 80, 7);
                return $code;
            }
        }

        $fallback = $this->fallbackByLanguage($server);
        $this->storeCache($normalizedIp, $fallback, 'fallback-language', 30, 1);
        return $fallback;
    }

    private function normalizeIp(string $ip): ?string
    {
        $ip = trim($ip);
        if ($ip === '') {
            return null;
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    private function isPrivateIp(string $ip): bool
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    private function cachedCountryCode(string $ip): ?string
    {
        $stmt = $this->db->prepare('SELECT country_code FROM geoip_cache WHERE ip_address = :ip AND expires_at > NOW() LIMIT 1');
        $stmt->execute(['ip' => $ip]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $code = strtoupper((string) $row['country_code']);
        return strlen($code) === 2 ? $code : null;
    }

    private function fetchCountryCode(string $urlTemplate, string $ip): ?string
    {
        $url = sprintf($urlTemplate, urlencode($ip));
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 2,
                'ignore_errors' => true,
                'header' => "User-Agent: NoaPoliticalWars/1.0\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);
        if (!$response) {
            return null;
        }

        $json = json_decode($response, true);
        if (!is_array($json)) {
            return null;
        }

        if (isset($json['status']) && $json['status'] === 'success' && !empty($json['countryCode'])) {
            $code = strtoupper((string) $json['countryCode']);
            return strlen($code) === 2 ? $code : null;
        }

        if (isset($json['success']) && $json['success'] === true && !empty($json['country_code'])) {
            $code = strtoupper((string) $json['country_code']);
            return strlen($code) === 2 ? $code : null;
        }

        return null;
    }

    private function storeCache(string $ip, string $countryCode, string $source, int $confidence, int $ttlDays): void
    {
        $stmt = $this->db->prepare('INSERT INTO geoip_cache (ip_address, country_code, source_name, confidence, cached_at, expires_at)
            VALUES (:ip, :country_code, :source_name, :confidence, NOW(), DATE_ADD(NOW(), INTERVAL :ttl DAY))
            ON DUPLICATE KEY UPDATE
                country_code = VALUES(country_code),
                source_name = VALUES(source_name),
                confidence = VALUES(confidence),
                cached_at = NOW(),
                expires_at = DATE_ADD(NOW(), INTERVAL :ttl_update DAY)');
        $stmt->bindValue('ip', $ip);
        $stmt->bindValue('country_code', strtoupper($countryCode));
        $stmt->bindValue('source_name', $source);
        $stmt->bindValue('confidence', $confidence, PDO::PARAM_INT);
        $stmt->bindValue('ttl', $ttlDays, PDO::PARAM_INT);
        $stmt->bindValue('ttl_update', $ttlDays, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function fallbackByLanguage(array $server): string
    {
        $lang = strtolower((string) ($server['HTTP_ACCEPT_LANGUAGE'] ?? ''));

        if (str_starts_with($lang, 'tr')) {
            return 'TR';
        }
        if (str_starts_with($lang, 'de')) {
            return 'DE';
        }
        if (str_starts_with($lang, 'ru')) {
            return 'RU';
        }
        if (str_starts_with($lang, 'en-us')) {
            return 'US';
        }

        return 'TR';
    }
}
