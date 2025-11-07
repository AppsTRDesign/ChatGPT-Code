<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class AudienceTemplateChannel extends Model
{
    protected static string $table = 'audience_template_channels';
    protected static array $fillable = [
        'template_id',
        'channel_id',
        'created_at',
    ];

    public static function attach(int $templateId, int $channelId): void
    {
        $stmt = self::connection()->prepare('SELECT id FROM `audience_template_channels` WHERE `template_id` = :template AND `channel_id` = :channel LIMIT 1');
        $stmt->execute([
            'template' => $templateId,
            'channel' => $channelId,
        ]);

        if ($stmt->fetch()) {
            return;
        }

        self::create([
            'template_id' => $templateId,
            'channel_id' => $channelId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function deleteByTemplate(int $templateId): void
    {
        $stmt = self::connection()->prepare('DELETE FROM `audience_template_channels` WHERE `template_id` = :template');
        $stmt->execute(['template' => $templateId]);
    }

    public static function deleteByChannel(int $channelId): void
    {
        $stmt = self::connection()->prepare('DELETE FROM `audience_template_channels` WHERE `channel_id` = :channel');
        $stmt->execute(['channel' => $channelId]);
    }

    public static function detach(int $templateId, int $channelId): void
    {
        $stmt = self::connection()->prepare('DELETE FROM `audience_template_channels` WHERE `template_id` = :template AND `channel_id` = :channel');
        $stmt->execute([
            'template' => $templateId,
            'channel' => $channelId,
        ]);
    }

    public static function templatesForChannel(int $channelId): array
    {
        $sql = 'SELECT atc.channel_id, at.id AS template_id, at.name, at.entity_type FROM `audience_template_channels` atc INNER JOIN `audience_templates` at ON atc.template_id = at.id WHERE atc.channel_id = :channel ORDER BY at.name ASC';
        $stmt = self::connection()->prepare($sql);
        $stmt->execute(['channel' => $channelId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function templatesIndex(): array
    {
        $sql = 'SELECT atc.channel_id, at.id AS template_id, at.name FROM `audience_template_channels` atc INNER JOIN `audience_templates` at ON atc.template_id = at.id ORDER BY at.name ASC';
        $stmt = self::connection()->query($sql);
        $map = [];
        if (!$stmt) {
            return $map;
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $channelId = (int) $row['channel_id'];
            $map[$channelId][] = [
                'id' => (int) $row['template_id'],
                'name' => $row['name'],
            ];
        }

        return $map;
    }

    public static function channelsForTemplate(int $templateId): array
    {
        $sql = 'SELECT ct.* FROM `audience_template_channels` atc INNER JOIN `channel_targets` ct ON atc.channel_id = ct.id WHERE atc.template_id = :template ORDER BY ct.updated_at DESC';
        $stmt = self::connection()->prepare($sql);
        $stmt->execute(['template' => $templateId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
