<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\License;
use App\Models\LicenseBinding;
use App\Models\BlacklistEntry;
use App\Services\Exceptions\BlacklistViolationException;
use App\Services\Exceptions\SeatLimitExceededException;
use Illuminate\Support\Carbon;

final class BindingService
{
    /**
     * @param array{hwid?:string,domain?:string,ip?:string} $context
     */
    public function touchBinding(License $license, array $context): LicenseBinding
    {
        $this->assertNotBlacklisted($license, $context);

        $query = $license->bindings();
        if (isset($context['hwid'])) {
            $query->where('hwid', $context['hwid']);
        }
        if (isset($context['domain'])) {
            $query->where('domain', $context['domain']);
        }
        if (isset($context['ip'])) {
            $query->where('ip', $context['ip']);
        }

        /** @var LicenseBinding|null $binding */
        $binding = $query->first();
        if ($binding !== null) {
            $binding->last_seen_at = Carbon::now();
            $binding->save();
            return $binding;
        }

        $this->ensureSeatAvailability($license);

        $binding = $license->bindings()->create([
            'hwid' => $context['hwid'] ?? null,
            'domain' => $context['domain'] ?? null,
            'ip' => $context['ip'] ?? null,
            'bind_limit' => max($license->seats, 1),
            'last_seen_at' => Carbon::now(),
        ]);

        return $binding;
    }

    /**
     * @param array{hwid?:string,domain?:string,ip?:string} $context
     */
    public function unbind(License $license, array $context): void
    {
        $license->bindings()
            ->where('hwid', $context['hwid'] ?? null)
            ->where('domain', $context['domain'] ?? null)
            ->where('ip', $context['ip'] ?? null)
            ->delete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function serializeBindings(License $license): array
    {
        return $license->bindings()->get()->map(static function (LicenseBinding $binding) {
            return [
                'id' => $binding->id,
                'hwid' => $binding->hwid,
                'domain' => $binding->domain,
                'ip' => $binding->ip,
                'last_seen_at' => $binding->last_seen_at?->toIso8601String(),
                'bind_limit' => $binding->bind_limit,
            ];
        })->all();
    }

    private function ensureSeatAvailability(License $license): void
    {
        $current = $license->bindings()->count();
        if ($current >= $license->seats) {
            throw new SeatLimitExceededException('Seat limit reached');
        }
    }

    /**
     * @param array{hwid?:string,domain?:string,ip?:string} $context
     */
    private function assertNotBlacklisted(License $license, array $context): void
    {
        $query = BlacklistEntry::query()->where(function ($builder) use ($license, $context) {
            if ($license->id !== null) {
                $builder->where('license_id', $license->id);
            }
            $builder->orWhereNull('license_id');
        });

        foreach (['hwid', 'domain', 'ip'] as $field) {
            if (!empty($context[$field])) {
                $query->orWhere($field, $context[$field]);
            }
        }

        if ($query->exists()) {
            throw new BlacklistViolationException('blacklisted');
        }
    }
}
