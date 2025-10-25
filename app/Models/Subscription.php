<?php

namespace App\Models;

class Subscription extends Model
{
    public static function countActive(): int
    {
        $stmt = static::db()->query('SELECT COUNT(*) FROM subscriptions WHERE status = "active"');
        return (int) $stmt->fetchColumn();
    }

    public static function countActiveByCurrentClient(): int
    {
        // TODO: Filter by authenticated client
        $stmt = static::db()->query('SELECT COUNT(*) FROM subscriptions WHERE status = "active"');
        return (int) $stmt->fetchColumn();
    }
}
