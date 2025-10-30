<?php

declare(strict_types=1);

use App\Models\ApiClient;
use App\Models\Product;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Support\Carbon;

final class DatabaseSeeder
{
    public function __construct(private readonly LicenseService $licenseService)
    {
    }

    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Kullanıcı',
                'password' => 'Password123!',
                'role' => 'admin',
            ]
        );

        $developer = User::query()->firstOrCreate(
            ['email' => 'dev@example.com'],
            [
                'name' => 'Developer',
                'password' => 'Password123!',
                'role' => 'developer',
            ]
        );

        $product = Product::query()->firstOrCreate(
            ['code' => 'APPX'],
            [
                'name' => 'AppX Masaüstü',
                'owner_user_id' => $developer->id,
                'version' => '1.0.0',
                'is_active' => true,
            ]
        );

        ApiClient::query()->firstOrCreate(
            ['access_key' => 'APPX-CLIENT-1'],
            [
                'product_id' => $product->id,
                'name' => 'AppX Prod Client',
                'secret_key' => bin2hex(random_bytes(16)),
                'rate_limit_per_min' => 120,
            ]
        );

        $this->licenseService->issue([
            'product_code' => $product->code,
            'type' => 'perpetual',
            'seats' => 5,
            'meta' => ['plan' => 'enterprise'],
        ], $developer, null, '127.0.0.1', 'seeder/1.0');

        $this->licenseService->issue([
            'product_code' => $product->code,
            'type' => 'subscription',
            'seats' => 10,
            'expires_at' => Carbon::now()->addMonth()->toIso8601String(),
        ], $developer, null, '127.0.0.1', 'seeder/1.0');

        $this->licenseService->issue([
            'product_code' => $product->code,
            'type' => 'trial',
            'seats' => 1,
            'expires_at' => Carbon::now()->addDays(14)->toIso8601String(),
        ], $developer, null, '127.0.0.1', 'seeder/1.0');
    }
}
