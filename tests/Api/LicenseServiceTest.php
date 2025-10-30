<?php

declare(strict_types=1);

namespace Tests\Api;

use App\Models\Product;
use App\Models\User;
use App\Services\BindingService;
use App\Services\LicenseService;
use App\Services\WebhookService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

final class LicenseServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $schema = $capsule->schema();
        $schema->create('users', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->timestamps();
        });
        $schema->create('products', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('owner_user_id');
            $table->string('name');
            $table->string('code');
            $table->string('version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        $schema->create('licenses', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('key_public');
            $table->string('key_hash');
            $table->string('type');
            $table->unsignedInteger('seats');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('grace_days');
            $table->string('status');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
        $schema->create('license_bindings', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('license_id');
            $table->string('hwid')->nullable();
            $table->string('domain')->nullable();
            $table->string('ip')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedInteger('bind_limit')->default(1);
            $table->timestamps();
        });
        $schema->create('license_events', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('license_id');
            $table->string('event_type');
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function testIssueLicenseCreatesRecord(): void
    {
        $user = User::query()->create([
            'name' => 'Developer',
            'email' => 'dev@example.com',
            'password' => 'secret',
            'role' => 'developer',
        ]);

        $product = Product::query()->create([
            'owner_user_id' => $user->id,
            'name' => 'Test Product',
            'code' => 'TEST',
            'version' => '1.0.0',
            'is_active' => true,
        ]);

        $service = new LicenseService(new BindingService(), new WebhookService());

        $result = $service->issue([
            'product_code' => $product->code,
            'type' => 'perpetual',
            'seats' => 3,
        ], $user, null, '127.0.0.1', 'phpunit');

        $this->assertArrayHasKey('license_id', $result);
        $this->assertArrayHasKey('key_display', $result);
        $this->assertMatchesRegularExpression('/TEST-\d{6}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}/', $result['key_display']);
        $this->assertDatabaseCount('licenses', 1);
    }

    private function assertDatabaseCount(string $table, int $expected): void
    {
        $count = Capsule::table($table)->count();
        $this->assertSame($expected, $count, "Failed asserting that table {$table} has {$expected} rows. Found {$count}");
    }
}
