<?php

namespace App\Models;

class Client extends Model
{
    protected static string $table = 'clients';
    protected static array $fillable = [
        'user_id',
        'name',
        'email',
        'domain',
        'phone',
        'default_language',
        'status',
        'mail_verified_at',
        'logo_path',
        'favicon_path',
        'notes'
    ];

    public static function allWithStats(): array
    {
        $sql = 'SELECT c.*, u.username, u.email AS user_email, u.email_verified_at, u.login_banned_until, u.is_blocked,
                (SELECT COUNT(*) FROM subscriptions s WHERE s.client_id = c.id AND s.status = "active") AS active_tokens,
                (SELECT COUNT(*) FROM notifications n WHERE n.client_id = c.id) AS notification_count,
                (SELECT COUNT(*) FROM api_keys k WHERE k.client_id = c.id AND k.status = "active") AS api_key_count,
                (SELECT COUNT(*) FROM client_packages cp WHERE cp.client_id = c.id AND cp.status = "active") AS active_package_count
                FROM clients c
                INNER JOIN users u ON u.id = c.user_id
                ORDER BY c.created_at DESC';

        $stmt = static::db()->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    public static function findByUser(int $userId): ?array
    {
        return static::first(['user_id' => $userId]);
    }

    public static function count(): int
    {
        $stmt = static::db()->query('SELECT COUNT(*) FROM clients');
        return (int) $stmt->fetchColumn();
    }

    public static function countNewSince(int $days): int
    {
        $sql = 'SELECT COUNT(*) FROM clients WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)';
        $stmt = static::db()->prepare($sql);
        $stmt->execute(['days' => $days]);

        return (int) $stmt->fetchColumn();
    }
}
