<?php

namespace App;

use PDO;

class Notifications
{
    public static function registerPlayer(?int $userId, string $playerId, ?string $platform = null): bool
    {
        $playerId = trim($playerId);
        if ($playerId === '') {
            return false;
        }

        $db = Helpers::db();
        $stmt = $db->prepare('INSERT INTO onesignal_subscriptions (user_id, player_id, platform, last_active) VALUES (:user_id, :player_id, :platform, NOW())
            ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), platform = VALUES(platform), last_active = NOW()');

        return $stmt->execute([
            'user_id' => $userId,
            'player_id' => $playerId,
            'platform' => $platform,
        ]);
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
            $stmt = $db->query('SELECT player_id FROM onesignal_subscriptions WHERE user_id IS NOT NULL');
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'player_id');
        }

        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (!$userIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $db->prepare("SELECT player_id FROM onesignal_subscriptions WHERE user_id IN ($placeholders)");
        $stmt->execute($userIds);

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'player_id');
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
}
