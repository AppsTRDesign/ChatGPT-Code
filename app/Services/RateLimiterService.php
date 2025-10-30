<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RateLimit;
use Illuminate\Support\Carbon;

final class RateLimiterService
{
    private int $defaultLimit;
    private int $windowSeconds;

    public function __construct()
    {
        $this->defaultLimit = (int) ($_ENV['RATE_LIMIT_DEFAULT'] ?? 60);
        $this->windowSeconds = (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 60);
    }

    public function allow(string $clientKey, string $ip, string $route, ?int $customLimit = null): bool
    {
        $key = $clientKey !== '' ? $clientKey : $ip;
        $limit = $customLimit ?? $this->defaultLimit;

        /** @var RateLimit|null $record */
        $record = RateLimit::query()->where('key', $key)->first();

        $windowStart = Carbon::now()->subSeconds($this->windowSeconds);

        if ($record === null) {
            RateLimit::query()->create([
                'key' => $key,
                'ip' => $ip,
                'route' => $route,
                'hits' => 1,
                'window_started_at' => Carbon::now(),
            ]);
            return true;
        }

        if ($record->window_started_at === null || $record->window_started_at->lessThan($windowStart)) {
            $record->hits = 1;
            $record->route = $route;
            $record->window_started_at = Carbon::now();
            $record->save();
            return true;
        }

        if ($record->hits >= $limit) {
            return false;
        }

        $record->increment('hits');
        return true;
    }

    public function getRetryAfterSeconds(string $clientKey): int
    {
        $key = $clientKey !== '' ? $clientKey : 'public';
        /** @var RateLimit|null $record */
        $record = RateLimit::query()->where('key', $key)->first();
        if ($record === null) {
            return $this->windowSeconds;
        }

        if ($record->window_started_at === null) {
            return $this->windowSeconds;
        }

        $elapsed = Carbon::now()->diffInSeconds($record->window_started_at);
        return max(1, $this->windowSeconds - $elapsed);
    }
}
