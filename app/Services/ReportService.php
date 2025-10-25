<?php

namespace App\Services;

use App\Core\Database;

class ReportService
{
    public static function adminDailySummary(int $days = 14): array
    {
        $sql = 'SELECT DATE(created_at) AS day,
                       COUNT(*) AS total,
                       SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) AS sent,
                       SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) AS failed
                FROM notifications
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                GROUP BY DATE(created_at)
                ORDER BY day ASC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['days' => $days]);

        return $stmt->fetchAll() ?: [];
    }

    public static function clientEngagement(int $clientId, int $days = 14): array
    {
        $sql = 'SELECT DATE(n.created_at) AS day,
                       SUM(CASE WHEN nl.opened_at IS NOT NULL THEN 1 ELSE 0 END) AS opens,
                       SUM(CASE WHEN nl.clicked_at IS NOT NULL THEN 1 ELSE 0 END) AS clicks,
                       COUNT(*) AS delivered
                FROM notification_logs nl
                INNER JOIN notifications n ON n.id = nl.notification_id
                WHERE n.client_id = :client_id
                  AND n.created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                GROUP BY DATE(n.created_at)
                ORDER BY day ASC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['client_id' => $clientId, 'days' => $days]);

        return $stmt->fetchAll() ?: [];
    }

    public static function topTemplates(int $clientId, int $limit = 5): array
    {
        $sql = 'SELECT t.name,
                       COUNT(n.id) AS usage_count,
                       SUM(CASE WHEN nl.clicked_at IS NOT NULL THEN 1 ELSE 0 END) AS clicks
                FROM templates t
                LEFT JOIN notifications n ON n.template_id = t.id
                LEFT JOIN notification_logs nl ON nl.notification_id = n.id
                WHERE (t.client_id = :client_id OR t.client_id IS NULL)
                GROUP BY t.id
                ORDER BY usage_count DESC
                LIMIT ' . (int) $limit;

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll() ?: [];
    }
}
