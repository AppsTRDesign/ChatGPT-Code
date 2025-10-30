<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();
        if ($schema->hasTable('license_events')) {
            return;
        }

        $schema->create('license_events', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('license_id');
            $table->enum('event_type', ['issue', 'activate', 'validate', 'heartbeat', 'revoke', 'blacklist', 'unblacklist', 'refresh', 'bind', 'unbind', 'usage']);
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('license_id')->references('id')->on('licenses')->onDelete('cascade');
            $table->index('license_id');
        });
    }
};
