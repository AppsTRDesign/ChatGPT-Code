<?php

namespace App\Models;

class Package extends Model
{
    protected static string $table = 'packages';
    protected static array $fillable = [
        'name',
        'slug',
        'monthly_limit',
        'duration_days',
        'site_limit',
        'price',
        'currency',
        'description',
        'features',
        'allowed_features',
        'status'
    ];

    protected static function transformRecord(array $record): array
    {
        $record = parent::transformRecord($record);
        $record['features'] = static::decodeJson($record['features'] ?? null);
        $record['allowed_features'] = static::decodeJson($record['allowed_features'] ?? null);

        return $record;
    }

    public static function allActive(): array
    {
        return static::all(['status' => 'active']);
    }

    public static function findBySlug(string $slug): ?array
    {
        return static::first(['slug' => $slug]);
    }

    protected static function decodeJson($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function normalisePayload(array $payload): array
    {
        if (array_key_exists('features', $payload)) {
            $payload['features'] = static::encodeList($payload['features']);
        }

        if (array_key_exists('allowed_features', $payload)) {
            $payload['allowed_features'] = static::encodeList($payload['allowed_features']);
        }

        return $payload;
    }

    protected static function encodeList($value): ?string
    {
        $items = static::normaliseList($value);
        return $items ? json_encode($items) : null;
    }

    protected static function normaliseList($value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\r\n,]+/', $value) ?: [];
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $normalised = array_map(function ($item) {
            if (is_string($item)) {
                return trim($item);
            }

            return $item;
        }, $value);

        return array_values(array_filter($normalised, fn ($item) => $item !== null && $item !== ''));
    }
}
