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

    public static function trafficSeries(string $range = 'daily'): array
    {
        $config = self::rangeConfig($range);
        $sql = sprintf(
            'SELECT %1$s AS bucket,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) AS sent,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) AS failed
             FROM notifications
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %2$s)
             GROUP BY %3$s
             ORDER BY bucket ASC',
            $config['expression'],
            $config['interval'],
            $config['group']
        );

        $stmt = Database::pdo()->query($sql);
        $rows = $stmt->fetchAll() ?: [];

        return array_map(static function (array $row) use ($range) {
            $row['bucket'] = self::formatBucket($row['bucket'], $range);
            return $row;
        }, $rows);
    }

    public static function membershipSeries(string $range = 'daily'): array
    {
        $config = self::rangeConfig($range, 'created_at');
        $clientSql = sprintf(
            'SELECT %1$s AS bucket, COUNT(*) AS total
             FROM clients
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %2$s)
             GROUP BY %3$s',
            $config['expression'],
            $config['interval'],
            $config['group']
        );

        $packageConfig = self::rangeConfig($range, 'approved_at');
        $packageSql = sprintf(
            'SELECT %1$s AS bucket, COUNT(*) AS total
             FROM client_packages
             WHERE status = "active" AND approved_at IS NOT NULL AND approved_at >= DATE_SUB(NOW(), INTERVAL %2$s)
             GROUP BY %3$s',
            $packageConfig['expression'],
            $packageConfig['interval'],
            $packageConfig['group']
        );

        $clientRows = Database::pdo()->query($clientSql)->fetchAll() ?: [];
        $packageRows = Database::pdo()->query($packageSql)->fetchAll() ?: [];

        $buckets = [];
        foreach ($clientRows as $row) {
            $buckets[$row['bucket']] = true;
        }
        foreach ($packageRows as $row) {
            $buckets[$row['bucket']] = true;
        }

        ksort($buckets);

        $clientMap = [];
        foreach ($clientRows as $row) {
            $clientMap[$row['bucket']] = (int) $row['total'];
        }

        $packageMap = [];
        foreach ($packageRows as $row) {
            $packageMap[$row['bucket']] = (int) $row['total'];
        }

        $series = [];
        foreach (array_keys($buckets) as $bucket) {
            $series[] = [
                'bucket' => self::formatBucket($bucket, $range),
                'new_members' => $clientMap[$bucket] ?? 0,
                'activated_packages' => $packageMap[$bucket] ?? 0
            ];
        }

        return $series;
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

    protected static function rangeConfig(string $range, string $column = 'created_at'): array
    {
        return match ($range) {
            'weekly' => [
                'expression' => sprintf('DATE_FORMAT(%s, "%%x-W%%v")', $column),
                'interval' => '24 WEEK',
                'group' => sprintf('DATE_FORMAT(%s, "%%x-W%%v")', $column)
            ],
            'monthly' => [
                'expression' => sprintf('DATE_FORMAT(%s, "%%Y-%%m")', $column),
                'interval' => '18 MONTH',
                'group' => sprintf('DATE_FORMAT(%s, "%%Y-%%m")', $column)
            ],
            'yearly' => [
                'expression' => sprintf('DATE_FORMAT(%s, "%%Y")', $column),
                'interval' => '5 YEAR',
                'group' => sprintf('DATE_FORMAT(%s, "%%Y")', $column)
            ],
            default => [
                'expression' => sprintf('DATE_FORMAT(%s, "%%Y-%%m-%%d")', $column),
                'interval' => '30 DAY',
                'group' => sprintf('DATE_FORMAT(%s, "%%Y-%%m-%%d")', $column)
            ],
        };
    }

    protected static function formatBucket(string $bucket, string $range): string
    {
        return match ($range) {
            'weekly' => $bucket,
            default => $bucket,
        };
    }
}
