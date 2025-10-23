<?php

namespace App;

class UsageLogger
{
    public static function log(int $userId, string $endpoint, string $status, ?string $note = null): void
    {
        $stmt = Helpers::db()->prepare('INSERT INTO api_usage_logs (user_id, endpoint, status, note) VALUES (:user_id, :endpoint, :status, :note)');
        $stmt->execute([
            'user_id' => $userId,
            'endpoint' => $endpoint,
            'status' => $status,
            'note' => $note,
        ]);

        if ($status === 'success') {
            Subscription::handleUsageThresholds($userId);
        }
    }

    public static function statsForUser(int $userId): array
    {
        $stmt = Helpers::db()->prepare('SELECT DATE(created_at) as date, COUNT(*) as total FROM api_usage_logs WHERE user_id = :user_id GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function totalUsageInMonth(int $userId): int
    {
        $stmt = Helpers::db()->prepare('SELECT COUNT(*) FROM api_usage_logs WHERE user_id = :user_id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = "success"');
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function totalUsageInRange(int $userId, string $start, ?string $end = null): int
    {
        $query = 'SELECT COUNT(*) FROM api_usage_logs WHERE user_id = :user_id AND status = "success" AND created_at >= :start';
        $params = [
            'user_id' => $userId,
            'start' => $start,
        ];

        if ($end) {
            $query .= ' AND created_at <= :end';
            $params['end'] = $end;
        }

        $stmt = Helpers::db()->prepare($query);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
