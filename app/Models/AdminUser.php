<?php
declare(strict_types=1);

namespace App\Models;

final class AdminUser extends Model
{
    protected static string $table = 'admin_users';
    protected static array $fillable = [
        'username',
        'password',
        'email',
        'created_at',
        'updated_at',
    ];

    public static function findByUsername(string $username): ?array
    {
        $stmt = self::connection()->prepare('SELECT * FROM ' . static::$table . ' WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function ensureDefaultAdmin(): void
    {
        $stmt = self::connection()->query('SELECT COUNT(*) as total FROM ' . static::$table);
        $count = (int) $stmt->fetchColumn();
        if ($count === 0) {
            self::create([
                'username' => 'admin',
                'password' => password_hash('admin', PASSWORD_DEFAULT),
                'email' => 'admin@example.com',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
