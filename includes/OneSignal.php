<?php

namespace App;

use RuntimeException;
use Throwable;

class OneSignal
{
    private const API_BASE = 'https://onesignal.com/api/v1';

    public static function isEnabled(): bool
    {
        return Settings::onesignalEnabled();
    }

    private static function requireEnabled(): void
    {
        if (!self::isEnabled()) {
            throw new RuntimeException('OneSignal etkin değil.');
        }
    }

    private static function appId(): string
    {
        $appId = Settings::onesignalAppId();
        if (!$appId) {
            throw new RuntimeException('OneSignal uygulama kimliği yapılandırılmamış.');
        }
        return $appId;
    }

    private static function restKey(): string
    {
        $key = Settings::onesignalRestKey();
        if (!$key) {
            throw new RuntimeException('OneSignal API anahtarı yapılandırılmamış.');
        }
        return $key;
    }

    private static function apiRequest(string $method, string $path, array $options = []): array
    {
        self::requireEnabled();

        $method = strtoupper($method);
        $url = rtrim(self::API_BASE, '/') . '/' . ltrim($path, '/');

        $query = $options['query'] ?? [];
        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        $body = $options['json'] ?? null;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array_filter([
                'Authorization: Basic ' . self::restKey(),
                'Content-Type: application/json; charset=utf-8',
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('OneSignal isteği başarısız: ' . $error);
        }

        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        if ($status >= 400) {
            $message = $decoded['errors'][0] ?? $decoded['error'] ?? 'OneSignal isteği başarısız oldu.';
            throw new RuntimeException($message . ' (HTTP ' . $status . ')');
        }

        return $decoded;
    }

    public static function syncSubscribers(int $pageSize = 200): array
    {
        self::requireEnabled();

        if (!Helpers::tableExists('onesignal_subscriptions')) {
            return ['imported' => 0];
        }

        $offset = 0;
        $imported = 0;
        $db = Helpers::db();
        $query = 'INSERT INTO onesignal_subscriptions (player_id, external_id, language, country, city, ip, device_type, device_model, device_os, sdk, last_active, tags_json, created_at, updated_at)
VALUES (:player_id, :external_id, :language, :country, :city, :ip, :device_type, :device_model, :device_os, :sdk, :last_active, :tags_json, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    external_id = VALUES(external_id),
    language = VALUES(language),
    country = VALUES(country),
    city = VALUES(city),
    ip = VALUES(ip),
    device_type = VALUES(device_type),
    device_model = VALUES(device_model),
    device_os = VALUES(device_os),
    sdk = VALUES(sdk),
    last_active = VALUES(last_active),
    tags_json = VALUES(tags_json),
    updated_at = NOW()';
        $stmt = $db->prepare($query);

        do {
        $appId = self::appId();

        $response = self::apiRequest('GET', 'players', [
            'query' => [
                'app_id' => $appId,
                'limit' => $pageSize,
                'offset' => $offset,
            ],
        ]);
            $players = $response['players'] ?? [];
            if (!$players) {
                break;
            }

            foreach ($players as $player) {
                if (empty($player['id'])) {
                    continue;
                }
                $tags = $player['tags'] ?? null;
                $city = null;
                if (is_array($tags)) {
                    $city = $tags['city'] ?? $tags['City'] ?? $tags['CITY'] ?? null;
                    if (is_array($city)) {
                        $city = reset($city);
                    }
                    $city = $city !== null ? (string) $city : null;
                }
                $stmt->execute([
                    'player_id' => (string) $player['id'],
                    'external_id' => isset($player['external_user_id']) ? (string) $player['external_user_id'] : null,
                    'language' => isset($player['language']) ? substr((string) $player['language'], 0, 10) : null,
                    'country' => isset($player['country']) ? substr((string) $player['country'], 0, 10) : null,
                    'city' => $city ? mb_substr($city, 0, 120) : null,
                    'ip' => isset($player['ip']) ? substr((string) $player['ip'], 0, 45) : null,
                    'device_type' => self::mapDeviceType($player['device_type'] ?? null),
                    'device_model' => isset($player['device_model']) ? substr((string) $player['device_model'], 0, 120) : null,
                    'device_os' => isset($player['device_os']) ? substr((string) $player['device_os'], 0, 60) : null,
                    'sdk' => isset($player['sdk']) ? substr((string) $player['sdk'], 0, 60) : null,
                    'last_active' => isset($player['last_active']) && is_numeric($player['last_active'])
                        ? date('Y-m-d H:i:s', (int) $player['last_active'])
                        : null,
                    'tags_json' => $tags ? json_encode($tags, JSON_UNESCAPED_UNICODE) : null,
                ]);
                $imported++;
            }

            $offset += $pageSize;
        } while (count($players) === $pageSize);

        return ['imported' => $imported];
    }

    private static function mapDeviceType(mixed $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $map = [
            0 => 'iOS',
            1 => 'Android',
            2 => 'Amazon',
            3 => 'Windows Phone',
            4 => 'Chrome',
            5 => 'Chrome',
            6 => 'Chrome',
            7 => 'Amazon',
            8 => 'Safari',
            9 => 'Firefox',
            10 => 'MacOS',
            11 => 'Chrome Web',
            12 => 'Android Chrome',
            13 => 'Android WebView',
            14 => 'Edge',
        ];
        if (is_int($type)) {
            return $map[$type] ?? 'Diğer';
        }
        if (is_numeric($type)) {
            $key = (int) $type;
            return $map[$key] ?? 'Diğer';
        }
        return (string) $type;
    }

    public static function send(array $payload): array
    {
        if (!isset($payload['headings']) || !isset($payload['contents'])) {
            throw new RuntimeException('OneSignal bildirimi eksik parametre içeriyor.');
        }

        $payload['app_id'] = self::appId();
        return self::apiRequest('POST', 'notifications', [
            'json' => $payload,
        ]);
    }

    public static function fetchNotification(string $notificationId): ?array
    {
        try {
            return self::apiRequest('GET', 'notifications/' . rawurlencode($notificationId), [
                'query' => [
                    'app_id' => self::appId(),
                ],
            ]);
        } catch (Throwable $exception) {
            error_log('OneSignal bildirim sorgusu başarısız: ' . $exception->getMessage());
            return null;
        }
    }

    public static function fetchNotificationHistory(string $notificationId, string $event): array
    {
        $event = strtolower($event);
        if (!in_array($event, ['sent', 'delivered', 'opened', 'clicked'], true)) {
            throw new RuntimeException('Geçersiz OneSignal bildirim geçmişi olayı.');
        }

        try {
            $response = self::apiRequest('GET', 'notifications/' . rawurlencode($notificationId) . '/history', [
                'query' => [
                    'event' => $event,
                    'app_id' => self::appId(),
                ],
            ]);
            $entries = $response['notifications'] ?? $response['players'] ?? $response['events'] ?? [];
            return is_array($entries) ? $entries : [];
        } catch (Throwable $exception) {
            error_log('OneSignal geçmiş sorgusu başarısız: ' . $exception->getMessage());
            return [];
        }
    }

    public static function refreshCampaignStats(?int $campaignId = null): array
    {
        self::requireEnabled();

        if (!Helpers::tableExists('web_push_campaigns')) {
            return ['updated' => 0];
        }

        $db = Helpers::db();
        $params = [];
        $query = "SELECT id, onesignal_id, target_type FROM web_push_campaigns WHERE onesignal_id IS NOT NULL AND onesignal_id <> ''";
        if ($campaignId !== null) {
            $query .= ' AND id = :id';
            $params['id'] = $campaignId;
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $campaigns = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        if (!$campaigns) {
            return ['updated' => 0];
        }

        $updated = 0;
        foreach ($campaigns as $campaign) {
            $onesignalId = $campaign['onesignal_id'];
            if (!$onesignalId) {
                continue;
            }

            $detail = self::fetchNotification($onesignalId);
            if (!is_array($detail)) {
                continue;
            }

            $stats = [
                'recipients' => (int) ($detail['recipients'] ?? $detail['successful'] ?? 0),
                'successful' => (int) ($detail['successful'] ?? 0),
                'sent' => (int) ($detail['successful'] ?? $detail['recipients'] ?? 0),
                'failed' => (int) ($detail['failed'] ?? 0),
                'errored' => (int) ($detail['errored'] ?? 0),
                'converted' => (int) ($detail['converted'] ?? 0),
                'clicked' => (int) ($detail['clicks'] ?? $detail['converted'] ?? 0),
                'delivered' => (int) ($detail['successful'] ?? 0),
                'opened' => (int) ($detail['opens'] ?? $detail['opened'] ?? $detail['converted'] ?? 0),
            ];

            $status = $stats['successful'] > 0 || $stats['recipients'] > 0 ? 'sent' : 'queued';

            $update = $db->prepare('UPDATE web_push_campaigns SET status = :status, stats_json = :stats_json, target_count = CASE WHEN :recipients > 0 THEN :recipients ELSE target_count END, sent_at = CASE WHEN :sent_status = "sent" AND sent_at IS NULL THEN NOW() ELSE sent_at END WHERE id = :id');
            $update->execute([
                'status' => $status,
                'stats_json' => json_encode($stats, JSON_UNESCAPED_UNICODE),
                'recipients' => $stats['recipients'],
                'sent_status' => $status,
                'id' => (int) $campaign['id'],
            ]);

            self::storeCampaignEvents((int) $campaign['id'], $onesignalId, $stats);
            $updated++;
        }

        return ['updated' => $updated];
    }

    private static function storeCampaignEvents(int $campaignId, string $onesignalId, array $stats): void
    {
        if (!Helpers::tableExists('web_push_events')) {
            return;
        }

        $db = Helpers::db();
        $db->prepare('DELETE FROM web_push_events WHERE campaign_id = :id AND event_type IN ("sent","delivered","opened","clicked")')->execute([
            'id' => $campaignId,
        ]);

        $eventTypes = ['sent', 'delivered', 'opened', 'clicked'];
        $summaryCounts = [];

        foreach ($eventTypes as $event) {
            $history = self::fetchNotificationHistory($onesignalId, $event);
            if (!$history) {
                continue;
            }

            $normalized = self::normalizeHistoryEntries($history);
            if (!$normalized) {
                continue;
            }

            $playerIds = array_values(array_filter(array_unique(array_map(static function (array $row) {
                return $row['player_id'] ?? null;
            }, $normalized))));

            $meta = $playerIds ? self::loadSubscriptionMetadata($playerIds) : [];

            $insert = $db->prepare('INSERT INTO web_push_events (campaign_id, event_type, player_id, country, city, ip, platform, referer, count, created_at) VALUES (:campaign_id, :event_type, :player_id, :country, :city, :ip, :platform, :referer, :count, :created_at)');

            $total = 0;
            foreach ($normalized as $row) {
                $playerId = $row['player_id'] ?? null;
                $metaRow = $playerId && isset($meta[$playerId]) ? $meta[$playerId] : [];
                $count = max(1, (int) ($row['count'] ?? 1));
                $createdAt = $row['created_at'] ?? null;
                if ($createdAt && !preg_match('/^\d{4}-\d{2}-\d{2}/', $createdAt)) {
                    $timestamp = strtotime($createdAt);
                    $createdAt = $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
                }
                if ($createdAt === null) {
                    $createdAt = date('Y-m-d H:i:s');
                }

                $insert->execute([
                    'campaign_id' => $campaignId,
                    'event_type' => $event,
                    'player_id' => $playerId,
                    'country' => $row['country'] ?? ($metaRow['country'] ?? null),
                    'city' => $row['city'] ?? ($metaRow['city'] ?? null),
                    'ip' => $row['ip'] ?? ($metaRow['ip'] ?? null),
                    'platform' => $row['platform'] ?? ($metaRow['device_type'] ?? null),
                    'referer' => $row['referer'] ?? null,
                    'count' => $count,
                    'created_at' => $createdAt,
                ]);
                $total += $count;
            }

            if ($total > 0) {
                $summaryCounts[$event] = ($summaryCounts[$event] ?? 0) + $total;
            }
        }

        if ($summaryCounts) {
            $summaryInsert = $db->prepare('INSERT INTO web_push_events (campaign_id, event_type, player_id, country, city, ip, platform, count, created_at) VALUES (:campaign_id, :event_type, NULL, NULL, NULL, NULL, :platform, :count, NOW())');
            foreach ($summaryCounts as $event => $count) {
                $summaryInsert->execute([
                    'campaign_id' => $campaignId,
                    'event_type' => $event,
                    'platform' => 'Toplam',
                    'count' => $count,
                ]);
            }
        }

        if ($stats) {
            $summaryInsert = $db->prepare('INSERT INTO web_push_events (campaign_id, event_type, player_id, country, city, ip, platform, count, created_at) VALUES (:campaign_id, :event_type, NULL, NULL, NULL, NULL, :platform, :count, NOW())');
            foreach (['sent', 'delivered', 'opened', 'clicked'] as $eventType) {
                if (!isset($summaryCounts[$eventType]) && isset($stats[$eventType]) && (int) $stats[$eventType] > 0) {
                    $summaryInsert->execute([
                        'campaign_id' => $campaignId,
                        'event_type' => $eventType,
                        'platform' => 'Toplam',
                        'count' => (int) $stats[$eventType],
                    ]);
                }
            }
        }
    }

    private static function loadSubscriptionMetadata(array $playerIds): array
    {
        if (!$playerIds) {
            return [];
        }

        if (!Helpers::tableExists('onesignal_subscriptions')) {
            return [];
        }

        $db = Helpers::db();
        $placeholders = implode(',', array_fill(0, count($playerIds), '?'));
        $stmt = $db->prepare('SELECT player_id, country, city, ip, device_type FROM onesignal_subscriptions WHERE player_id IN (' . $placeholders . ')');
        $stmt->execute($playerIds);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $map = [];
        foreach ($rows as $row) {
            $map[$row['player_id']] = $row;
        }
        return $map;
    }

    private static function normalizeHistoryEntries(array $entries): array
    {
        $normalized = [];

        foreach ($entries as $entry) {
            if (isset($entry['data']) && is_array($entry['data'])) {
                $entry = $entry['data'];
            }

            $playerId = self::extractValue($entry, ['player_id', 'id', 'playerId', 'external_user_id']);
            $countRaw = self::extractValue($entry, ['count', 'total']);
            $count = $countRaw !== null && is_numeric($countRaw) ? (int) $countRaw : 1;
            if ($count <= 0) {
                $count = 1;
            }

            $country = self::extractValue($entry, ['country', 'country_code', 'Country']);
            $city = self::extractValue($entry, ['city', 'City', 'state', 'location']);
            $ip = self::extractValue($entry, ['ip', 'ip_address', 'IPAddress']);
            $platform = self::extractValue($entry, ['device_type', 'browser', 'platform']);
            $referer = self::extractValue($entry, ['referer', 'referrer', 'utm_source']);
            $timestamp = self::extractValue($entry, ['event_time', 'occurred_at', 'timestamp', 'created_at']);

            $createdAt = null;
            if ($timestamp !== null && $timestamp !== '') {
                if (is_numeric($timestamp)) {
                    $createdAt = date('Y-m-d H:i:s', (int) $timestamp);
                } else {
                    $time = strtotime((string) $timestamp);
                    $createdAt = $time ? date('Y-m-d H:i:s', $time) : null;
                }
            }

            $normalized[] = [
                'player_id' => $playerId ? substr((string) $playerId, 0, 80) : null,
                'count' => $count,
                'country' => $country ? substr((string) $country, 0, 10) : null,
                'city' => $city ? mb_substr((string) $city, 0, 120) : null,
                'ip' => $ip ? substr((string) $ip, 0, 45) : null,
                'platform' => $platform ? mb_substr((string) $platform, 0, 120) : null,
                'referer' => $referer ? mb_substr((string) $referer, 0, 255) : null,
                'created_at' => $createdAt,
            ];
        }

        return $normalized;
    }

    private static function extractValue(array $entry, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $entry)) {
                $value = $entry[$key];
            } else {
                $value = null;
                foreach ($entry as $k => $v) {
                    if (strcasecmp((string) $k, (string) $key) === 0) {
                        $value = $v;
                        break;
                    }
                }
            }

            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $value = reset($value);
            }

            if (is_scalar($value)) {
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }
}
