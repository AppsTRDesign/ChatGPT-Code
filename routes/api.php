<?php

declare(strict_types=1);

use App\Controllers\Api\V1\LicenseController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app): void {
    $app->group('/api/v1', static function (RouteCollectorProxy $group): void {
        $group->post('/licenses/issue', [LicenseController::class, 'issue'])->setArgument('auth', 'mixed');
        $group->post('/licenses/validate', [LicenseController::class, 'validate'])->setArgument('auth', 'hmac');
        $group->post('/licenses/heartbeat', [LicenseController::class, 'heartbeat'])->setArgument('auth', 'hmac');
        $group->post('/licenses/revoke', [LicenseController::class, 'revoke'])->setArgument('auth', 'mixed');
        $group->post('/licenses/refresh', [LicenseController::class, 'refresh'])->setArgument('auth', 'mixed');
        $group->post('/licenses/bind', [LicenseController::class, 'bind'])->setArgument('auth', 'mixed');
        $group->post('/licenses/unbind', [LicenseController::class, 'unbind'])->setArgument('auth', 'mixed');
        $group->get('/licenses/{key}/status', [LicenseController::class, 'status'])->setArgument('auth', 'mixed');
        $group->post('/webhooks/test', [LicenseController::class, 'webhookTest'])->setArgument('auth', 'jwt');
    });
};
