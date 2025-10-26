<?php

namespace App\Models;

class SupportRequest extends Model
{
    protected static string $table = 'support_requests';
    protected static array $fillable = [
        'client_id',
        'email',
        'subject',
        'message',
        'status'
    ];
}
