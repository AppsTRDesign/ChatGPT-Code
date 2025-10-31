<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\QrService;
use App\Services\SettingsService;
use Core\Response;

$tableUrl = $_GET['table_url'] ?? '';
if (!$tableUrl) {
    Response::json(['error' => 'table_url parameter is required'], 422);
}

$settingsService = new SettingsService();
$settings = $settingsService->all();
$qrService = new QrService();

$qrUrl = $qrService->generateUrl($tableUrl, $settings['qr'] ?? []);

Response::json([
    'qr_url' => $qrUrl,
]);
