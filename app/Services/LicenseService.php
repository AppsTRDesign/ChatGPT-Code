<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApiClient;
use App\Models\License;
use App\Models\Product;
use App\Models\User;
use App\Services\Exceptions\BlacklistViolationException;
use App\Services\Exceptions\SeatLimitExceededException;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;

final class LicenseService
{
    public function __construct(
        private readonly BindingService $bindingService,
        private readonly WebhookService $webhookService
    ) {
    }

    /**
     * @param array{product_code:string,type:string,seats?:int,expires_at?:string|null,grace_days?:int,meta?:array} $input
     */
    public function issue(array $input, ?User $actor = null, ?ApiClient $client = null, ?string $ip = null, ?string $userAgent = null): array
    {
        $product = Product::query()->where('code', $input['product_code'])->firstOrFail();
        if (!$product->is_active) {
            throw new \RuntimeException('Ürün pasif durumda.');
        }

        $key = $this->generateLicenseKey($product->code);
        $hash = password_hash($key, PASSWORD_ARGON2ID);

        $license = new License();
        $license->product_id = $product->id;
        $license->key_public = $key;
        $license->key_hash = $hash;
        $license->type = $input['type'];
        $license->seats = $input['seats'] ?? 1;
        $license->grace_days = $input['grace_days'] ?? 3;
        $license->status = 'active';
        $license->meta = $input['meta'] ?? [];
        $license->expires_at = isset($input['expires_at']) ? Carbon::parse($input['expires_at']) : $this->defaultExpiry($input['type']);
        $license->save();

        $this->recordEvent($license, 'issue', $ip, $userAgent, [
            'actor' => $actor?->id,
            'client' => $client?->id,
            'type' => $license->type,
            'seats' => $license->seats,
        ]);

        $this->webhookService->dispatch($product, 'license.issued', [
            'license_id' => $license->id,
            'key' => $license->key_public,
            'type' => $license->type,
        ]);

        return [
            'license_id' => $license->id,
            'key_display' => $license->key_public,
            'masked_key' => $license->masked_key,
            'offline_token' => $this->generateOfflineToken($license),
        ];
    }

    /**
     * @param array{product_code:string,key:string,hwid?:string,domain?:string,ip?:string} $input
     */
    public function validate(array $input, ?string $ip = null, ?string $userAgent = null): array
    {
        $license = $this->findLicense($input['product_code'], $input['key']);

        if ($license->status === 'revoked' || $license->status === 'blacklisted') {
            $this->recordEvent($license, 'validate', $ip, $userAgent, ['status' => $license->status]);
            return $this->formatResponse($license, $license->status, 410);
        }

        $now = Carbon::now();
        $status = 'valid';
        $httpStatus = 200;

        if ($license->expires_at !== null && $now->greaterThan($license->expires_at)) {
            $graceEnd = $license->expires_at->copy()->addDays($license->grace_days);
            if ($now->lessThanOrEqualTo($graceEnd)) {
                $status = 'grace';
                $httpStatus = 206;
            } else {
                $license->status = 'expired';
                $license->save();
                $this->recordEvent($license, 'validate', $ip, $userAgent, ['status' => 'expired']);
                return $this->formatResponse($license, 'expired', 410);
            }
        }

        try {
            $binding = $this->bindingService->touchBinding($license, [
                'hwid' => $input['hwid'] ?? null,
                'domain' => $input['domain'] ?? null,
                'ip' => $input['ip'] ?? $ip,
            ]);
        } catch (SeatLimitExceededException $exception) {
            $this->recordEvent($license, 'validate', $ip, $userAgent, ['status' => 'seat_limit']);
            return $this->formatResponse($license, 'seat_limit', 409);
        } catch (BlacklistViolationException $exception) {
            $this->recordEvent($license, 'validate', $ip, $userAgent, ['status' => 'blacklisted']);
            return $this->formatResponse($license, 'blacklisted', 451);
        }

        $this->recordEvent($license, 'validate', $ip, $userAgent, [
            'status' => $status,
            'binding_id' => $binding->id,
        ]);

        return $this->formatResponse($license, $status, $httpStatus);
    }

