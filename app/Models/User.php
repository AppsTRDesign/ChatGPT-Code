<?php

namespace App\Models;

class User extends Model
{
    protected static string $table = 'users';
    protected static array $fillable = ['username', 'password', 'role'];

    public static function findByUsername(string $username): ?array
    {
        $stmt = static::db()->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        return $user ?: null;
    }
}
