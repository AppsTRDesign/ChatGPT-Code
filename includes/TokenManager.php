<?php

namespace App;

class TokenManager
{
    public static function create(int $userId, string $label): string
    {
        $token = Helpers::randomString(64);
        $stmt = Helpers::db()->prepare('INSERT INTO api_tokens (user_id, token, label) VALUES (:user_id, :token, :label)');
        $stmt->execute([
            'user_id' => $userId,
            'token' => $token,
            'label' => $label,
        ]);

        return $token;
    }

    public static function list(int $userId): array
    {
        $stmt = Helpers::db()->prepare('SELECT * FROM api_tokens WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function validate(string $token): ?array
    {
        $stmt = Helpers::db()->prepare('SELECT t.*, u.role, u.username FROM api_tokens t JOIN users u ON u.id = t.user_id WHERE token = :token AND revoked_at IS NULL');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function revoke(int $tokenId, int $userId): bool
    {
        $stmt = Helpers::db()->prepare('UPDATE api_tokens SET revoked_at = NOW() WHERE id = :id AND user_id = :user_id');
        return $stmt->execute(['id' => $tokenId, 'user_id' => $userId]);
    }

    public static function restore(int $tokenId, int $userId): bool
    {
        $stmt = Helpers::db()->prepare('UPDATE api_tokens SET revoked_at = NULL WHERE id = :id AND user_id = :user_id');
        return $stmt->execute(['id' => $tokenId, 'user_id' => $userId]);
    }

    public static function delete(int $tokenId, int $userId): bool
    {
        $stmt = Helpers::db()->prepare('DELETE FROM api_tokens WHERE id = :id AND user_id = :user_id');
        return $stmt->execute(['id' => $tokenId, 'user_id' => $userId]);
    }
}
