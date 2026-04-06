<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use PDO;

final class AuthSecurityService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::connection();
    }

    public function allow(string $action, string $identifier, string $ip, int $limit, int $windowSeconds, int $blockSeconds): bool
    {
        $now = new \DateTimeImmutable('now');

        $stmt = $this->db->prepare('SELECT id, attempt_count, window_start, blocked_until FROM auth_rate_limits WHERE action_key = :action AND identifier = :identifier AND ip_address = :ip LIMIT 1');
        $stmt->execute(['action' => $action, 'identifier' => $identifier, 'ip' => $ip]);
        $row = $stmt->fetch();

        if (!$row) {
            $ins = $this->db->prepare('INSERT INTO auth_rate_limits (action_key, identifier, ip_address, attempt_count, window_start, blocked_until, updated_at) VALUES (:action,:identifier,:ip,0,NOW(),NULL,NOW())');
            $ins->execute(['action' => $action, 'identifier' => $identifier, 'ip' => $ip]);
            return true;
        }

        if (!empty($row['blocked_until']) && $now < new \DateTimeImmutable((string) $row['blocked_until'])) {
            return false;
        }

        $windowStart = new \DateTimeImmutable((string) $row['window_start']);
        if (($now->getTimestamp() - $windowStart->getTimestamp()) > $windowSeconds) {
            $reset = $this->db->prepare('UPDATE auth_rate_limits SET attempt_count = 0, window_start = NOW(), blocked_until = NULL WHERE id = :id');
            $reset->execute(['id' => $row['id']]);
            return true;
        }

        return ((int) $row['attempt_count']) < $limit;
    }

    public function registerFailure(string $action, string $identifier, string $ip, int $limit, int $blockSeconds): void
    {
        $stmt = $this->db->prepare('SELECT id, attempt_count FROM auth_rate_limits WHERE action_key = :action AND identifier = :identifier AND ip_address = :ip LIMIT 1');
        $stmt->execute(['action' => $action, 'identifier' => $identifier, 'ip' => $ip]);
        $row = $stmt->fetch();

        if (!$row) {
            return;
        }

        $attemptCount = ((int) $row['attempt_count']) + 1;
        $blockedUntil = $attemptCount >= $limit
            ? (new \DateTimeImmutable('now +' . $blockSeconds . ' seconds'))->format('Y-m-d H:i:s')
            : null;

        $update = $this->db->prepare('UPDATE auth_rate_limits SET attempt_count = :attempt_count, blocked_until = :blocked_until, updated_at = NOW() WHERE id = :id');
        $update->execute([
            'attempt_count' => $attemptCount,
            'blocked_until' => $blockedUntil,
            'id' => $row['id'],
        ]);
    }

    public function clearFailures(string $action, string $identifier, string $ip): void
    {
        $stmt = $this->db->prepare('UPDATE auth_rate_limits SET attempt_count = 0, blocked_until = NULL, window_start = NOW(), updated_at = NOW() WHERE action_key = :action AND identifier = :identifier AND ip_address = :ip');
        $stmt->execute(['action' => $action, 'identifier' => $identifier, 'ip' => $ip]);
    }

    public function issuePasswordReset(int $userId, string $ip): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);

        $stmt = $this->db->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, used_at, created_at, ip_address) VALUES (:user_id,:token_hash,DATE_ADD(NOW(), INTERVAL 30 MINUTE),NULL,NOW(),:ip)');
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $hash,
            'ip' => $ip,
        ]);

        return $token;
    }

    public function consumePasswordReset(string $token, string $newPassword): bool
    {
        if (mb_strlen($newPassword) < 8) {
            return false;
        }

        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
        $stmt->execute(['token_hash' => $hash]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $u = $this->db->prepare('UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id');
            $u->execute([
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'id' => $row['user_id'],
            ]);

            $mark = $this->db->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id');
            $mark->execute(['id' => $row['id']]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function findUserIdByEmail(string $email): ?int
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ? (int) $row['id'] : null;
    }
}
