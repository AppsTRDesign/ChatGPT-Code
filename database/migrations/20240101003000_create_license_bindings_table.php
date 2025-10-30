<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();
        if ($schema->hasTable('license_bindings')) {
            return;
        }

        $schema->create('license_bindings', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('license_id');
            $table->string('hwid')->nullable();
            $table->string('domain')->nullable();
            $table->string('ip')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedInteger('bind_limit')->default(1);
            $table->timestamps();

            $table->foreign('license_id')->references('id')->on('licenses')->onDelete('cascade');
            $table->index(['license_id', 'hwid']);
        });
    }
};
