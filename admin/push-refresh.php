<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\OneSignal;
use App\Settings;

Auth::requireRole('admin');
Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek yöntemi.']);
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!Helpers::validateCsrf($csrf)) {
    echo json_encode(['status' => 'error', 'message' => 'Oturum doğrulaması doğrulanamadı.']);
    exit;
}

if (!Settings::onesignalEnabled()) {
    echo json_encode(['status' => 'error', 'message' => 'OneSignal ayarları pasif durumda.']);
    exit;
}

$campaignId = isset($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : null;
if ($campaignId !== null && $campaignId <= 0) {
    $campaignId = null;
}

try {
    $result = OneSignal::refreshCampaignStats($campaignId);
    $updated = (int) ($result['updated'] ?? 0);
    echo json_encode([
        'status' => 'success',
        'message' => $updated > 0 ? 'İstatistikler güncellendi.' : 'Güncellenecek kampanya bulunamadı.',
        'updated' => $updated,
    ]);
} catch (\Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $exception->getMessage(),
    ]);
}
