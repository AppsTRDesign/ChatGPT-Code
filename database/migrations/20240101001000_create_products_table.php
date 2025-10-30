<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();
        if ($schema->hasTable('products')) {
            return;
        }

        $schema->create('products', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('owner_user_id');
            $table->string('name');
            $table->string('code')->unique();
            $table->string('version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('owner_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
