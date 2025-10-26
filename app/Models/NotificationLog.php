<?php

namespace App\Models;

class NotificationLog extends Model
{
    protected static string $table = 'notification_logs';
    protected static array $fillable = [
        'notification_id',
        'subscription_id',
        'status',
        'error_message',
        'opened_at',
        'clicked_at',
        'closed_at'
    ];

    public static function createMany(int $notificationId, array $subscriptionIds): void
    {
        if (!$subscriptionIds) {
            return;
        }

        $values = [];
        $params = [];

        foreach ($subscriptionIds as $index => $subscriptionId) {
            $values[] = sprintf('( :notification_%1$d, :subscription_%1$d, "pending")', $index);
            $params['notification_' . $index] = $notificationId;
            $params['subscription_' . $index] = $subscriptionId;
        }

        $sql = 'INSERT INTO notification_logs (notification_id, subscription_id, status) VALUES ' . implode(', ', $values);
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
    }

    public static function pendingForToken(int $clientId, string $token): array
    {
        $sql = 'SELECT nl.*, n.title, n.message, n.target_url, n.expires_at, n.id AS notification_id,
                n.button_text, n.button_url, n.image_path, n.icon_path, n.language, n.filters, n.site_id, n.ttl_seconds
                FROM notification_logs nl
                INNER JOIN subscriptions s ON s.id = nl.subscription_id
                INNER JOIN notifications n ON n.id = nl.notification_id
                WHERE s.client_id = :client_id AND s.token = :token AND nl.status = "pending"';

        $stmt = static::db()->prepare($sql);
        $stmt->execute(['client_id' => $clientId, 'token' => $token]);

        return $stmt->fetchAll() ?: [];
    }

    public static function markAsSent(array $logIds): void
    {
        if (!$logIds) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($logIds), '?'));
        $sql = 'UPDATE notification_logs SET status = "sent", updated_at = CURRENT_TIMESTAMP WHERE id IN (' . $placeholders . ')';
        $stmt = static::db()->prepare($sql);
        $stmt->execute($logIds);
    }

    public static function registerEvent(int $notificationId, int $subscriptionId, string $event): void
    {
        $fieldMap = [
            'opened' => 'opened_at',
            'clicked' => 'clicked_at',
            'closed' => 'closed_at'
        ];

        if (!isset($fieldMap[$event])) {
            return;
        }

        $field = $fieldMap[$event];
        $sql = sprintf(
            'UPDATE notification_logs SET %s = COALESCE(%s, CURRENT_TIMESTAMP), status = CASE WHEN :event = "clicked" THEN "sent" ELSE status END WHERE notification_id = :notification_id AND subscription_id = :subscription_id',
            $field,
            $field
        );

        $stmt = static::db()->prepare($sql);
        $stmt->execute([
            'event' => $event,
            'notification_id' => $notificationId,
            'subscription_id' => $subscriptionId
        ]);
    }

    public static function averageClickRateForClient(int $clientId): float
    {
        $sql = 'SELECT
                    SUM(CASE WHEN nl.clicked_at IS NOT NULL THEN 1 ELSE 0 END) AS clicks,
                    COUNT(*) AS total
                FROM notification_logs nl
                INNER JOIN notifications n ON n.id = nl.notification_id
                WHERE n.client_id = :client_id';

        $stmt = static::db()->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);
        $row = $stmt->fetch();

        if (!$row || (int) $row['total'] === 0) {
            return 0.0f;
        }

        return round(((int) $row['clicks'] / (int) $row['total']) * 100, 2);
    }
}
