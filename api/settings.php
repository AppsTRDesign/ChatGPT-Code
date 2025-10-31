<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\SettingsService;
use Core\Config;
use Core\Response;
use Helpers\Language;

$service = new SettingsService();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $settings = $service->all();
        $languages = $service->languages();
        $languageCodes = Language::available();
        $qrSettings = $settings['qr'] ?? [];
        $qrPreview = Config::get('qr_api')['base_url'] . '?' . http_build_query([
            'token' => $qrSettings['token'] ?? '',
            'type' => 'url',
            'url' => BASE_URL . '/menu.php',
            'width' => $qrSettings['width'] ?? 400,
            'height' => $qrSettings['height'] ?? 400,
            'color' => $qrSettings['color'] ?? '#000000',
            'background' => $qrSettings['background'] ?? '#ffffff',
            'format' => $qrSettings['format'] ?? 'png',
            'background_transparent' => !empty($qrSettings['transparent']) ? 'true' : 'false',
        ]);

        Response::json([
            'settings' => $settings,
            'languages' => $languages,
            'language_files' => $languageCodes,
            'qr_preview' => $qrPreview,
        ]);
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $updated = $service->update($payload);
        Response::json([
            'settings' => $updated,
            'message' => 'Ayarlar başarıyla güncellendi.',
        ]);
    }

    Response::json(['error' => 'Desteklenmeyen istek yöntemi'], 405);
} catch (Throwable $exception) {
    Response::json([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 500);
}
