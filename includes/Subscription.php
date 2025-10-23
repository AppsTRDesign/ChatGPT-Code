<?php

namespace App;

use DateTimeImmutable;
use Throwable;

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
        $stmt = Helpers::db()->prepare('SELECT up.*, p.name, p.monthly_limit, p.duration_days AS package_duration, u.email, u.username FROM user_packages up JOIN packages p ON p.id = up.package_id JOIN users u ON u.id = up.user_id WHERE up.user_id = :user_id AND up.status = "active" AND (up.expires_at IS NULL OR up.expires_at > NOW()) ORDER BY up.activated_at DESC LIMIT 1');
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

        $update = $db->prepare('UPDATE user_packages SET status = "active", activated_at = NOW(), expires_at = :expires_at, limit_snapshot = :limit_snapshot, duration_days = :duration_days, threshold_50_notified = 0, threshold_25_notified = 0, threshold_5_notified = 0 WHERE id = :id');
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

    public static function handleUsageThresholds(int $userId): void
    {
        $subscription = self::activeForUser($userId);
        if (!$subscription) {
            return;
        }

        $limit = (int) ($subscription['limit_snapshot'] ?? $subscription['monthly_limit']);
        if ($limit <= 0) {
            return;
        }

        $activatedAt = $subscription['activated_at'] ?? null;
        if (!$activatedAt) {
            return;
        }

        $used = UsageLogger::totalUsageInRange($userId, $activatedAt, $subscription['expires_at'] ?? null);
        $remaining = max(0, $limit - $used);
        if ($remaining <= 0) {
            return;
        }

        $percentRemaining = ($remaining / $limit) * 100;
        $thresholds = [
            50 => 'threshold_50_notified',
            25 => 'threshold_25_notified',
            5 => 'threshold_5_notified',
        ];

        foreach ($thresholds as $percent => $column) {
            if (!empty($subscription[$column])) {
                continue;
            }

            if ($percentRemaining <= $percent) {
                self::notifyThreshold($subscription, $percent, $remaining, $limit, $column);
            }
        }
    }

    private static function notifyThreshold(array $subscription, int $percent, int $remaining, int $limit, string $column): void
    {
        $db = Helpers::db();
        $stmt = $db->prepare("UPDATE user_packages SET {$column} = 1 WHERE id = :id");
        $stmt->execute(['id' => $subscription['id']]);

        $email = $subscription['email'] ?? null;
        if (!$email) {
            return;
        }

        $subject = sprintf('API kullanım limitiniz %% %d seviyesine düştü', $percent);
        $content = '<h1>Limit Uyarısı</h1>'
            . '<p>Aktif paketinizin ' . $limit . ' isteklik kotasından yalnızca ' . $remaining . ' kullanım kaldı.</p>'
            . '<p>Kesintisiz devam etmek için panelden yeni paket satın alabilir veya limitinizi artırabilirsiniz.</p>';
        $body = Mailer::template('API Kullanım Uyarısı', $content);
        Mailer::send($email, $subject, $body, true);
    }

    public static function ensureFreeTier(int $userId): void
    {
        $active = self::activeForUser($userId);
        if ($active) {
            return;
        }

        self::grantFreePackage($userId);
    }

    public static function grantFreePackage(int $userId): void
    {
        $db = Helpers::db();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare('SELECT id FROM packages WHERE name = :name LIMIT 1');
            $stmt->execute(['name' => 'Ücretsiz']);
            $packageId = $stmt->fetchColumn();

            if (!$packageId) {
                $insert = $db->prepare('INSERT INTO packages (name, description, monthly_limit, duration_days, features, price, is_active) VALUES (:name, :description, :monthly_limit, :duration_days, :features, 0, 1)');
                $insert->execute([
                    'name' => 'Ücretsiz',
                    'description' => 'Her ay 100 API isteği içeren ücretsiz paket',
                    'monthly_limit' => 100,
                    'duration_days' => 30,
                    'features' => "100 API isteği\nTemel renk ayarı\nLogo desteği",
                ]);
                $packageId = (int) $db->lastInsertId();
            }

            $existing = $db->prepare('SELECT id FROM user_packages WHERE user_id = :user_id AND package_id = :package_id AND status = "active" AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1');
            $existing->execute([
                'user_id' => $userId,
                'package_id' => $packageId,
            ]);

            if ($existing->fetchColumn()) {
                $db->commit();
                return;
            }

            $expiresAt = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');

            $insertUserPackage = $db->prepare('INSERT INTO user_packages (user_id, package_id, status, payment_method, created_at, activated_at, expires_at, limit_snapshot, duration_days) VALUES (:user_id, :package_id, "active", "bank", NOW(), NOW(), :expires_at, :limit_snapshot, :duration_days)');
            $insertUserPackage->execute([
                'user_id' => $userId,
                'package_id' => $packageId,
                'expires_at' => $expiresAt,
                'limit_snapshot' => 100,
                'duration_days' => 30,
            ]);

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
        }
    }
}
