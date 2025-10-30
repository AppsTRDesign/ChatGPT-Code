<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseEvent extends Model
{
    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    protected $table = 'license_events';

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
