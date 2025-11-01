<?php

namespace App\Services;

use Core\Database;
use Helpers\Mail;
use PDO;

class AuthService
{
    private PDO $db;
    private int $restaurantId;
    private ?SettingsService $settingsService = null;

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
        $settings = $this->settings();
        $mail = $settings->mailSettings();
        $fromEmail = $mail['from_email'] ?: ($mail['notification_email'] ?: strtolower($email));
        $fromName = $mail['from_name'] ?: 'QR Menü';
        $replyTo = $mail['reply_to'] ?: $fromEmail;

        $subject = 'QR Menü Şifre Sıfırlama';
        $body = '<p>Merhaba,</p>' .
            '<p>QR menü yönetim paneli şifreniz sıfırlandı.</p>' .
            '<p><strong>Yeni şifreniz:</strong> ' . htmlspecialchars($newPassword, ENT_QUOTES, 'UTF-8') . '</p>' .
            '<p>Güvenliğiniz için giriş yaptıktan sonra şifrenizi güncellemeyi unutmayın.</p>' .
            '<p>İyi çalışmalar dileriz.</p>';

        $sent = Mail::send(strtolower($email), $subject, $body, [
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'reply_to' => $replyTo,
        ]);

        if (!$sent) {
            throw new \RuntimeException('Şifre e-postası gönderilirken bir hata oluştu.');
        }

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

        if (trim($newPassword) === '') {
            throw new \InvalidArgumentException('Yeni şifre boş olamaz.');
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->db->prepare('UPDATE restaurant_users SET password = ? WHERE id = ?')->execute([$hash, $userId]);
    }

    public function updateProfile(int $userId, string $name, string $email): array
    {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '') {
            throw new \InvalidArgumentException('Ad alanı zorunludur.');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Geçerli bir e-posta adresi girin.');
        }

        $exists = $this->db->prepare('SELECT id FROM restaurant_users WHERE restaurant_id = ? AND email = ? AND id != ?');
        $exists->execute([$this->restaurantId, $email, $userId]);
        if ($exists->fetch()) {
            throw new \RuntimeException('Bu e-posta adresi başka bir kullanıcıya ait.');
        }

        $this->db->prepare('UPDATE restaurant_users SET name = ?, email = ?, updated_at = NOW() WHERE restaurant_id = ? AND id = ?')
            ->execute([$name, $email, $this->restaurantId, $userId]);

        $user = [
            'id' => $userId,
            'email' => $email,
            'name' => $name,
        ];

        $_SESSION['user'] = $user;

        return $user;
    }

    private function settings(): SettingsService
    {
        if ($this->settingsService === null) {
            $this->settingsService = new SettingsService($this->restaurantId);
        }

        return $this->settingsService;
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
