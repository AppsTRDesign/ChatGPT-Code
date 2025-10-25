<?php

namespace App\Models;

class ApiKey extends Model
{
    public static function validate(string $key): bool
    {
        $stmt = static::db()->prepare('SELECT id FROM api_keys WHERE api_key = :key AND status = "active"');
        $stmt->execute(['key' => $key]);
        return (bool) $stmt->fetchColumn();
    }
}
