<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();
        if ($schema->hasTable('users')) {
            return;
        }

        $schema->create('users', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'developer'])->default('developer');
            $table->string('api_token')->nullable();
            $table->string('two_factor_secret')->nullable();
            $table->timestamps();
        });
    }
};
