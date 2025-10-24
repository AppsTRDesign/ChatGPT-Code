<?php

namespace App;

use PDO;

class Activity
{
    private const SESSION_KEY = 'activity_session_key';

    public static function sessionKey(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(16));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function track(string $area = 'public'): void
    {
        try {
            $sessionKey = self::sessionKey();
            $db = Helpers::db();
            $user = Auth::user();
            $userId = $user ? (int) $user['id'] : null;
            $ip = self::clientIp();
            $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000);
            $platform = self::detectPlatform($userAgent);
            $geo = self::detectGeo();
            $referer = self::resolveReferer();
            $search = self::detectSearch($referer);
            $lastUrl = self::currentUrl($area);
            $_SESSION['activity_last_url'] = $lastUrl;

            $query = <<<SQL
INSERT INTO session_activity (session_key, user_id, ip, user_agent, platform, country, city, referer, search_engine, search_term, last_url, last_seen, created_at)
VALUES (:session_key, :user_id, :ip, :user_agent, :platform, :country, :city, :referer, :search_engine, :search_term, :last_url, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    user_id = VALUES(user_id),
    ip = VALUES(ip),
    user_agent = VALUES(user_agent),
    platform = VALUES(platform),
    country = VALUES(country),
    city = VALUES(city),
    referer = IF(session_activity.referer IS NULL OR session_activity.referer = '', VALUES(referer), session_activity.referer),
    search_engine = IF(session_activity.search_engine IS NULL OR session_activity.search_engine = '', VALUES(search_engine), session_activity.search_engine),
    search_term = IF(session_activity.search_term IS NULL OR session_activity.search_term = '', VALUES(search_term), session_activity.search_term),
    last_url = VALUES(last_url),
    last_seen = NOW()
SQL;
            $stmt = $db->prepare($query);

            $stmt->execute([
                'session_key' => $sessionKey,
                'user_id' => $userId,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'platform' => $platform,
                'country' => $geo['country'] ?? null,
                'city' => $geo['city'] ?? null,
                'referer' => $referer,
                'search_engine' => $search['engine'] ?? null,
                'search_term' => $search['term'] ?? null,
                'last_url' => $lastUrl,
            ]);
        } catch (\Throwable $exception) {
            error_log('Session tracking failed: ' . $exception->getMessage());
        }
    }

