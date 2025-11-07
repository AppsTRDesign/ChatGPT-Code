<?php
declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\MemberController;
use App\Controllers\MessagingController;
use App\Controllers\PhoneController;
use App\Controllers\ServiceController;
use App\Controllers\SettingsController;
use App\Controllers\TemplateController;

/** @var \App\Core\Router $router */
$router->get('/', [AuthController::class, 'showLogin'], ['guest']);
$router->get('/admin/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/admin/login', [AuthController::class, 'login'], ['guest']);
$router->get('/admin/logout', [AuthController::class, 'logout'], ['auth']);
$router->get('/admin/password-reset', [AuthController::class, 'showReset'], ['guest']);
$router->post('/admin/password-reset', [AuthController::class, 'sendReset'], ['guest']);

$router->get('/admin', [AdminController::class, 'dashboard'], ['auth']);

$router->get('/admin/phones', [PhoneController::class, 'index'], ['auth']);
$router->post('/admin/phones', [PhoneController::class, 'store'], ['auth']);
$router->post('/admin/phones/{id}', [PhoneController::class, 'update'], ['auth']);
$router->post('/admin/phones/{id}/delete', [PhoneController::class, 'destroy'], ['auth']);

$router->get('/admin/members', [MemberController::class, 'index'], ['auth']);
$router->post('/admin/members', [MemberController::class, 'store'], ['auth']);
$router->post('/admin/members/{id}/delete', [MemberController::class, 'destroy'], ['auth']);
$router->get('/admin/members/export', [MemberController::class, 'export'], ['auth']);
$router->post('/admin/members/import', [MemberController::class, 'import'], ['auth']);

$router->get('/admin/templates', [MessagingController::class, 'index'], ['auth']);
$router->get('/admin/messaging', [MessagingController::class, 'index'], ['auth']);
$router->post('/admin/messaging', [MessagingController::class, 'store'], ['auth']);
$router->post('/admin/messaging/{id}', [MessagingController::class, 'update'], ['auth']);
$router->post('/admin/messaging/{id}/delete', [MessagingController::class, 'destroy'], ['auth']);

$router->get('/admin/message-templates', [TemplateController::class, 'index'], ['auth']);
$router->post('/admin/message-templates', [TemplateController::class, 'store'], ['auth']);
$router->post('/admin/message-templates/{id}', [TemplateController::class, 'update'], ['auth']);
$router->post('/admin/message-templates/{id}/delete', [TemplateController::class, 'destroy'], ['auth']);

$router->get('/admin/settings', [SettingsController::class, 'index'], ['auth']);
$router->post('/admin/settings', [SettingsController::class, 'update'], ['auth']);

$router->get('/admin/services', [ServiceController::class, 'index'], ['auth']);
$router->post('/admin/services', [ServiceController::class, 'store'], ['auth']);
$router->post('/admin/services/{id}', [ServiceController::class, 'update'], ['auth']);
$router->post('/admin/services/{id}/delete', [ServiceController::class, 'destroy'], ['auth']);
