<?php

declare(strict_types=1);

namespace App\Models;

class User extends Model
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(array $data): void
    {
        $stmt = self::db()->prepare('INSERT INTO users (name, email, password, role, is_active) VALUES (:name, :email, :password, :role, :is_active)');
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'] ?? 'member',
            'is_active' => $data['is_active'] ?? 0,
        ]);
    }

    public static function countActive(): int
    {
        $stmt = self::db()->query('SELECT COUNT(*) AS total FROM users WHERE is_active = 1');
        return (int)$stmt->fetchColumn();
    }

    public static function all(): array
    {
        $stmt = self::db()->query('SELECT id, name, email, role, is_active, created_at FROM users ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }
}
