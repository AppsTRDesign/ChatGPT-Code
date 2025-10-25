<?php

declare(strict_types=1);

namespace App\Models;

class Purchase extends Model
{
    public static function all(): array
    {
        $stmt = self::db()->query('SELECT purchases.*, users.email, packages.name AS package_name FROM purchases INNER JOIN users ON users.id = purchases.user_id INNER JOIN packages ON packages.id = purchases.package_id ORDER BY purchases.created_at DESC');
        return $stmt->fetchAll();
    }

    public static function countPending(): int
    {
        $stmt = self::db()->query("SELECT COUNT(*) FROM purchases WHERE status = 'pending'");
        return (int)$stmt->fetchColumn();
    }

    public static function sumApproved(): float
    {
        $stmt = self::db()->query("SELECT COALESCE(SUM(amount), 0) FROM purchases WHERE status = 'approved'");
        return (float)$stmt->fetchColumn();
    }

    public static function countFailed(): int
    {
        $stmt = self::db()->query("SELECT COUNT(*) FROM purchases WHERE status = 'failed'");
        return (int)$stmt->fetchColumn();
    }
}
