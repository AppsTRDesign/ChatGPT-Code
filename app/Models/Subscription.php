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
        'site_id',
        'language',
        'city',
        'country',
        'platform',
        'browser',
        'device_model',
        'device_type',
        'ip_address',
        'last_seen_at'
    ];

    protected static function transformRecord(array $record): array
    {
        if (isset($record['ip_address']) && $record['ip_address'] !== null) {
            $record['ip_address'] = inet_ntop($record['ip_address']);
        }

        if (isset($record['site_name']) || isset($record['site_domain'])) {
            $name = trim((string) ($record['site_name'] ?? ''));
            $domain = trim((string) ($record['site_domain'] ?? ''));
            $label = $name;

            if ($domain !== '') {
                $label = $label !== '' ? sprintf('%s (%s)', $label, $domain) : $domain;
            }

            $record['site'] = $label !== '' ? $label : null;
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
        $sql = 'SELECT s.*, cs.name AS site_name, cs.domain AS site_domain
                FROM subscriptions s
                LEFT JOIN client_sites cs ON cs.id = s.site_id
                WHERE s.client_id = :client_id';

        $stmt = static::db()->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);

        return array_map([static::class, 'transformRecord'], $stmt->fetchAll() ?: []);
    }

    public static function activeForClient(int $clientId, array $tokens = [], array $filters = []): array
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

        $filterMap = [
            'site_id' => 'site_id',
            'language' => 'language',
            'country' => 'country',
            'city' => 'city',
            'platform' => 'platform',
            'browser' => 'browser',
            'device_type' => 'device_type',
            'device_model' => 'device_model',
        ];

        foreach ($filterMap as $key => $column) {
            if (!isset($filters[$key]) || $filters[$key] === '' || $filters[$key] === null) {
                continue;
            }

            $value = $filters[$key];
            if (is_array($value)) {
                $placeholders = [];
                foreach ($value as $index => $item) {
                    $placeholder = sprintf(':%s_%d', $column, $index);
                    $placeholders[] = $placeholder;
                    $params[sprintf('%s_%d', $column, $index)] = $item;
                }

                if ($placeholders) {
                    $sql .= sprintf(' AND %s IN (%s)', $column, implode(', ', $placeholders));
                }
            } else {
                $sql .= sprintf(' AND %s = :%s', $column, $column);
                $params[$column] = $value;
            }
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
            'site_id' => $attributes['site_id'] ?? null,
            'language' => $attributes['language'] ?? null,
            'city' => $attributes['city'] ?? null,
            'country' => $attributes['country'] ?? null,
            'platform' => $attributes['platform'] ?? null,
            'browser' => $attributes['browser'] ?? null,
            'device_model' => $attributes['device_model'] ?? null,
            'device_type' => $attributes['device_type'] ?? null,
            'ip_address' => isset($attributes['ip_address']) && $attributes['ip_address'] ? inet_pton($attributes['ip_address']) : null,
            'last_seen_at' => $attributes['last_seen_at'] ?? date('Y-m-d H:i:s'),
        ];

        $sql = 'INSERT INTO subscriptions (client_id, token, endpoint, public_key, auth_token, status, site_id, language, city, country, platform, browser, device_model, device_type, ip_address, last_seen_at)'
            . ' VALUES (:client_id, :token, :endpoint, :public_key, :auth_token, :status, :site_id, :language, :city, :country, :platform, :browser, :device_model, :device_type, :ip_address, :last_seen_at)'
            . ' ON DUPLICATE KEY UPDATE endpoint = VALUES(endpoint), public_key = VALUES(public_key), auth_token = VALUES(auth_token),'
            . ' status = VALUES(status), site_id = VALUES(site_id), language = VALUES(language), city = VALUES(city), country = VALUES(country), platform = VALUES(platform), browser = VALUES(browser),'
            . ' device_model = VALUES(device_model), device_type = VALUES(device_type), ip_address = VALUES(ip_address), last_seen_at = VALUES(last_seen_at), updated_at = CURRENT_TIMESTAMP';

        $stmt = static::db()->prepare($sql);
        $stmt->execute($payload);

        $subscription = static::first(['token' => $attributes['token']]);

        return $subscription ?? [];
    }

    public static function findByToken(string $token): ?array
    {
        return static::first(['token' => $token]);
    }

    public static function updateStatus(int $id, string $status): void
    {
        static::updateById($id, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
