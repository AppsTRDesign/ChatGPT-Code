<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class License extends Model
{
    protected $casts = [
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bindings(): HasMany
    {
        return $this->hasMany(LicenseBinding::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(LicenseEvent::class);
    }

    protected function maskedKey(): Attribute
    {
        return Attribute::make(
            get: function () {
                $display = $this->key_public ?? '';
                if ($display === '') {
                    return '';
                }
                return substr($display, 0, 8) . '****' . substr($display, -4);
            }
        );
    }
}
