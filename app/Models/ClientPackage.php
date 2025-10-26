<?php

namespace App\Models;

class ClientPackage extends Model
{
    protected static string $table = 'client_packages';
    protected static array $fillable = [
        'client_id',
        'package_id',
        'status',
        'payment_id',
        'payment_method',
        'amount',
        'currency',
        'note',
        'error_log',
        'auto_approved',
        'requested_at',
        'approved_at',
        'activated_at',
        'expires_at',
        'cancelled_at'
    ];

    public static function forClient(int $clientId): array
    {
        $sql = 'SELECT cp.*, p.name AS package_name, p.slug AS package_slug, p.duration_days, p.monthly_limit, p.site_limit
                FROM client_packages cp
                INNER JOIN packages p ON p.id = cp.package_id
                WHERE cp.client_id = :client_id
                ORDER BY cp.created_at DESC';

        $stmt = static::db()->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll() ?: [];
    }

    public static function allWithRelations(): array
    {
        $sql = 'SELECT cp.*, c.name AS client_name, c.email AS client_email, p.name AS package_name, p.slug AS package_slug
                FROM client_packages cp
                INNER JOIN clients c ON c.id = cp.client_id
                INNER JOIN packages p ON p.id = cp.package_id
                ORDER BY cp.created_at DESC';

        $stmt = static::db()->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    public static function activate(int $id, ?string $expiresAt = null): void
    {
        $payload = [
            'status' => 'active',
            'approved_at' => date('Y-m-d H:i:s'),
            'activated_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
            'cancelled_at' => null,
            'error_log' => null
        ];

        static::updateById($id, $payload);
    }

    public static function reject(int $id, ?string $note = null): void
    {
        static::updateById($id, [
            'status' => 'rejected',
            'approved_at' => date('Y-m-d H:i:s'),
            'note' => $note
        ]);
    }

    public static function cancel(int $id, ?string $note = null): void
    {
        static::updateById($id, [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'note' => $note
        ]);
    }

    public static function markPartial(int $id, ?string $note = null): void
    {
        static::updateById($id, [
            'status' => 'partial',
            'note' => $note,
            'approved_at' => null,
            'activated_at' => null,
            'expires_at' => null
        ]);
    }

    public static function countActive(): int
    {
        $stmt = static::db()->query('SELECT COUNT(*) FROM client_packages WHERE status = "active"');
        return (int) $stmt->fetchColumn();
    }
}