    public static function heartbeat(string $area = 'public'): void
    {
        try {
            $sessionKey = self::sessionKey();
            $db = Helpers::db();
            $user = Auth::user();
            $userId = $user ? (int) $user['id'] : null;
            $lastUrl = $_SESSION['activity_last_url'] ?? null;

            $sql = 'UPDATE session_activity SET last_seen = NOW(), user_id = :user_id';
            $params = [
                'user_id' => $userId,
                'session_key' => $sessionKey,
            ];
            if ($lastUrl) {
                $sql .= ', last_url = :last_url';
                $params['last_url'] = $lastUrl;
            }
            $sql .= ' WHERE session_key = :session_key';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                self::track($area);
            }
        } catch (\Throwable $exception) {
            error_log('Heartbeat update failed: ' . $exception->getMessage());
        }
    }

    public static function sessionInfo(): array
    {
        $sessionKey = self::sessionKey();
        try {
            $db = Helpers::db();
            $stmt = $db->prepare('SELECT * FROM session_activity WHERE session_key = :session_key');
            $stmt->execute(['session_key' => $sessionKey]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                self::track();
                $stmt->execute(['session_key' => $sessionKey]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            }
            return $row ?: [];
        } catch (\Throwable $exception) {
            error_log('Session info fetch failed: ' . $exception->getMessage());
            return [];
        }
    }

    public static function logPushEvent(int $campaignId, string $eventType, array $overrides = []): void
    {
        $eventType = strtolower($eventType);
        if (!in_array($eventType, ['delivered', 'viewed', 'clicked'], true)) {
            return;
        }

        $sessionKey = self::sessionKey();
        $user = Auth::user();
        $userId = $user ? (int) $user['id'] : null;
        $info = self::sessionInfo();

        $platform = $overrides['platform'] ?? ($info['platform'] ?? null);
        $ip = $overrides['ip'] ?? ($info['ip'] ?? self::clientIp());
        $country = $overrides['country'] ?? ($info['country'] ?? null);
        $city = $overrides['city'] ?? ($info['city'] ?? null);
        $referer = $overrides['referer'] ?? ($info['referer'] ?? null);
        $searchEngine = $overrides['search_engine'] ?? ($info['search_engine'] ?? null);
        $searchTerm = $overrides['search_term'] ?? ($info['search_term'] ?? null);
        $userAgent = $overrides['user_agent'] ?? ($info['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null));

        try {
            $db = Helpers::db();
            $stmt = $db->prepare('INSERT INTO web_push_events (campaign_id, user_id, session_key, event_type, platform, ip, country, city, referer, search_engine, search_term, user_agent, created_at)
                VALUES (:campaign_id, :user_id, :session_key, :event_type, :platform, :ip, :country, :city, :referer, :search_engine, :search_term, :user_agent, NOW())
                ON DUPLICATE KEY UPDATE
                    platform = VALUES(platform),
                    ip = VALUES(ip),
                    country = VALUES(country),
                    city = VALUES(city),
                    referer = VALUES(referer),
                    search_engine = VALUES(search_engine),
                    search_term = VALUES(search_term),
                    user_agent = VALUES(user_agent),
                    created_at = NOW()');
            $stmt->execute([
                'campaign_id' => $campaignId,
                'user_id' => $userId,
                'session_key' => $sessionKey,
                'event_type' => $eventType,
                'platform' => $platform,
                'ip' => $ip,
                'country' => $country,
                'city' => $city,
                'referer' => $referer,
                'search_engine' => $searchEngine,
                'search_term' => $searchTerm,
                'user_agent' => $userAgent,
            ]);
        } catch (\Throwable $exception) {
            error_log('Push event log failed: ' . $exception->getMessage());
        }
    }

    private static function resolveReferer(): ?string
    {
        if (!isset($_SESSION['activity_referer'])) {
            $referer = isset($_SERVER['HTTP_REFERER']) ? filter_var((string) $_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL) : null;
            $_SESSION['activity_referer'] = $referer ?: null;
        }

        return $_SESSION['activity_referer'] ?? null;
    }

    private static function detectPlatform(?string $userAgent): ?string
    {
        $ua = strtolower($userAgent ?? '');
        if ($ua === '') {
            return null;
        }
        if (str_contains($ua, 'android')) {
            return 'Android';
        }
        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ipod')) {
            return 'iOS';
        }
        if (str_contains($ua, 'windows')) {
            return 'Windows';
        }
        if (str_contains($ua, 'mac os')) {
            return 'macOS';
        }
        if (str_contains($ua, 'linux')) {
            return 'Linux';
        }
        return 'Diğer';
    }

    private static function detectGeo(): array
    {
        $country = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? $_SERVER['GEOIP_COUNTRY_CODE'] ?? $_SERVER['HTTP_X_APPENGINE_COUNTRY'] ?? null;
        $city = $_SERVER['HTTP_CF_IPCITY'] ?? $_SERVER['GEOIP_CITY'] ?? $_SERVER['HTTP_X_APPENGINE_CITY'] ?? null;

        return [
            'country' => $country ?: null,
            'city' => $city ?: null,
        ];
    }

    private static function detectSearch(?string $referer): array
    {
        if (!$referer) {
            return ['engine' => null, 'term' => null];
        }

        $host = parse_url($referer, PHP_URL_HOST);
        if (!$host) {
            return ['engine' => null, 'term' => null];
        }

        $host = strtolower($host);
        $map = [
            'google' => 'q',
            'bing' => 'q',
            'yahoo' => 'p',
            'yandex' => 'text',
            'duckduckgo' => 'q',
            'ecosia' => 'q',
        ];

        foreach ($map as $engine => $param) {
            if (str_contains($host, $engine)) {
                $query = parse_url($referer, PHP_URL_QUERY);
                if ($query) {
                    parse_str($query, $params);
                    $term = $params[$param] ?? null;
                    if ($term) {
                        return [
                            'engine' => ucfirst($engine),
                            'term' => mb_substr($term, 0, 120),
                        ];
                    }
                }
                return [
                    'engine' => ucfirst($engine),
                    'term' => null,
                ];
            }
        }

        return ['engine' => null, 'term' => null];
    }

    private static function clientIp(): ?string
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CF_CONNECTING_IP',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $value = $_SERVER[$key];
                if ($key === 'HTTP_X_FORWARDED_FOR') {
                    $parts = explode(',', $value);
                    $value = trim($parts[0]);
                }
                $value = trim($value);
                if ($value !== '') {
                    return substr($value, 0, 45);
                }
            }
        }

        return null;
    }

    private static function currentUrl(string $area): string
    {
        $scheme = is_https() ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? ($area === 'admin' ? '/admin' : '/');

        return $scheme . $host . $uri;
    }
}
