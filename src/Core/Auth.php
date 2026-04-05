<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\DB;

final class Auth
{
    public static function adminCheck(): bool
    {
        return (bool) ($_SESSION['is_admin'] ?? false);
    }

    public static function attemptAdmin(string $username, string $password): bool
    {
        $defaultUser = env('ADMIN_USER', 'admin');
        $defaultPass = env('ADMIN_PASS', 'admin123');

        if ($username === $defaultUser && hash_equals($defaultPass, $password)) {
            session_regenerate_id(true);
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_login_at'] = gmdate('Y-m-d H:i:s');
            return true;
        }

        return false;
    }

    public static function logoutAdmin(): void
    {
        $_SESSION['is_admin'] = false;
    }

    public static function userId(): ?int
    {
        $id = $_SESSION['user_id'] ?? null;
        return $id ? (int) $id : null;
    }

    public static function loginUser(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_login_at'] = gmdate('Y-m-d H:i:s');
    }

    public static function logoutUser(): void
    {
        unset($_SESSION['user_id'], $_SESSION['user_login_at']);
        session_regenerate_id(true);
    }

    public static function user(): ?array
    {
        $id = self::userId();
        if (!$id) {
            return null;
        }

        $stmt = DB::connection()->prepare('SELECT u.*, c.name AS country_name, c.flag_emoji, ci.name AS city_name FROM users u JOIN countries c ON c.id = u.country_id JOIN cities ci ON ci.id = u.city_id WHERE u.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }
}
