<?php

namespace App\Services;

use Core\Database;
use PDO;

class AuthService
{
    private PDO $db;
    private int $restaurantId;

    public function __construct(int $restaurantId = 1)
    {
        $this->db = Database::connection();
        $this->restaurantId = $restaurantId;
    }

    public function attempt(string $email, string $password): array
    {
        $statement = $this->db->prepare('SELECT id, email, password, name FROM restaurant_users WHERE restaurant_id = ? AND email = ?');
        $statement->execute([$this->restaurantId, strtolower($email)]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            throw new \RuntimeException('Geçersiz e-posta veya şifre.');
        }

        $this->db->prepare('UPDATE restaurant_users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
        ];

        return $_SESSION['user'];
    }

    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
    }

    public function resetPassword(string $email): string
    {
        $statement = $this->db->prepare('SELECT id FROM restaurant_users WHERE restaurant_id = ? AND email = ?');
        $statement->execute([$this->restaurantId, strtolower($email)]);
        $user = $statement->fetch();

        if (!$user) {
            throw new \RuntimeException('Kullanıcı bulunamadı.');
        }

        $newPassword = $this->generatePassword();
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);

        $this->db->prepare('UPDATE restaurant_users SET password = ? WHERE id = ?')->execute([$hash, $user['id']]);

        return $newPassword;
    }

    public function updatePassword(int $userId, string $currentPassword, string $newPassword): void
    {
        $statement = $this->db->prepare('SELECT password FROM restaurant_users WHERE restaurant_id = ? AND id = ?');
        $statement->execute([$this->restaurantId, $userId]);
        $user = $statement->fetch();

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new \RuntimeException('Mevcut şifre yanlış.');
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->db->prepare('UPDATE restaurant_users SET password = ? WHERE id = ?')->execute([$hash, $userId]);
    }

    private function generatePassword(int $length = 10): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $max = strlen($characters) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $max)];
        }

        return $password;
    }
}
