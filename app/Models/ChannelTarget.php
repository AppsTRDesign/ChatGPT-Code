<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class ChannelTarget extends Model
{
    protected static string $table = 'channel_targets';
    protected static array $fillable = [
        'telegram_id',
        'access_hash',
        'username',
        'title',
        'type',
        'is_public',
        'extra',
        'created_at',
        'updated_at',
    ];

    public static function findByTelegramId(string $telegramId, string $type): ?array
    {
        $stmt = self::connection()->prepare('SELECT * FROM `channel_targets` WHERE `telegram_id` = :id AND `type` = :type LIMIT 1');
        $stmt->execute([
            'id' => $telegramId,
            'type' => $type,
        ]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function upsert(array $data): int
    {
        $existing = self::findByTelegramId((string) $data['telegram_id'], (string) $data['type']);
        $payload = [
            'telegram_id' => (string) $data['telegram_id'],
            'access_hash' => $data['access_hash'] ?? null,
            'username' => $data['username'] ?? null,
            'title' => $data['title'] ?? null,
            'type' => (string) $data['type'],
            'is_public' => isset($data['is_public']) ? (int) $data['is_public'] : 0,
            'extra' => $data['extra'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            self::update((int) $existing['id'], $payload);
            return (int) $existing['id'];
        }

        $payload['created_at'] = $payload['updated_at'];
        return self::create($payload);
    }

    public static function allWithTemplates(): array
    {
        $sql = 'SELECT ct.*, GROUP_CONCAT(at.name ORDER BY at.name SEPARATOR ", ") AS template_names FROM `channel_targets` ct LEFT JOIN `audience_template_channels` atc ON atc.channel_id = ct.id LEFT JOIN `audience_templates` at ON atc.template_id = at.id GROUP BY ct.id ORDER BY ct.updated_at DESC';
        $stmt = self::connection()->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function findWithTemplates(int $id): ?array
    {
        $sql = 'SELECT ct.*, GROUP_CONCAT(at.name ORDER BY at.name SEPARATOR ", ") AS template_names FROM `channel_targets` ct LEFT JOIN `audience_template_channels` atc ON atc.channel_id = ct.id LEFT JOIN `audience_templates` at ON atc.template_id = at.id WHERE ct.id = :id GROUP BY ct.id LIMIT 1';
        $stmt = self::connection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
