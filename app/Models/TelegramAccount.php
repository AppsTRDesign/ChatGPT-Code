<?php
declare(strict_types=1);

namespace App\Models;

final class TelegramAccount extends Model
{
    protected static string $table = 'telegram_accounts';
    protected static array $fillable = [
        'phone_number',
        'label',
        'is_active',
        'banned_at',
        'last_seen_at',
        'session_status',
        'created_at',
        'updated_at',
    ];

    public static function findByPhone(string $phone): ?array
    {
        $stmt = self::connection()->prepare('SELECT * FROM ' . static::$table . ' WHERE phone_number = :phone LIMIT 1');
        $stmt->execute(['phone' => $phone]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
