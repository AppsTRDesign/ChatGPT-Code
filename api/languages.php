<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\LanguageService;
use App\Services\SettingsService;
use Core\Response;

$settingsService = new SettingsService();
$languageService = new LanguageService();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $code = $_GET['code'] ?? null;
        if ($code) {
            $translations = $languageService->read($code);
            Response::json([
                'code' => $code,
                'translations' => $translations,
            ]);
        }

        Response::json([
            'languages' => $settingsService->languages(),
            'default' => $settingsService->currentLanguage(),
        ]);
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        $code = strtolower($payload['code']);
        $label = $payload['label'] ?? strtoupper($code);
        $translations = $payload['translations'] ?? [];

        if (is_string($translations)) {
            $translations = json_decode($translations, true, 512, JSON_THROW_ON_ERROR);
        }

        if (empty($translations)) {
            throw new \RuntimeException('Çeviri içeriği boş olamaz.');
        }

        $languageService->save($code, $translations);
        $settingsService->saveLanguageMeta($code, $label);

        Response::json([
            'languages' => $settingsService->languages(),
            'message' => 'Dil dosyası kaydedildi.',
        ]);
    }

    if ($method === 'PATCH') {
        $payload = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        $code = strtolower($payload['code'] ?? '');
        if ($code === '') {
            throw new \InvalidArgumentException('Dil kodu belirtilmelidir.');
        }
        $settingsService->setDefaultLanguage($code);

        Response::json([
            'languages' => $settingsService->languages(),
            'message' => 'Varsayılan dil güncellendi.',
        ]);
    }

    if ($method === 'DELETE') {
        parse_str($_SERVER['QUERY_STRING'] ?? '', $query);
        $code = strtolower($query['code'] ?? '');
        if ($code === '') {
            throw new \InvalidArgumentException('Dil kodu bulunamadı.');
        }

        $languageService->delete($code);
        $languages = $settingsService->deleteLanguage($code);

        Response::json([
            'languages' => $languages,
            'message' => 'Dil kaydı silindi.',
        ]);
    }

    Response::json(['error' => 'Desteklenmeyen istek yöntemi'], 405);
} catch (Throwable $exception) {
    Response::json([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 500);
}
