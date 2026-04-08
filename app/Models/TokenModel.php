<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class TokenModel
{
    public function create(int $userId, string $tokenHash): string
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO api_tokens(user_id,token_hash,created_at,expires_at) VALUES(:user_id,:token_hash,NOW(),DATE_ADD(NOW(), INTERVAL 30 DAY))'
        );
        $stmt->execute(['user_id' => $userId, 'token_hash' => $tokenHash]);
        return (string) Database::connection()->lastInsertId();
    }

    public function findValidUserId(string $tokenHash): ?int
    {
        $stmt = Database::connection()->prepare(
            'SELECT user_id FROM api_tokens WHERE token_hash = :token_hash AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();
        return $row ? (int) $row['user_id'] : null;
    }
}
