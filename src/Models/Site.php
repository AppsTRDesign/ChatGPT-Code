<?php

declare(strict_types=1);

namespace App\Models;

class Site extends Model
{
    public static function forUser(int $userId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM sites WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
