<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../bootstrap/app.php';

/** @var Capsule $capsule */
$capsule = $app->getContainer()->get(Capsule::class);
$schema = $capsule->schema();

if (!$schema->hasTable('migrations')) {
    $schema->create('migrations', static function ($table): void {
        $table->increments('id');
        $table->string('migration')->unique();
        $table->timestamp('ran_at')->useCurrent();
    });
}

$ran = $capsule->table('migrations')->pluck('migration')->all();
$files = glob(__DIR__ . '/../database/migrations/*.php');
sort($files);

foreach ($files as $file) {
    $migrationName = basename($file);
    if (in_array($migrationName, $ran, true)) {
        continue;
    }

    $instance = require $file;
    if (is_object($instance) && method_exists($instance, 'up')) {
        $instance->up();
    } elseif (is_callable($instance)) {
        $instance($schema);
    }

    $capsule->table('migrations')->insert(['migration' => $migrationName]);
    echo "Migrated: {$migrationName}\n";
}
