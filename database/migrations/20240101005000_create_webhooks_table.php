<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();
        if ($schema->hasTable('webhooks')) {
            return;
        }

        $schema->create('webhooks', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('url');
            $table->string('secret');
            $table->boolean('is_active')->default(true);

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }
};
