<?php

namespace App\Models;

class Setting extends Model
{
    protected static string $table = 'settings';
    protected static array $fillable = ['group', 'key', 'value'];

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
            'INSERT INTO settings (`group`, `key`, `value`) VALUES (:group, :key, :value)'
            . ' ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)' // concatenated to avoid heredoc quoting issues
        );

        return $stmt->execute([
            'group' => $group,
            'key' => $key,
            'value' => $value
        ]);
    }

    public static function getGroup(string $group): array
    {
        $stmt = static::db()->prepare('SELECT `key`, `value` FROM settings WHERE `group` = :group');
        $stmt->execute(['group' => $group]);

        $settings = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $settings[$row['key']] = $row['value'];
        }

        return $settings;
    }

    public static function setGroup(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            static::set($group, $key, $value);
        }
    }
}
