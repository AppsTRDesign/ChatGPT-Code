<?php

namespace App\Models;

class Notification extends Model
{
    protected static string $table = 'notifications';
    protected static array $fillable = [
        'client_id',
        'template_id',
        'title',
        'message',
        'target_url',
        'language',
        'site_id',
        'filters',
        'button_text',
        'button_url',
        'image_path',
        'icon_path',
        'ttl_seconds',
        'status',
        'schedule_at',
        'sent_at',
        'expires_at'
    ];

    protected static function transformRecord(array $record): array
    {
        $record = parent::transformRecord($record);
        if (isset($record['filters']) && is_string($record['filters'])) {
            $decoded = json_decode($record['filters'], true);
            $record['filters'] = is_array($decoded) ? $decoded : [];
        }

        return $record;
    }

    public static function count(): int
    {
        $stmt = static::db()->query('SELECT COUNT(*) AS total FROM notifications');
        return (int) $stmt->fetchColumn();
    }

    public static function countForClient(int $clientId): int
    {
        $stmt = static::db()->prepare('SELECT COUNT(*) FROM notifications WHERE client_id = :client_id');
        $stmt->execute(['client_id' => $clientId]);

        return (int) $stmt->fetchColumn();
    }

    public static function createForClient(int $clientId, array $data): int
    {
        $payload = [
            'client_id' => $clientId,
            'template_id' => $data['template_id'] ?? null,
            'title' => $data['title'] ?? '',
            'message' => $data['message'] ?? '',
            'target_url' => $data['target_url'] ?? null,
            'language' => $data['language'] ?? null,
            'site_id' => $data['site_id'] ?? null,
            'filters' => isset($data['filters']) ? json_encode($data['filters']) : null,
            'button_text' => $data['button_text'] ?? null,
            'button_url' => $data['button_url'] ?? null,
            'image_path' => $data['image_path'] ?? null,
            'icon_path' => $data['icon_path'] ?? null,
            'ttl_seconds' => $data['ttl_seconds'] ?? null,
            'status' => $data['status'] ?? 'queued',
            'schedule_at' => $data['schedule_at'] ?? null,
            'sent_at' => $data['sent_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ];

        return static::create($payload);
    }

    public static function allForClient(int $clientId): array
    {
        return static::all(['client_id' => $clientId]);
    }

    public static function markAsSent(int $notificationId): void
    {
        static::updateById($notificationId, [
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s')
        ]);
    }

    public static function updateStatus(int $notificationId, string $status): void
    {
        static::updateById($notificationId, ['status' => $status]);
    }

    public static function recent(int $limit = 10, ?int $clientId = null): array
    {
        $sql = 'SELECT n.*, COUNT(l.id) AS recipient_count,
                SUM(CASE WHEN l.clicked_at IS NOT NULL THEN 1 ELSE 0 END) AS click_count,
                SUM(CASE WHEN l.opened_at IS NOT NULL THEN 1 ELSE 0 END) AS open_count
                FROM notifications n
                LEFT JOIN notification_logs l ON l.notification_id = n.id';

        $params = [];

        if ($clientId) {
            $sql .= ' WHERE n.client_id = :client_id';
            $params['client_id'] = $clientId;
        }

        $sql .= ' GROUP BY n.id ORDER BY n.created_at DESC LIMIT ' . (int) $limit;

        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);

        return array_map([static::class, 'transformRecord'], $stmt->fetchAll() ?: []);
    }
}
