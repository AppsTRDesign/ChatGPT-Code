<?php
declare(strict_types=1);

namespace App\Models;

final class Setting extends Model
{
    protected static string $table = 'settings';
    protected static array $fillable = ['key', 'value', 'updated_at'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $stmt = self::connection()->prepare('SELECT value FROM ' . static::$table . ' WHERE key = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : (string) $value;
    }

    public static function set(string $key, string $value): void
    {
        $existing = self::get($key);
        if ($existing === null) {
            self::create([
                'key' => $key,
                'value' => $value,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            self::updateByKey($key, $value);
        }
    }

    public static function updateByKey(string $key, string $value): void
    {
        $stmt = self::connection()->prepare('UPDATE ' . static::$table . ' SET value = :value, updated_at = :updated_at WHERE key = :key');
        $stmt->execute([
            'value' => $value,
            'updated_at' => date('Y-m-d H:i:s'),
            'key' => $key,
        ]);
    }

    public static function allAsArray(): array
    {
        $stmt = self::connection()->query('SELECT key, value FROM ' . static::$table);
        $results = $stmt->fetchAll();
        $data = [];
        foreach ($results as $row) {
            $data[$row['key']] = $row['value'];
        }
        return $data;
    }
}
