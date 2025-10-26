<?php

namespace App\Models;

class ApiUsageLog extends Model
{
    protected static string $table = 'api_usage_logs';
    protected static array $fillable = [
        'client_id',
        'api_key_id',
        'endpoint',
        'status',
        'metadata'
    ];

    protected static function transformRecord(array $record): array
    {
        $record = parent::transformRecord($record);
        $record['metadata'] = $record['metadata'] ? json_decode($record['metadata'], true) : [];

        return $record;
    }

    public static function record(int $clientId, int $apiKeyId, string $endpoint, string $status = 'success', array $metadata = []): void
    {
        $payload = [
            'client_id' => $clientId,
            'api_key_id' => $apiKeyId,
            'endpoint' => $endpoint,
            'status' => $status,
            'metadata' => $metadata ? json_encode($metadata) : null
        ];

        static::create($payload);
    }

    public static function dailyUsage(?int $clientId = null): array
    {
        $conditions = [];
        $params = [];

        if ($clientId) {
            $conditions[] = 'client_id = :client_id';
            $params['client_id'] = $clientId;
        }

        $sql = 'SELECT DATE(created_at) AS day,
                       COUNT(*) AS total,
                       SUM(CASE WHEN status = "error" THEN 1 ELSE 0 END) AS errors
                FROM api_usage_logs';

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 30';

        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public static function summary(?int $clientId = null): array
    {
        $sql = 'SELECT endpoint,
                       COUNT(*) AS total,
                       SUM(CASE WHEN status = "error" THEN 1 ELSE 0 END) AS errors
                FROM api_usage_logs';

        $params = [];
        if ($clientId) {
            $sql .= ' WHERE client_id = :client_id';
            $params['client_id'] = $clientId;
        }

        $sql .= ' GROUP BY endpoint ORDER BY total DESC';

        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }
}
