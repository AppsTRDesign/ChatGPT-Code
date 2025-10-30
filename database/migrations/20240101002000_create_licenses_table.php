<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();
        if ($schema->hasTable('licenses')) {
            return;
        }

        $schema->create('licenses', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('key_public');
            $table->string('key_hash');
            $table->enum('type', ['perpetual', 'subscription', 'trial'])->default('perpetual');
            $table->unsignedInteger('seats')->default(1);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('grace_days')->default(3);
            $table->enum('status', ['active', 'revoked', 'expired', 'blacklisted'])->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique('key_public');
            $table->unique('key_hash');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index('product_id');
        });
    }
};
