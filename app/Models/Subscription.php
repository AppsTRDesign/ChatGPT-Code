<?php

namespace App\Models;

class Subscription extends Model
{
    protected static string $table = 'subscriptions';
    protected static array $fillable = [
        'client_id',
        'token',
        'endpoint',
        'public_key',
        'auth_token',
        'status',
        'city',
        'country',
        'platform',
        'browser',
        'device_model',
        'device_type',
        'ip_address'
    ];

    protected static function transformRecord(array $record): array
    {
        if (isset($record['ip_address']) && $record['ip_address'] !== null) {
            $record['ip_address'] = inet_ntop($record['ip_address']);
        }

        return $record;
    }

    public static function countActive(): int
    {
        $stmt = static::db()->query('SELECT COUNT(*) FROM subscriptions WHERE status = "active"');
        return (int) $stmt->fetchColumn();
    }

    public static function countActiveByClient(int $clientId): int
    {
        $stmt = static::db()->prepare('SELECT COUNT(*) FROM subscriptions WHERE status = "active" AND client_id = :client_id');
        $stmt->execute(['client_id' => $clientId]);

        return (int) $stmt->fetchColumn();
    }

    public static function allForClient(int $clientId): array
    {
        return static::all(['client_id' => $clientId]);
    }

    public static function activeForClient(int $clientId, array $tokens = []): array
    {
        $sql = 'SELECT * FROM subscriptions WHERE client_id = :client_id AND status = "active"';
        $params = ['client_id' => $clientId];

        if ($tokens) {
            $placeholders = [];
            foreach ($tokens as $index => $token) {
                $key = ':token_' . $index;
                $placeholders[] = $key;
                $params['token_' . $index] = $token;
            }

            $sql .= ' AND token IN (' . implode(', ', $placeholders) . ')';
        }

        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);

        return array_map([static::class, 'transformRecord'], $stmt->fetchAll() ?: []);
    }

    public static function upsert(int $clientId, array $attributes): array
    {
        $payload = [
            'client_id' => $clientId,
            'token' => $attributes['token'],
            'endpoint' => $attributes['endpoint'] ?? '',
            'public_key' => $attributes['public_key'] ?? null,
            'auth_token' => $attributes['auth_token'] ?? null,
            'status' => $attributes['status'] ?? 'active',
            'city' => $attributes['city'] ?? null,
            'country' => $attributes['country'] ?? null,
            'platform' => $attributes['platform'] ?? null,
            'browser' => $attributes['browser'] ?? null,
            'device_model' => $attributes['device_model'] ?? null,
            'device_type' => $attributes['device_type'] ?? null,
            'ip_address' => isset($attributes['ip_address']) && $attributes['ip_address'] ? inet_pton($attributes['ip_address']) : null,
        ];

        $sql = 'INSERT INTO subscriptions (client_id, token, endpoint, public_key, auth_token, status, city, country, platform, browser, device_model, device_type, ip_address)'
            . ' VALUES (:client_id, :token, :endpoint, :public_key, :auth_token, :status, :city, :country, :platform, :browser, :device_model, :device_type, :ip_address)'
            . ' ON DUPLICATE KEY UPDATE endpoint = VALUES(endpoint), public_key = VALUES(public_key), auth_token = VALUES(auth_token),'
            . ' status = VALUES(status), city = VALUES(city), country = VALUES(country), platform = VALUES(platform), browser = VALUES(browser),'
            . ' device_model = VALUES(device_model), device_type = VALUES(device_type), ip_address = VALUES(ip_address), updated_at = CURRENT_TIMESTAMP';

        $stmt = static::db()->prepare($sql);
        $stmt->execute($payload);

        $subscription = static::first(['token' => $attributes['token']]);

        return $subscription ?? [];
    }

    public static function findByToken(string $token): ?array
    {
        return static::first(['token' => $token]);
    }
}
