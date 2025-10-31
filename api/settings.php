<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\SettingsService;
use Core\Response;
use Helpers\Language;
$service = new SettingsService();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $settings = $service->all();
        $languages = Language::available();
        Response::json([
            'settings' => $settings,
            'languages' => $languages,
        ]);
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $updated = $service->update($payload);
        Response::json([
            'settings' => $updated,
            'message' => 'Settings updated successfully',
        ]);
    }

    Response::json(['error' => 'Unsupported method'], 405);
} catch (Throwable $exception) {
    Response::json([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 500);
}
