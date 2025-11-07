<?php
declare(strict_types=1);

namespace App\Models;

final class Member extends Model
{
    protected static string $table = 'members';
    protected static array $fillable = [
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'is_public',
        'joined_from_channel',
        'last_active_at',
        'online_status',
        'created_at',
        'updated_at',
    ];
}
