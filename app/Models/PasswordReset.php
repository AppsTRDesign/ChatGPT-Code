<?php
declare(strict_types=1);

namespace App\Models;

final class PasswordReset extends Model
{
    protected static string $table = 'password_resets';
    protected static array $fillable = [
        'email',
        'token',
        'created_at',
    ];

    public static function findByToken(string $token): ?array
    {
        $stmt = self::connection()->prepare('SELECT * FROM ' . static::$table . ' WHERE token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function deleteByEmail(string $email): void
    {
        $stmt = self::connection()->prepare('DELETE FROM ' . static::$table . ' WHERE email = :email');
        $stmt->execute(['email' => $email]);
    }
}