    /**
     * @param array{product_code:string,key:string,hwid?:string,domain?:string,ip?:string,usage_meta?:array} $input
     */
    public function heartbeat(array $input, ?string $ip = null, ?string $userAgent = null): array
    {
        $license = $this->findLicense($input['product_code'], $input['key']);

        try {
            $binding = $this->bindingService->touchBinding($license, [
                'hwid' => $input['hwid'] ?? null,
                'domain' => $input['domain'] ?? null,
                'ip' => $input['ip'] ?? $ip,
            ]);
        } catch (SeatLimitExceededException $exception) {
            return ['status' => 'seat_limit', 'http_status' => 409];
        } catch (BlacklistViolationException $exception) {
            return ['status' => 'blacklisted', 'http_status' => 451];
        }

        $this->recordEvent($license, 'heartbeat', $ip, $userAgent, [
            'binding_id' => $binding->id,
            'usage_meta' => $input['usage_meta'] ?? [],
        ]);

        return [
            'status' => 'ok',
            'http_status' => 200,
        ];
    }

    /**
     * @param array{license_id?:int,key?:string,reason?:string} $input
     */
    public function revoke(array $input, ?string $ip = null, ?string $userAgent = null): array
    {
        $license = $this->resolveLicense($input);
        $license->status = 'revoked';
        $license->save();

        $this->recordEvent($license, 'revoke', $ip, $userAgent, [
            'reason' => $input['reason'] ?? null,
        ]);

        return ['status' => 'revoked'];
    }

    /**
     * @param array{key:string,extend_days?:int,new_expires_at?:string|null} $input
     */
    public function refresh(array $input, ?string $ip = null, ?string $userAgent = null): array
    {
        $license = $this->findByKey($input['key']);

        if (isset($input['new_expires_at'])) {
            $license->expires_at = $input['new_expires_at'] ? Carbon::parse($input['new_expires_at']) : null;
        } elseif (isset($input['extend_days'])) {
            $license->expires_at = ($license->expires_at ?? Carbon::now())->addDays((int) $input['extend_days']);
        }

        $license->status = 'active';
        $license->save();

        $this->recordEvent($license, 'refresh', $ip, $userAgent, [
            'expires_at' => $license->expires_at?->toIso8601String(),
        ]);

        return [
            'status' => 'active',
            'expires_at' => $license->expires_at?->toIso8601String(),
        ];
    }

    /**
     * @param array{key:string,hwid?:string,domain?:string,ip?:string} $input
     */
    public function bind(array $input, ?string $ip = null, ?string $userAgent = null): array
    {
        $license = $this->findByKey($input['key']);

        try {
            $binding = $this->bindingService->touchBinding($license, [
                'hwid' => $input['hwid'] ?? null,
                'domain' => $input['domain'] ?? null,
                'ip' => $input['ip'] ?? $ip,
            ]);
        } catch (SeatLimitExceededException $exception) {
            return ['status' => 'seat_limit'];
        }

        $this->recordEvent($license, 'bind', $ip, $userAgent, [
            'binding_id' => $binding->id,
        ]);

        return [
            'bindings' => $this->bindingService->serializeBindings($license),
        ];
    }

    /**
     * @param array{key:string,hwid?:string,domain?:string,ip?:string} $input
     */
    public function unbind(array $input, ?string $ip = null, ?string $userAgent = null): array
    {
        $license = $this->findByKey($input['key']);
        $this->bindingService->unbind($license, [
            'hwid' => $input['hwid'] ?? null,
            'domain' => $input['domain'] ?? null,
            'ip' => $input['ip'] ?? $ip,
        ]);

        $this->recordEvent($license, 'unbind', $ip, $userAgent, []);

        return [
            'bindings' => $this->bindingService->serializeBindings($license),
        ];
    }

    public function status(string $key): array
    {
        $license = $this->findByKey($key);
        return $this->formatResponse($license, $license->status, 200);
    }

