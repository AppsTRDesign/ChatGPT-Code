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
}
