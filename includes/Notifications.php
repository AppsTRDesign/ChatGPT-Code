<?php

namespace App;

use PDO;

class Notifications
{
    public static function registerPlayer(?int $userId, string $playerId, ?string $platform = null, array $meta = []): bool
    {
        $playerId = trim($playerId);
        if ($playerId === '') {
            return false;
        }

        try {
            $db = Helpers::db();
            $stmt = $db->prepare('INSERT INTO onesignal_subscriptions (external_id, player_id, platform, language, country, last_active)
                VALUES (:external_id, :player_id, :platform, :language, :country, NOW())
                ON DUPLICATE KEY UPDATE external_id = VALUES(external_id), platform = VALUES(platform), language = VALUES(language),
                    country = VALUES(country), last_active = NOW()');

            $language = isset($meta['language']) ? trim((string) $meta['language']) : null;
            $country = isset($meta['country']) ? trim((string) $meta['country']) : null;

            return $stmt->execute([
                'external_id' => $userId !== null && $userId > 0 ? $userId : null,
                'player_id' => $playerId,
                'platform' => $platform,
                'language' => $language !== '' ? $language : null,
                'country' => $country !== '' ? $country : null,
            ]);
        } catch (\PDOException $exception) {
            error_log('OneSignal registerPlayer failed: ' . $exception->getMessage());
            return false;
        }
    }

    public static function removePlayer(string $playerId): void
    {
        $playerId = trim($playerId);
        if ($playerId === '') {
            return;
        }

        $stmt = Helpers::db()->prepare('DELETE FROM onesignal_subscriptions WHERE player_id = :player_id');
        $stmt->execute(['player_id' => $playerId]);
    }

    public static function playerIds(?array $userIds = null): array
    {
        $db = Helpers::db();

        if ($userIds === null) {
            $stmt = $db->query('SELECT player_id FROM onesignal_subscriptions');
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'player_id');
        }

        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (!$userIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $db->prepare("SELECT player_id FROM onesignal_subscriptions WHERE external_id IN ($placeholders)");
        $stmt->execute($userIds);

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'player_id');
    }

    public static function syncFromOneSignal(): array
    {
        $appId = Settings::onesignalAppId();
        $restKey = Settings::onesignalRestKey();

        if (!$appId || !$restKey) {
            return ['success' => false, 'message' => 'OneSignal ayarları eksik.'];
        }

        $headers = [
            'Authorization: Basic ' . $restKey,
            'Content-Type: application/json',
        ];

        $limit = 300;
        $offset = 0;
        $inserted = 0;
        $updated = 0;
        $total = 0;

        do {
            $url = sprintf('https://onesignal.com/api/v1/players?app_id=%s&limit=%d&offset=%d', urlencode($appId), $limit, $offset);
            $response = self::requestJson($url, $headers);

            if (!$response || !isset($response['players']) || !is_array($response['players'])) {
                return ['success' => false, 'message' => 'OneSignal cihaz listesi alınamadı.'];
            }

            foreach ($response['players'] as $player) {
                if (empty($player['id'])) {
                    continue;
                }

                $result = self::upsertFromSync($player);
                $inserted += $result['inserted'];
                $updated += $result['updated'];
            }

            $count = count($response['players']);
            $total += $count;
            $offset += $limit;
        } while (!empty($response['players']) && count($response['players']) === $limit);

        return [
            'success' => true,
            'message' => sprintf('Toplam %d cihaz eşitlendi (%d yeni, %d güncellendi).', $total, $inserted, $updated),
            'inserted' => $inserted,
            'updated' => $updated,
            'total' => $total,
        ];
    }

