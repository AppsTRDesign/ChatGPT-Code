<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();
        if ($schema->hasTable('subscriptions')) {
            return;
        }

        $schema->create('subscriptions', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('license_id');
            $table->string('external_id')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('license_id')->references('id')->on('licenses')->onDelete('cascade');
        });
    }
};