    public function offlineToken(License $license): ?string
    {
        return $this->generateOfflineToken($license);
    }

    /**
     * @param array{license_id?:int,key?:string} $input
     */
    private function resolveLicense(array $input): License
    {
        if (isset($input['license_id'])) {
            return License::query()->findOrFail($input['license_id']);
        }

        if (isset($input['key'])) {
            return $this->findByKey($input['key']);
        }

        throw new \InvalidArgumentException('License reference required');
    }

    private function findLicense(string $productCode, string $key): License
    {
        $license = $this->findByKey($key);
        if ($license->product->code !== $productCode) {
            throw new \RuntimeException('Product mismatch');
        }

        if (!$license->product->is_active) {
            throw new \RuntimeException('Product inactive');
        }

        if (!password_verify($key, $license->key_hash)) {
            throw new \RuntimeException('Invalid license key');
        }

        return $license;
    }

    private function findByKey(string $key): License
    {
        $license = License::query()->where('key_public', $key)->first();
        if ($license === null) {
            throw new \RuntimeException('License not found');
        }
        return $license;
    }

    private function formatResponse(License $license, string $status, int $httpStatus): array
    {
        return [
            'http_status' => $httpStatus,
            'status' => $status,
            'expires_at' => $license->expires_at?->toIso8601String(),
            'seats' => $license->seats,
            'bindings' => $this->bindingService->serializeBindings($license),
            'policy' => [
                'type' => $license->type,
                'grace_days' => $license->grace_days,
                'meta' => $license->meta,
            ],
            'server_time' => Carbon::now()->toIso8601String(),
        ];
    }

    private function recordEvent(License $license, string $eventType, ?string $ip, ?string $userAgent, array $payload): void
    {
        $license->events()->create([
            'event_type' => $eventType,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'payload' => $payload,
            'created_at' => Carbon::now(),
        ]);
    }

    private function defaultExpiry(string $type): ?Carbon
    {
        return match ($type) {
            'trial' => Carbon::now()->addDays(14),
            'subscription' => Carbon::now()->addMonth(),
            default => null,
        };
    }

    private function generateLicenseKey(string $productCode): string
    {
        $prefix = strtoupper(substr($productCode, 0, 4));
        $uuid = strtoupper(str_replace('-', '', Uuid::uuid4()->toString()));
        $chunks = [substr($uuid, 0, 4), substr($uuid, 4, 4), substr($uuid, 8, 4)];

        return sprintf('%s-%s-%s-%s-%s', $prefix, date('Ym'), ...$chunks);
    }

    private function generateOfflineToken(License $license): ?string
    {
        $privatePath = $_ENV['OFFLINE_SIGN_PRIVATE_KEY_PATH'] ?? null;
        if ($privatePath === null || !file_exists($privatePath)) {
            return null;
        }

        $contents = trim((string) file_get_contents($privatePath));
        if ($contents === '') {
            return null;
        }

        $key = $this->decodeKey($contents);
        if ($key === null) {
            return null;
        }

        $payload = [
            'product_code' => $license->product->code,
            'key_display' => $license->key_public,
            'type' => $license->type,
            'seats' => $license->seats,
            'expires_at' => $license->expires_at?->toIso8601String(),
            'bind_rules' => [
                'grace_days' => $license->grace_days,
                'max_bindings' => $license->seats,
            ],
        ];

        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        if (!function_exists('sodium_crypto_sign_detached')) {
            return null;
        }

        $signature = \sodium_crypto_sign_detached($json, $key);

        return json_encode([
            'payload' => $payload,
            'signature' => base64_encode($signature),
            'algorithm' => 'ed25519',
        ], JSON_THROW_ON_ERROR);
    }

    private function decodeKey(string $contents): ?string
    {
        if (str_starts_with($contents, 'base64:')) {
            return base64_decode(substr($contents, 7), true) ?: null;
        }

        if (preg_match('/^[0-9a-fA-F]+$/', $contents) === 1) {
            return hex2bin($contents) ?: null;
        }

        return base64_decode($contents, true) ?: null;
    }
}
