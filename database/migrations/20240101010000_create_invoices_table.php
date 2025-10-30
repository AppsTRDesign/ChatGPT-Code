<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();
        if ($schema->hasTable('invoices')) {
            return;
        }

        $schema->create('invoices', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('subscription_id')->nullable();
            $table->string('invoice_number');
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('set null');
        });
    }
};
