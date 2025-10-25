<?php

declare(strict_types=1);

namespace App\Support;

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user']);
    }

    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function login(array $user): void
    {
        $_SESSION['user'] = $user;
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
    }

    public function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = compact('type', 'message');
    }

    public function consumeFlash(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }
}
