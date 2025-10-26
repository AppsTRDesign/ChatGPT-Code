<?php

namespace App\Models;

class User extends Model
{
    protected static string $table = 'users';
    protected static array $fillable = [
        'username',
        'email',
        'password',
        'role',
        'provider',
        'firebase_uid',
        'email_verified_at',
        'login_banned_until',
        'is_blocked'
    ];

    public static function findByUsername(string $username): ?array
    {
        $stmt = static::db()->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        return $user ?: null;
    }
}
