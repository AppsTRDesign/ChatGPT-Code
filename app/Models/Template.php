<?php

namespace App\Models;

class Template extends Model
{
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
}
