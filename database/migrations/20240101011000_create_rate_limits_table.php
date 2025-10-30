<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();
        if ($schema->hasTable('rate_limits')) {
            return;
        }

        $schema->create('rate_limits', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('key')->unique();
            $table->string('ip');
            $table->string('route')->nullable();
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('window_started_at')->nullable();
            $table->timestamps();
        });
    }
};