    public static function sendPush(array $playerIds, string $title, string $message, array $options = []): array
    {
        $playerIds = array_values(array_unique(array_filter($playerIds)));
        if (!$playerIds) {
            return ['success' => false, 'message' => 'Aktif cihaz bulunamadı.'];
        }

        $appId = Settings::onesignalAppId();
        $restKey = Settings::onesignalRestKey();

        if (!$appId || !$restKey) {
            return ['success' => false, 'message' => 'OneSignal ayarları eksik.'];
        }

        $payload = [
            'app_id' => $appId,
            'include_player_ids' => $playerIds,
            'headings' => ['en' => $title, 'tr' => $title],
            'contents' => ['en' => $message, 'tr' => $message],
        ];

        if (!empty($options['url'])) {
            $payload['url'] = self::absoluteUrl($options['url']);
        }

        if (!empty($options['image'])) {
            $imageUrl = self::absoluteUrl($options['image']);
            $payload['chrome_web_image'] = $imageUrl;
            $payload['chrome_big_picture'] = $imageUrl;
            $payload['big_picture'] = $imageUrl;
        }

        $response = self::postJson('https://onesignal.com/api/v1/notifications', $payload, [
            'Authorization: Basic ' . $restKey,
            'Content-Type: application/json',
        ]);

        if (!$response || !empty($response['errors'])) {
            $messageText = is_array($response['errors'] ?? null) ? implode(', ', $response['errors']) : ($response['errors'] ?? 'Bilinmeyen hata');
            return ['success' => false, 'message' => $messageText];
        }

        return ['success' => true, 'message' => 'Bildirim gönderildi.', 'response' => $response];
    }

    private static function absoluteUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }

    private static function postJson(string $url, array $payload, array $headers = []): ?array
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => array_merge($headers, ['Content-Type: application/json']),
                CURLOPT_POSTFIELDS => $json,
            ]);
            $content = curl_exec($ch);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", array_merge($headers, ['Content-Type: application/json'])),
                    'content' => $json,
                    'timeout' => 15,
                ],
            ]);
            $content = @file_get_contents($url, false, $context);
        }

        if ($content === false || $content === null) {
            return null;
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }

    private static function requestJson(string $url, array $headers = []): ?array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => $headers,
            ]);
            $content = curl_exec($ch);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers),
                    'timeout' => 15,
                ],
            ]);
            $content = @file_get_contents($url, false, $context);
        }

        if ($content === false || $content === null) {
            return null;
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }

    private static function upsertFromSync(array $player): array
    {
        $db = Helpers::db();

        $platform = null;
        if (isset($player['device_type'])) {
            $platform = self::mapPlatform((int) $player['device_type']);
        }

        $externalId = null;
        if (!empty($player['external_user_id']) && is_numeric($player['external_user_id'])) {
            $externalId = (int) $player['external_user_id'];
        }

        $language = isset($player['language']) ? trim((string) $player['language']) : null;
        $country = isset($player['country']) ? trim((string) $player['country']) : null;

        $lastActive = null;
        if (!empty($player['last_active']) && is_numeric($player['last_active'])) {
            $lastActive = date('Y-m-d H:i:s', (int) $player['last_active']);
        }

        $stmt = $db->prepare('INSERT INTO onesignal_subscriptions (external_id, player_id, platform, language, country, last_active)
            VALUES (:external_id, :player_id, :platform, :language, :country, COALESCE(:last_active, NOW()))
            ON DUPLICATE KEY UPDATE external_id = VALUES(external_id), platform = VALUES(platform), language = VALUES(language),
                country = VALUES(country), last_active = COALESCE(VALUES(last_active), last_active)');

        $result = $stmt->execute([
            'external_id' => $externalId,
            'player_id' => trim((string) $player['id']),
            'platform' => $platform,
            'language' => $language !== '' ? $language : null,
            'country' => $country !== '' ? $country : null,
            'last_active' => $lastActive,
        ]);

        if (!$result) {
            return ['inserted' => 0, 'updated' => 0];
        }

        return $stmt->rowCount() > 1 ? ['inserted' => 0, 'updated' => 1] : ['inserted' => 1, 'updated' => 0];
    }

    private static function mapPlatform(int $deviceType): ?string
    {
        return match ($deviceType) {
            0 => 'iOS',
            1 => 'Android',
            2 => 'Amazon',
            3 => 'WindowsPhone',
            4 => 'ChromeApp',
            5 => 'Chrome',
            6 => 'Firefox',
            7 => 'MacOS',
            8 => 'ChromeWebsite',
            9 => 'iOS',
            10 => 'Android',
            11 => 'SMS',
            12 => 'Email',
            13 => 'Huawei',
            default => null,
        };
    }
}
