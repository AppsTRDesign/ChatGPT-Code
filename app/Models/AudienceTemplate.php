<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class AudienceTemplate extends Model
{
    protected static string $table = 'audience_templates';
    protected static array $fillable = [
        'name',
        'entity_type',
        'description',
        'created_at',
        'updated_at',
    ];

    public static function forType(string $type): array
    {
        $stmt = self::connection()->prepare('SELECT * FROM ' . self::tableName() . ' WHERE `entity_type` = :type ORDER BY `name` ASC');
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll();
    }

    public static function findByName(string $name, string $type): ?array
    {
        $stmt = self::connection()->prepare('SELECT * FROM ' . self::tableName() . ' WHERE `name` = :name AND `entity_type` = :type LIMIT 1');
        $stmt->execute([
            'name' => $name,
            'type' => $type,
        ]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function deleteWithRelations(int $id): void
    {
        $template = self::find($id);
        if (!$template) {
            return;
        }

        if ($template['entity_type'] === 'member') {
            AudienceTemplateMember::deleteByTemplate($id);
        } else {
            AudienceTemplateChannel::deleteByTemplate($id);
        }

        self::delete($id);
    }

    public static function countsByType(string $type): array
    {
        $connection = self::connection();
        if ($type === 'member') {
            $stmt = $connection->query('SELECT template_id, COUNT(*) AS total FROM `audience_template_members` GROUP BY template_id');
        } else {
            $stmt = $connection->query('SELECT template_id, COUNT(*) AS total FROM `audience_template_channels` GROUP BY template_id');
        }

        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['template_id']] = (int) $row['total'];
        }

        return $map;
    }
}
