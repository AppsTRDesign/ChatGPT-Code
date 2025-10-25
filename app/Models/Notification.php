<?php

namespace App\Models;

class Notification extends Model
{
    public static function count(): int
    {
        $stmt = static::db()->query('SELECT COUNT(*) AS total FROM notifications');
        return (int) $stmt->fetchColumn();
    }

    public static function countByCurrentClient(): int
    {
        // TODO: Filter by authenticated client
        $stmt = static::db()->query('SELECT COUNT(*) AS total FROM notifications');
        return (int) $stmt->fetchColumn();
    }
}
