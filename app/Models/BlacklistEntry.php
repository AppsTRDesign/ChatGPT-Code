<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlacklistEntry extends Model
{
    public $timestamps = false;

    protected $table = 'blacklist';

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
