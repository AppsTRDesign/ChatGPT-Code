<?php

namespace App;

use PDO;

class NotificationService
{
    public const ACTION_DELIVERED = 'delivered';
    public const ACTION_CLICKED = 'clicked';
    public const ACTION_DISMISSED = 'dismissed';

    public static function create(int $adminId, string $title, string $message, array $languages, array $platforms, ?string $url, ?string $imagePath): int
    {
        $db = Helpers::db();
        $stmt = $db->prepare('INSERT INTO web_notifications (admin_id, title, message, languages_json, platforms_json, url, image_path, created_at) VALUES (:admin_id, :title, :message, :languages, :platforms, :url, :image_path, NOW())');
        $stmt->execute([
            'admin_id' => $adminId,
            'title' => $title,
            'message' => $message,
            'languages' => $languages ? json_encode(array_values($languages), JSON_UNESCAPED_UNICODE) : null,
            'platforms' => $platforms ? json_encode(array_values($platforms), JSON_UNESCAPED_UNICODE) : null,
            'url' => $url,
            'image_path' => $imagePath,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function fetchForSession(string $sessionKey, ?string $language, ?string $platform, ?int $userId = null): array
    {
        $db = Helpers::db();
        $stmt = $db->prepare('SELECT wn.* FROM web_notifications wn LEFT JOIN web_notification_events we ON we.notification_id = wn.id AND we.session_key = :session WHERE we.id IS NULL ORDER BY wn.id DESC LIMIT 20');
        $stmt->execute(['session' => $sessionKey]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $language = $language ? strtolower(substr($language, 0, 10)) : null;
        $platform = $platform ? strtolower(substr($platform, 0, 20)) : null;
        $results = [];

        foreach ($rows as $row) {
            $allowedLanguages = self::decodeList($row['languages_json']);
            $allowedPlatforms = self::decodeList($row['platforms_json']);

            if ($allowedLanguages && $language) {
                $match = false;
                foreach ($allowedLanguages as $allowed) {
                    $allowed = strtolower($allowed);
                    if ($allowed === '' || str_starts_with($language, $allowed) || str_starts_with($allowed, $language)) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) {
                    continue;
                }
            } elseif ($allowedLanguages && !$language) {
                continue;
            }

            if ($allowedPlatforms) {
                if (!$platform || !in_array($platform, $allowedPlatforms, true)) {
                    continue;
                }
            }

            $results[] = $row;
        }

        foreach ($results as &$row) {
            self::recordEvent((int) $row['id'], $sessionKey, self::ACTION_DELIVERED, [
                'user_id' => $userId,
                'platform' => $platform,
                'language' => $language,
            ]);
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'message' => $row['message'],
                'url' => $row['url'],
                'image_path' => $row['image_path'],
                'created_at' => $row['created_at'],
            ];
        }, $results);
    }

    public static function recordEvent(int $notificationId, string $sessionKey, string $action, array $meta = []): void
    {
        $action = in_array($action, [self::ACTION_DELIVERED, self::ACTION_CLICKED, self::ACTION_DISMISSED], true) ? $action : self::ACTION_DELIVERED;
        $db = Helpers::db();
        $ip = self::clientIp();
        $geo = Geo::lookup($ip);
        $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000);
        $referer = $_SESSION['activity_last_url'] ?? ($_SERVER['HTTP_REFERER'] ?? null);
        $language = isset($meta['language']) && $meta['language'] !== '' ? strtolower(substr((string) $meta['language'], 0, 10)) : null;
        $platform = isset($meta['platform']) && $meta['platform'] !== '' ? strtolower(substr((string) $meta['platform'], 0, 20)) : null;
        $userId = isset($meta['user_id']) ? (int) $meta['user_id'] : null;

        $stmt = $db->prepare('INSERT INTO web_notification_events (notification_id, session_key, user_id, action, ip, user_agent, platform, language, country, city, referer, created_at) VALUES (:notification_id, :session_key, :user_id, :action, :ip, :user_agent, :platform, :language, :country, :city, :referer, NOW()) ON DUPLICATE KEY UPDATE ip = VALUES(ip), user_agent = VALUES(user_agent), platform = VALUES(platform), language = VALUES(language), country = VALUES(country), city = VALUES(city), referer = VALUES(referer), created_at = VALUES(created_at)');
        $stmt->execute([
            'notification_id' => $notificationId,
            'session_key' => $sessionKey,
            'user_id' => $userId,
            'action' => $action,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'platform' => $platform,
            'language' => $language,
            'country' => $geo['country'] ?? null,
            'city' => $geo['city'] ?? null,
            'referer' => $referer,
        ]);
    }

    public static function metrics(string $range = 'weekly', ?int $notificationId = null): array
    {
        $range = in_array($range, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $range : 'weekly';
        $now = new \DateTimeImmutable('now');
        $start = $now->modify('-6 days');
        $groupFormat = '%Y-%m-%d';
        $labelFormat = 'd.m';
        $steps = 7;
        $increment = '+1 day';

        switch ($range) {
            case 'daily':
                $steps = 24;
                $start = $now->setTime((int) $now->format('H'), 0)->modify('-23 hours');
                $groupFormat = '%Y-%m-%d %H:00:00';
                $labelFormat = 'H:i';
                $increment = '+1 hour';
                break;
            case 'monthly':
                $steps = 30;
                $start = $now->setTime(0, 0)->modify('-29 days');
                $groupFormat = '%Y-%m-%d';
                $labelFormat = 'd.m';
                $increment = '+1 day';
                break;
            case 'yearly':
                $steps = 12;
                $start = $now->modify('first day of this month')->setTime(0, 0)->modify('-11 months');
                $groupFormat = '%Y-%m-01';
                $labelFormat = 'm.Y';
                $increment = '+1 month';
                break;
        }

        $db = Helpers::db();
        $params = ['start' => $start->format('Y-m-d H:i:s')];
        $filter = '';
        if ($notificationId) {
            $filter = ' AND notification_id = :notification_id';
            $params['notification_id'] = $notificationId;
        }

        $sql = "SELECT DATE_FORMAT(created_at, '$groupFormat') AS bucket,
                    SUM(action = 'delivered') AS delivered,
                    SUM(action = 'clicked') AS clicked,
                    SUM(action = 'dismissed') AS dismissed
                FROM web_notification_events
                WHERE created_at >= :start$filter
                GROUP BY bucket";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $raw = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $raw[$row['bucket']] = [
                'delivered' => (int) $row['delivered'],
                'clicked' => (int) $row['clicked'],
                'dismissed' => (int) $row['dismissed'],
            ];
        }

        $cursor = $start;
        $result = [];
        for ($i = 0; $i < $steps; $i++) {
            $bucket = $cursor->format(str_contains($groupFormat, '%H') ? 'Y-m-d H:00:00' : (str_contains($groupFormat, '%m-01') ? 'Y-m-01' : 'Y-m-d'));
            $label = $cursor->format($labelFormat);
            $row = $raw[$bucket] ?? ['delivered' => 0, 'clicked' => 0, 'dismissed' => 0];
            $result[] = [
                'label' => $label,
                'delivered' => $row['delivered'],
                'clicked' => $row['clicked'],
                'dismissed' => $row['dismissed'],
            ];
            $cursor = $cursor->modify($increment);
        }

        return array_reverse($result);
    }

    private static function decodeList(?string $json): array
    {
        if (!$json) {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_values(array_filter(array_map(static function ($value) {
            return strtolower(trim((string) $value));
        }, $decoded)));
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
}
