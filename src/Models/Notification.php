<?php

declare(strict_types=1);

namespace App\Models;

class Notification extends Model
{
    public static function create(array $data): array
    {
        $stmt = self::db()->prepare('INSERT INTO notifications (user_id, title, message, link, channel) VALUES (:user_id, :title, :message, :link, :channel)');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'message' => $data['message'],
            'link' => $data['link'] ?? null,
            'channel' => $data['channel'] ?? 'panel',
        ]);

        return self::find((int)self::db()->lastInsertId());
    }

    public static function forUser(int $userId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        $stmt = self::db()->query('SELECT notifications.*, users.name AS user_name FROM notifications INNER JOIN users ON users.id = notifications.user_id ORDER BY notifications.created_at DESC');
        return $stmt->fetchAll();
    }

    public static function statsForUser(int $userId): array
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS total, SUM(clicked) AS clicked, SUM(dismissed) AS dismissed FROM notification_stats WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $stats = $stmt->fetch();
        return $stats ?: ['total' => 0, 'clicked' => 0, 'dismissed' => 0];
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM notifications WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $notification = $stmt->fetch();
        return $notification ?: null;
    }

    public static function templates(): array
    {
        $templates = [];
        for ($i = 1; $i <= 10; $i++) {
            $html = file_get_contents(__DIR__ . "/../../resources/templates/template{$i}.html") ?: '';
            $html = str_replace('${i}', (string) $i, $html);
            $templates[] = [
                'id' => $i,
                'name' => "Şablon {$i}",
                'html' => $html,
            ];
        }
        return $templates;
    }
}
