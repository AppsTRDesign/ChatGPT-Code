<?php

namespace App;

use PDO;
use PDOException;

class Auth
{
    public static function login(string $username, string $password): bool
    {
        $db = Helpers::db();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'email' => $user['email'] ?? null,
        ];

        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function requireRole(string $role): void
    {
        $user = self::user();
        if (!$user || $user['role'] !== $role) {
            redirect('/login.php');
        }
    }

    public static function register(string $username, string $email, string $password): bool
    {
        $db = Helpers::db();
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $db->prepare('INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)');
            return $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashed,
                'role' => 'client',
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
