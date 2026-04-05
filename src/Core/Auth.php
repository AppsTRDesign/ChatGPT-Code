<?php

declare(strict_types=1);

namespace App\Core;

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
            $_SESSION['is_admin'] = true;
            return true;
        }

        return false;
    }

    public static function logoutAdmin(): void
    {
        $_SESSION['is_admin'] = false;
    }
}
