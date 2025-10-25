<?php

declare(strict_types=1);

namespace App\Models;

class Package extends Model
{
    public static function all(): array
    {
        $stmt = self::db()->query('SELECT * FROM packages ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public static function allActive(): array
    {
        $stmt = self::db()->query('SELECT * FROM packages WHERE is_active = 1 ORDER BY price ASC');
        return $stmt->fetchAll();
    }
}
