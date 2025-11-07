<?php
declare(strict_types=1);

namespace App\Models;

final class DispatchJob extends Model
{
    protected static string $table = 'dispatch_jobs';
    protected static array $fillable = [
        'name',
        'template_id',
        'target_type',
        'target_value',
        'scheduled_for',
        'status',
        'created_by',
        'created_at',
        'updated_at',
    ];

    public static function dueJobs(): array
    {
        $stmt = self::connection()->prepare('SELECT * FROM ' . static::$table . ' WHERE status = :status AND (scheduled_for IS NULL OR scheduled_for <= :now) ORDER BY id ASC');
        $stmt->execute([
            'status' => 'queued',
            'now' => date('Y-m-d H:i:s'),
        ]);
        return $stmt->fetchAll();
    }
}
