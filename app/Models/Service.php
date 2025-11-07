<?php
declare(strict_types=1);

namespace App\Models;

final class Service extends Model
{
    protected static string $table = 'services';
    protected static array $fillable = [
        'name',
        'slug',
        'status',
        'last_heartbeat_at',
        'created_at',
        'updated_at',
    ];
}
