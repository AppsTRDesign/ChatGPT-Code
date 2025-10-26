<?php

namespace App\Models;

class Campaign extends BaseModel
{
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT c.*, COUNT(l.id) AS sent_count FROM campaigns c LEFT JOIN notification_logs l ON l.campaign_id = c.id GROUP BY c.id ORDER BY c.created_at DESC');
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO campaigns (title, message, target_segment, target_tags) VALUES (:title, :message, :target_segment, :target_tags)');
        $stmt->execute([
            'title' => $data['title'],
            'message' => $data['message'],
            'target_segment' => $data['target_segment'] ?? null,
            'target_tags' => $data['target_tags'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
