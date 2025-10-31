<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\QrService;
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
        $qrLogo = $settings['branding']['qr_logo'] ?? ($qrSettings['logo'] ?? null);
        if ($qrLogo && str_starts_with($qrLogo, '/')) {
            $qrLogo = rtrim(BASE_URL, '/') . $qrLogo;
        }
        $qrService = new QrService();
        $qrPreview = $qrService->generateUrl(BASE_URL . '/menu', array_merge($qrSettings, ['logo' => $qrLogo]));

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
