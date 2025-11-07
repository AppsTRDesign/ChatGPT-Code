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
        'phone_code_hash',
        'two_factor_hint',
        'last_error',
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

    public static function firstReady(): ?array
    {
        $stmt = self::connection()->prepare('SELECT * FROM ' . static::$table . ' WHERE is_active = 1 AND session_status = :status ORDER BY updated_at DESC LIMIT 1');
        $stmt->execute(['status' => 'ready']);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
