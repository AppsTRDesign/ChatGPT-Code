<?php

namespace App\Models;

class ApiKey extends Model
{
    protected static string $table = 'api_keys';
    protected static array $fillable = ['client_id', 'name', 'api_key', 'permissions', 'status', 'last_used_at'];

    protected static function transformRecord(array $record): array
    {
        $record = parent::transformRecord($record);
        $record['permissions'] = json_decode($record['permissions'] ?? '[]', true) ?: [];
        return $record;
    }

    public static function findActiveByKey(string $key): ?array
    {
        return static::first(['api_key' => $key, 'status' => 'active']);
    }

    public static function generateKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function createForClient(int $clientId, array $attributes): int
    {
        $permissions = $attributes['permissions'] ?? [];
        $payload = [
            'client_id' => $clientId,
            'name' => $attributes['name'] ?? 'API Key',
            'api_key' => $attributes['api_key'] ?? static::generateKey(),
            'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
            'status' => $attributes['status'] ?? 'active'
        ];

        return static::create($payload);
    }

    public static function allForClient(int $clientId): array
    {
        return static::all(['client_id' => $clientId]);
    }

    public static function touchLastUsed(int $id): void
    {
        static::updateById($id, ['last_used_at' => date('Y-m-d H:i:s')]);
    }

    public static function hasPermission(array $apiKey, string $permission): bool
    {
        $permissions = json_decode($apiKey['permissions'] ?? '[]', true);
        if (!is_array($permissions) || !$permissions) {
            return false;
        }

        return in_array($permission, $permissions, true);
    }
}
