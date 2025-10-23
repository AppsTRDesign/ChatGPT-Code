<?php

namespace App;

class Subscription
{
    public const STATUS_LABELS = [
        'pending' => 'İşleniyor',
        'awaiting_payment' => 'Ödeme Bekliyor',
        'payment_missing' => 'Eksik Ödeme',
        'rejected' => 'Reddedildi',
        'cancelled' => 'İptal Edildi',
        'active' => 'Aktif',
    ];

    public static function activeForUser(int $userId): ?array
    {
        $stmt = Helpers::db()->prepare('SELECT up.*, p.name, p.monthly_limit, p.duration_days AS package_duration FROM user_packages up JOIN packages p ON p.id = up.package_id WHERE up.user_id = :user_id AND up.status = "active" AND (up.expires_at IS NULL OR up.expires_at > NOW()) ORDER BY up.activated_at DESC LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function requestPurchase(int $userId, int $packageId, string $paymentMethod, ?string $note = null): int
    {
        $status = $paymentMethod === 'bank' ? 'awaiting_payment' : 'pending';

        $stmt = Helpers::db()->prepare('INSERT INTO user_packages (user_id, package_id, status, payment_method, note) VALUES (:user_id, :package_id, :status, :payment_method, :note)');
        $stmt->execute([
            'user_id' => $userId,
            'package_id' => $packageId,
            'status' => $status,
            'payment_method' => $paymentMethod,
            'note' => $note,
        ]);

        return (int) Helpers::db()->lastInsertId();
    }

    public static function activate(int $userPackageId): bool
    {
        $db = Helpers::db();
        $stmt = $db->prepare('SELECT up.id, p.monthly_limit, COALESCE(p.duration_days, 30) AS duration_days FROM user_packages up JOIN packages p ON p.id = up.package_id WHERE up.id = :id');
        $stmt->execute(['id' => $userPackageId]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        $duration = max(1, (int) $row['duration_days']);
        $expiresAt = (new \DateTimeImmutable())->modify('+' . $duration . ' days')->format('Y-m-d H:i:s');

        $update = $db->prepare('UPDATE user_packages SET status = "active", activated_at = NOW(), expires_at = :expires_at, limit_snapshot = :limit_snapshot, duration_days = :duration_days WHERE id = :id');
        return $update->execute([
            'id' => $userPackageId,
            'expires_at' => $expiresAt,
            'limit_snapshot' => $row['monthly_limit'],
            'duration_days' => $duration,
        ]);
    }

    public static function updateStatus(int $userPackageId, string $status): bool
    {
        $allowed = ['pending', 'awaiting_payment', 'payment_missing', 'rejected', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $stmt = Helpers::db()->prepare('UPDATE user_packages SET status = :status, activated_at = NULL, expires_at = NULL, limit_snapshot = NULL, duration_days = NULL WHERE id = :id');
        return $stmt->execute([
            'id' => $userPackageId,
            'status' => $status,
        ]);
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst($status);
    }

    public static function usageLeft(int $userId): ?int
    {
        $subscription = self::activeForUser($userId);
        if (!$subscription) {
            return null;
        }

        $limit = (int) ($subscription['limit_snapshot'] ?? $subscription['monthly_limit']);
        if ($limit <= 0) {
            return null;
        }

        $start = $subscription['activated_at'] ?: null;
        if (!$start) {
            return $limit;
        }

        $end = $subscription['expires_at'] ?? null;
        $used = UsageLogger::totalUsageInRange($userId, $start, $end);

        return max(0, $limit - $used);
    }
}
