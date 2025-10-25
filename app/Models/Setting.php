<?php

namespace App\Models;

class Setting extends Model
{
    public static function get(string $group, string $key, $default = null)
    {
        $stmt = static::db()->prepare('SELECT value FROM settings WHERE `group` = :group AND `key` = :key LIMIT 1');
        $stmt->execute(['group' => $group, 'key' => $key]);
        $value = $stmt->fetchColumn();

        return $value ?? $default;
    }

    public static function set(string $group, string $key, $value): bool
    {
        $stmt = static::db()->prepare(
            'INSERT INTO settings (`group`, `key`, `value`) VALUES (:group, :key, :value)
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)' 
        );

        return $stmt->execute([
            'group' => $group,
            'key' => $key,
            'value' => $value
        ]);
    }
}
