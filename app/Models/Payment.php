<?php

namespace App\Models;

class Payment extends Model
{
    protected static string $table = 'payments';
    protected static array $fillable = [
        'client_id',
        'iyzico_payment_id',
        'status',
        'amount',
        'currency',
        'method',
        'note',
        'error_message',
        'raw_response'
    ];

    public static function record(int $clientId, array $payload): int
    {
        $data = [
            'client_id' => $clientId,
            'iyzico_payment_id' => $payload['iyzico_payment_id'] ?? ($payload['token'] ?? uniqid('iyzico_', false)),
            'status' => $payload['status'] ?? 'initiated',
            'amount' => $payload['amount'] ?? 0,
            'currency' => $payload['currency'] ?? 'TRY',
            'method' => $payload['method'] ?? 'iyzico',
            'note' => $payload['note'] ?? null,
            'error_message' => $payload['error_message'] ?? null,
            'raw_response' => json_encode($payload)
        ];

        return static::create($data);
    }

    public static function allForClient(int $clientId): array
    {
        return static::all(['client_id' => $clientId]);
    }

    public static function updateStatus(int $paymentId, string $status, ?string $note = null): void
    {
        $payload = [
            'status' => $status,
            'note' => $note
        ];

        if ($status === 'failed') {
            $payload['error_message'] = $note;
        }

        static::updateById($paymentId, $payload);
    }

    public static function sumByStatus(string $status, ?int $days = null): float
    {
        $sql = 'SELECT SUM(amount) FROM payments WHERE status = :status';
        $params = ['status' => $status];

        if ($days) {
            $sql .= ' AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)';
            $params['days'] = $days;
        }

        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);

        return (float) ($stmt->fetchColumn() ?: 0);
    }

    public static function countByStatus(string $status): int
    {
        $stmt = static::db()->prepare('SELECT COUNT(*) FROM payments WHERE status = :status');
        $stmt->execute(['status' => $status]);

        return (int) $stmt->fetchColumn();
    }

    public static function pendingCount(): int
    {
        $stmt = static::db()->query('SELECT COUNT(*) FROM client_packages WHERE status IN ("pending", "partial")');
        return (int) $stmt->fetchColumn();
    }

    public static function revenueSeries(string $range = 'monthly', int $limit = 12): array
    {
        $config = self::rangeConfig($range);
        $sql = sprintf(
            'SELECT DATE_FORMAT(approved_at, "%s") AS bucket, SUM(amount) AS total
             FROM client_packages
             WHERE status = "active" AND approved_at IS NOT NULL AND approved_at >= DATE_SUB(NOW(), INTERVAL %s)
             GROUP BY bucket
             ORDER BY bucket ASC
             LIMIT %d',
            $config['format'],
            $config['interval'],
            $config['limit'] ?? $limit
        );

        $stmt = static::db()->query($sql);
        $rows = $stmt->fetchAll() ?: [];

        return array_map(static function (array $row) use ($range) {
            $row['bucket'] = self::formatBucket($row['bucket'], $range);
            return $row;
        }, $rows);
    }

    protected static function rangeConfig(string $range): array
    {
        return match ($range) {
            'daily' => ['format' => '%Y-%m-%d', 'interval' => '45 DAY', 'limit' => 45],
            'weekly' => ['format' => '%x-W%v', 'interval' => '26 WEEK', 'limit' => 26],
            'yearly' => ['format' => '%Y', 'interval' => '6 YEAR', 'limit' => 6],
            default => ['format' => '%Y-%m', 'interval' => '18 MONTH', 'limit' => 18],
        };
    }

    protected static function formatBucket(string $bucket, string $range): string
    {
        return $bucket;
    }
}
