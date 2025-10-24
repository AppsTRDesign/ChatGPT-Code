<?php

namespace App;

use PDO;
use PDOException;
use DateTimeImmutable;
use RuntimeException;

class Auth
{
    private static function startSession(array $user): void
    {
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'email' => $user['email'] ?? null,
            'email_verified' => (int) ($user['email_verified'] ?? 0),
            'is_approved' => (int) ($user['is_approved'] ?? 1),
            'login_blocked' => (int) ($user['login_blocked'] ?? 0),
        ];
    }

    private static function generateUsername(string $seed): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '', $seed));
        if ($base === '') {
            $base = 'user';
        }

        $base = substr($base, 0, 20);
        if ($base === '') {
            $base = 'user';
        }

        $db = Helpers::db();
        $username = $base;
        $counter = 0;

        while (true) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
            $stmt->execute(['username' => $username]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $username;
            }

            $counter++;
            $suffix = (string) $counter;
            $trimmed = substr($base, 0, max(1, 20 - strlen($suffix)));
            $username = $trimmed . $suffix;
        }
    }

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

        if ((int) ($user['login_blocked'] ?? 0) === 1) {
            Helpers::flash('message', 'Hesabınız erişime kapatılmıştır. Lütfen destek ekibimizle iletişime geçin.');
            return false;
        }

        if ((int) ($user['is_approved'] ?? 1) === 0) {
            Helpers::flash('message', 'Hesabınız henüz onaylanmadı. Lütfen yönetici onayını bekleyin.');
            return false;
        }

        if (($user['email_verified'] ?? 0) == 0 && $user['role'] !== 'admin') {
            $_SESSION['pending_verification_user'] = (int) $user['id'];
            Helpers::flash('message', 'E-posta adresinizi doğruladıktan sonra giriş yapabilirsiniz. Doğrulama bağlantısı e-postanıza gönderildi.');
            if (!empty($user['email'])) {
                self::createVerification((int) $user['id'], $user['email']);
            }
            return false;
        }

        self::startSession($user);

        if ($user['role'] === 'client') {
            Subscription::ensureFreeTier((int) $user['id']);
        }

        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function requireRole(string $role): void
    {
        $user = self::user();
        if (!$user || $user['role'] !== $role) {
            redirect('/login');
        }
    }

    public static function register(string $username, string $email, string $password): int|false
    {
        $db = Helpers::db();
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $db->prepare('INSERT INTO users (username, email, password, role, email_verified, verification_sent_at) VALUES (:username, :email, :password, :role, 0, NOW())');
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashed,
                'role' => 'client',
            ]);
            $userId = (int) $db->lastInsertId();
            Subscription::grantFreePackage($userId);
            return $userId;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Helpers::db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function createVerification(int $userId, string $email): bool
    {
        $token = Helpers::randomString(64);
        $db = Helpers::db();
        $db->prepare('DELETE FROM email_verifications WHERE user_id = :user_id')->execute(['user_id' => $userId]);
        $stmt = $db->prepare('INSERT INTO email_verifications (user_id, token, expires_at) VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 24 HOUR))');
        $stmt->execute(['user_id' => $userId, 'token' => $token]);

        $db->prepare('UPDATE users SET verification_token = :token, verification_sent_at = NOW() WHERE id = :id')->execute([
            'token' => $token,
            'id' => $userId,
        ]);

        $verifyUrl = rtrim(BASE_URL, '/') . '/verify?token=' . $token;
        $content = '<h1>Merhaba!</h1>'
            . '<p>Hesabınızı aktifleştirmek için aşağıdaki butona tıklayın:</p>'
            . '<p><a class="btn" href="' . $verifyUrl . '">E-postamı Doğrula</a></p>'
            . '<p>Buton çalışmıyorsa bu bağlantıyı tarayıcınıza yapıştırın: <br><a href="' . $verifyUrl . '">' . $verifyUrl . '</a></p>';

        $body = Mailer::template('E-posta Doğrulama', $content);
        return Mailer::send($email, 'E-posta Doğrulama', $body, true);
    }

    public static function verifyEmail(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $stmt = Helpers::db()->prepare('SELECT * FROM email_verifications WHERE token = :token AND consumed_at IS NULL AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1');
        $stmt->execute(['token' => $token]);
        $verification = $stmt->fetch();
        if (!$verification) {
            return false;
        }

        $db = Helpers::db();
        $db->prepare('UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = :id')->execute(['id' => $verification['user_id']]);
        $db->prepare('UPDATE email_verifications SET consumed_at = NOW() WHERE id = :id')->execute(['id' => $verification['id']]);

        return true;
    }

    public static function resendVerification(int $userId): bool
    {
        $stmt = Helpers::db()->prepare('SELECT email FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $email = $stmt->fetchColumn();
        if (!$email) {
            return false;
        }

        return self::createVerification($userId, $email);
    }

    public static function createPasswordReset(string $email): bool
    {
        $user = self::findByEmail($email);
        if (!$user) {
            return false;
        }

        $token = Helpers::randomString(64);
        $expires = (new DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s');
        $db = Helpers::db();
        $db->prepare('DELETE FROM password_resets WHERE user_id = :user_id')->execute(['user_id' => $user['id']]);
        $stmt = $db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)');
        $stmt->execute([
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expires,
        ]);

        $resetUrl = rtrim(BASE_URL, '/') . '/reset-password?token=' . $token;
        $content = '<h1>Şifre Sıfırlama</h1>'
            . '<p>Yeni şifre belirlemek için aşağıdaki butonu kullanın:</p>'
            . '<p><a class="btn" href="' . $resetUrl . '">Şifreyi Sıfırla</a></p>'
            . '<p>Bağlantı 2 saat süreyle geçerlidir.</p>';

        $body = Mailer::template('Şifre Sıfırlama', $content);
        return Mailer::send($email, 'Şifre Sıfırlama Bağlantısı', $body, true);
    }

    public static function resetPassword(string $token, string $password): bool
    {
        $stmt = Helpers::db()->prepare('SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW() LIMIT 1');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $db = Helpers::db();
        $db->prepare('UPDATE users SET password = :password WHERE id = :id')->execute([
            'password' => $hashed,
            'id' => $row['user_id'],
        ]);
        $db->prepare('DELETE FROM password_resets WHERE user_id = :user_id')->execute(['user_id' => $row['user_id']]);

        return true;
    }

    public static function loginWithFirebase(array $payload, string $provider): bool
    {
        $uid = trim((string) ($payload['uid'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));

        if ($uid === '' && $email === '') {
            return false;
        }

        $db = Helpers::db();
        $user = null;

        if ($uid !== '') {
            $stmt = $db->prepare('SELECT * FROM users WHERE firebase_uid = :uid LIMIT 1');
            $stmt->execute(['uid' => $uid]);
            $user = $stmt->fetch();
        }

        if (!$user && $email !== '') {
            $stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
        }

        $verified = !empty($payload['email_verified']);

        if ($user) {
            $db->prepare('UPDATE users SET firebase_uid = :uid, firebase_provider = :provider, email_verified = CASE WHEN email_verified = 1 THEN 1 ELSE :verified END WHERE id = :id')
                ->execute([
                    'uid' => $uid !== '' ? $uid : ($user['firebase_uid'] ?? null),
                    'provider' => $provider,
                    'verified' => $verified ? 1 : (int) ($user['email_verified'] ?? 0),
                    'id' => $user['id'],
                ]);

            if ($verified) {
                $user['email_verified'] = 1;
            }

            if ($uid !== '') {
                $user['firebase_uid'] = $uid;
            }

            $user['firebase_provider'] = $provider;
        } else {
            $nameSeed = trim((string) ($payload['name'] ?? ''));
            $baseName = $nameSeed !== '' ? $nameSeed : ($email !== '' ? (strstr($email, '@', true) ?: $email) : Helpers::randomString(8));
            $username = self::generateUsername($baseName);
            $randomPassword = password_hash(Helpers::randomString(32), PASSWORD_DEFAULT);

            $stmt = $db->prepare('INSERT INTO users (username, email, password, role, email_verified, firebase_uid, firebase_provider, verification_token, verification_sent_at) VALUES (:username, :email, :password, :role, :verified, :firebase_uid, :firebase_provider, NULL, NULL)');
            $stmt->execute([
                'username' => $username,
                'email' => $email !== '' ? $email : null,
                'password' => $randomPassword,
                'role' => 'client',
                'verified' => $verified ? 1 : 0,
                'firebase_uid' => $uid !== '' ? $uid : null,
                'firebase_provider' => $provider,
            ]);

            $user = [
                'id' => (int) $db->lastInsertId(),
                'username' => $username,
                'email' => $email !== '' ? $email : null,
                'role' => 'client',
                'email_verified' => $verified ? 1 : 0,
                'is_approved' => 1,
                'login_blocked' => 0,
            ];

            Subscription::grantFreePackage($user['id']);
        }

        if ((int) ($user['login_blocked'] ?? 0) === 1) {
            throw new RuntimeException('Hesabınız erişime kapatılmıştır. Lütfen destek ekibiyle iletişime geçin.');
        }

        if ((int) ($user['is_approved'] ?? 1) === 0) {
            throw new RuntimeException('Hesabınız henüz onaylanmadı.');
        }

        self::startSession($user);

        if ($user['role'] === 'client') {
            Subscription::ensureFreeTier((int) $user['id']);
        }

        return true;
    }
}
