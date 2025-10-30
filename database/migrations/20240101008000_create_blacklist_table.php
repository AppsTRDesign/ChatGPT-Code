<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();
        if ($schema->hasTable('blacklist')) {
            return;
        }

        $schema->create('blacklist', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('license_id')->nullable();
            $table->string('hwid')->nullable();
            $table->string('domain')->nullable();
            $table->string('ip')->nullable();
            $table->string('text')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('license_id')->references('id')->on('licenses')->onDelete('cascade');
            $table->index(['license_id', 'hwid', 'domain', 'ip']);
        });
    }
};
