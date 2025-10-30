<?php

declare(strict_types=1);

namespace App\Models;

class RateLimit extends Model
{
    protected $casts = [
        'window_started_at' => 'datetime',
    ];
}
