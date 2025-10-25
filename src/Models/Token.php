<?php

declare(strict_types=1);

namespace App\Models;

class Token extends Model
{
    public static function createForUser(int $userId): array
    {
        $key = bin2hex(random_bytes(32));
        $stmt = self::db()->prepare('INSERT INTO tokens (user_id, token, is_active) VALUES (:user_id, :token, 1)');
        $stmt->execute(['user_id' => $userId, 'token' => $key]);
        return self::findByKey($key);
    }

    public static function forUser(int $userId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM tokens WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function usageForUser(int $userId): array
    {
        $stmt = self::db()->prepare('SELECT usage_date AS day, SUM(call_count) AS calls FROM token_usage WHERE user_id = :user_id GROUP BY usage_date ORDER BY usage_date DESC LIMIT 30');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function findByKey(string $key): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM tokens WHERE token = :token LIMIT 1');
        $stmt->execute(['token' => $key]);
        $token = $stmt->fetch();
        return $token ?: null;
    }

    public static function incrementUsage(int $tokenId): void
    {
        $stmt = self::db()->prepare('UPDATE tokens SET usage_count = usage_count + 1 WHERE id = :id');
        $stmt->execute(['id' => $tokenId]);
        $stmt = self::db()->prepare('INSERT INTO token_usage (token_id, user_id, usage_date, call_count) VALUES (:token_id, (SELECT user_id FROM tokens WHERE id = :token_id), CURRENT_DATE, 1)
            ON DUPLICATE KEY UPDATE call_count = call_count + 1');
        $stmt->execute(['token_id' => $tokenId]);
    }

    public static function apiUsageSummary(): array
    {
        $stmt = self::db()->query('SELECT tokens.token, users.email, tokens.usage_count FROM tokens INNER JOIN users ON users.id = tokens.user_id ORDER BY tokens.usage_count DESC');
        return $stmt->fetchAll();
    }

    public static function countApiCalls(): int
    {
        $stmt = self::db()->query('SELECT SUM(usage_count) FROM tokens');
        return (int)$stmt->fetchColumn();
    }

    public static function countAll(): int
    {
        $stmt = self::db()->query('SELECT COUNT(*) FROM tokens');
        return (int)$stmt->fetchColumn();
    }
}
