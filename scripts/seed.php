<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';
require __DIR__ . '/../database/seeders/DatabaseSeeder.php';

use App\Services\LicenseService;

$container = $app->getContainer();
/** @var LicenseService $licenseService */
$licenseService = $container->get(LicenseService::class);

$seeder = new DatabaseSeeder($licenseService);
$seeder->run();

echo "Database seeded\n";
