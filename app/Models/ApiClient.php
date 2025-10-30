<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiClient extends Model
{
    protected $hidden = ['secret_key'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
