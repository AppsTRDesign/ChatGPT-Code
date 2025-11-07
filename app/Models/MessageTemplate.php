<?php
declare(strict_types=1);

namespace App\Models;

final class MessageTemplate extends Model
{
    protected static string $table = 'message_templates';
    protected static array $fillable = [
        'title',
        'body',
        'attachment_path',
        'attachment_name',
        'attachment_type',
        'created_at',
        'updated_at',
    ];
}
