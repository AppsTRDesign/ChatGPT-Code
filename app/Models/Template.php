<?php

namespace App\Models;

class Template extends Model
{
    protected static string $table = 'templates';
    protected static array $fillable = ['client_id', 'name', 'slug', 'content', 'status'];

    public static function count(): int
    {
        $stmt = static::db()->query('SELECT COUNT(*) FROM templates');
        return (int) $stmt->fetchColumn();
    }

    public static function countActive(): int
    {
        $stmt = static::db()->query('SELECT COUNT(*) FROM templates WHERE status = "active"');
        return (int) $stmt->fetchColumn();
    }

    public static function countAvailableForClient(int $clientId): int
    {
        $stmt = static::db()->prepare('SELECT COUNT(*) FROM templates WHERE status = "active" AND (client_id IS NULL OR client_id = :client_id)');
        $stmt->execute(['client_id' => $clientId]);

        return (int) $stmt->fetchColumn();
    }

    public static function allForClient(int $clientId): array
    {
        $sql = 'SELECT * FROM templates WHERE client_id IS NULL OR client_id = :client_id ORDER BY created_at DESC';
        $stmt = static::db()->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll() ?: [];
    }

    public static function createForClient(?int $clientId, array $attributes): int
    {
        $payload = [
            'client_id' => $attributes['client_id'] ?? $clientId,
            'name' => $attributes['name'] ?? 'Yeni Şablon',
            'slug' => $attributes['slug'] ?? uniqid('template-', false),
            'content' => $attributes['content'] ?? '',
            'status' => $attributes['status'] ?? 'active'
        ];

        return static::create($payload);
    }
}
