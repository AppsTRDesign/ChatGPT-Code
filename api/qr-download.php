<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Services\QrService;
use App\Services\SettingsService;
use Core\Database;

$tableId = (int)($_GET['table_id'] ?? 0);
if ($tableId <= 0) {
    http_response_code(400);
    echo 'table_id parametresi gereklidir.';
    exit;
}

$restaurantId = 1;
$db = Database::connection();
$statement = $db->prepare('SELECT id FROM tables WHERE restaurant_id = ? AND id = ?');
$statement->execute([$restaurantId, $tableId]);
if (!$statement->fetchColumn()) {
    http_response_code(404);
    echo 'Masa bulunamadı.';
    exit;
}

$settingsService = new SettingsService($restaurantId);
$settings = $settingsService->all();
$qrConfig = $settings['qr'] ?? [];
if (!empty($settings['branding']['qr_logo'])) {
    $qrConfig['logo_url'] = $settings['branding']['qr_logo'];
}

$language = strtolower($_GET['lang'] ?? $settingsService->currentLanguage() ?? 'tr');
$currency = strtoupper($_GET['currency'] ?? $settingsService->currentCurrency() ?? 'TRY');

foreach ($settings['currencies'] ?? [] as $currencyOption) {
    if (!empty($currencyOption['is_default'])) {
        $currency = strtoupper($currencyOption['code']);
        break;
    }
}

$language = $language ?: 'tr';
$currency = $currency ?: 'TRY';

$tableUrl = rtrim(BASE_URL, '/') . '/menu/' . $tableId . '/' . $language . '/' . $currency;
$qrService = new QrService();
$qrUrl = $qrService->generateUrl($tableUrl, $qrConfig);

$format = $_GET['format'] ?? ($qrConfig['format'] ?? 'png');
$format = in_array(strtolower($format), ['png', 'svg', 'jpg'], true) ? strtolower($format) : 'png';

$ch = curl_init($qrUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,
]);
$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';
curl_close($ch);

if ($httpCode >= 400 || !$data) {
    http_response_code(502);
    echo 'QR kodu indirilemedi.';
    exit;
}

if ($contentType === '') {
    $contentType = match ($format) {
        'svg' => 'image/svg+xml',
        'jpg' => 'image/jpeg',
        default => 'image/png',
    };
}

$extension = match ($format) {
    'svg' => 'svg',
    'jpg' => 'jpg',
    default => 'png',
};

header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="masa-' . $tableId . '.' . $extension . '"');
header('Cache-Control: no-store');

echo $data;
