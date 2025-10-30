<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $hidden = ['password', 'api_token', 'two_factor_secret'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected function password(): Attribute
    {
        return Attribute::make(
            set: static fn (string $value) => password_hash($value, PASSWORD_ARGON2ID)
        );
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'owner_user_id');
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }
}
