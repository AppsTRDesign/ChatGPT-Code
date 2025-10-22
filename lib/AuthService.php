<?php
require_once __DIR__ . '/helpers.php';

class AuthService
{
    public function attempt(string $username, string $password): bool
    {
        $stmt = db()->prepare('SELECT id, username, password FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        session_start();
        $_SESSION['admin'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
        ];
        return true;
    }

    public function logout(): void
    {
        session_start();
        session_destroy();
    }

    public function ensureDefaultAdmin(): void
    {
        $stmt = db()->query('SELECT COUNT(*) AS total FROM admin_users');
        $total = (int)$stmt->fetchColumn();
        if ($total === 0) {
            $insert = db()->prepare('INSERT INTO admin_users (username, password, created_at) VALUES (?, ?, NOW())');
            $insert->execute([
                'admin',
                password_hash('admin', PASSWORD_DEFAULT),
            ]);
        }
    }
}
