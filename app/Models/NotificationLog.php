<?php

namespace App\Models;

class NotificationLog extends BaseModel
{
    public function create(array $data): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO notification_logs (campaign_id, subscriber_id, status, response_message) VALUES (:campaign_id, :subscriber_id, :status, :response_message)');
        return $stmt->execute([
            'campaign_id' => $data['campaign_id'],
            'subscriber_id' => $data['subscriber_id'],
            'status' => $data['status'],
            'response_message' => $data['response_message'],
        ]);
    }

    public function recent(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare('SELECT l.*, s.endpoint, c.title FROM notification_logs l JOIN subscribers s ON s.id = l.subscriber_id JOIN campaigns c ON c.id = l.campaign_id ORDER BY l.created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
