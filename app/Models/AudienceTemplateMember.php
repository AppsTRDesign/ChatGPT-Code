<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class AudienceTemplateMember extends Model
{
    protected static string $table = 'audience_template_members';
    protected static array $fillable = [
        'template_id',
        'member_id',
        'created_at',
    ];

    public static function attach(int $templateId, int $memberId): void
    {
        $stmt = self::connection()->prepare('SELECT id FROM `audience_template_members` WHERE `template_id` = :template AND `member_id` = :member LIMIT 1');
        $stmt->execute([
            'template' => $templateId,
            'member' => $memberId,
        ]);

        if ($stmt->fetch()) {
            return;
        }

        self::create([
            'template_id' => $templateId,
            'member_id' => $memberId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function deleteByTemplate(int $templateId): void
    {
        $stmt = self::connection()->prepare('DELETE FROM `audience_template_members` WHERE `template_id` = :template');
        $stmt->execute(['template' => $templateId]);
    }

    public static function deleteByMember(int $memberId): void
    {
        $stmt = self::connection()->prepare('DELETE FROM `audience_template_members` WHERE `member_id` = :member');
        $stmt->execute(['member' => $memberId]);
    }

    public static function detach(int $templateId, int $memberId): void
    {
        $stmt = self::connection()->prepare('DELETE FROM `audience_template_members` WHERE `template_id` = :template AND `member_id` = :member');
        $stmt->execute([
            'template' => $templateId,
            'member' => $memberId,
        ]);
    }

    public static function templatesForMember(int $memberId): array
    {
        $sql = 'SELECT atm.member_id, at.id AS template_id, at.name FROM `audience_template_members` atm INNER JOIN `audience_templates` at ON atm.template_id = at.id WHERE atm.member_id = :member ORDER BY at.name ASC';
        $stmt = self::connection()->prepare($sql);
        $stmt->execute(['member' => $memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function templatesIndex(): array
    {
        $sql = 'SELECT atm.member_id, at.id AS template_id, at.name FROM `audience_template_members` atm INNER JOIN `audience_templates` at ON atm.template_id = at.id ORDER BY at.name ASC';
        $stmt = self::connection()->query($sql);
        $map = [];
        if (!$stmt) {
            return $map;
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $memberId = (int) $row['member_id'];
            $map[$memberId][] = [
                'id' => (int) $row['template_id'],
                'name' => $row['name'],
            ];
        }

        return $map;
    }

    public static function membersForTemplate(int $templateId): array
    {
        $sql = 'SELECT m.* FROM `audience_template_members` atm INNER JOIN `members` m ON atm.member_id = m.id WHERE atm.template_id = :template ORDER BY m.updated_at DESC';
        $stmt = self::connection()->prepare($sql);
        $stmt->execute(['template' => $templateId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
