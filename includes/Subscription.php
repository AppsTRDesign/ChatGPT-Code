<?php

namespace App;

class Subscription
{
    public static function activeForUser(int $userId): ?array
    {
        $stmt = Helpers::db()->prepare('SELECT up.*, p.name, p.monthly_limit FROM user_packages up JOIN packages p ON p.id = up.package_id WHERE up.user_id = :user_id AND up.status = "active" ORDER BY up.activated_at DESC LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function requestPurchase(int $userId, int $packageId, string $paymentMethod, ?string $note = null): bool
    {
        $stmt = Helpers::db()->prepare('INSERT INTO user_packages (user_id, package_id, status, payment_method, note) VALUES (:user_id, :package_id, "pending", :payment_method, :note)');
        return $stmt->execute([
            'user_id' => $userId,
            'package_id' => $packageId,
            'payment_method' => $paymentMethod,
            'note' => $note,
        ]);
    }

    public static function activate(int $userPackageId): bool
    {
        $stmt = Helpers::db()->prepare('UPDATE user_packages SET status = "active", activated_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $userPackageId]);
    }

    public static function usageLeft(int $userId): ?int
    {
        $subscription = self::activeForUser($userId);
        if (!$subscription) {
            return null;
        }

        $used = UsageLogger::totalUsageInMonth($userId);
        return max(0, (int) $subscription['monthly_limit'] - $used);
    }
}
