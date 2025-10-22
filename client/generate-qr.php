<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Helpers;
use App\QrService;
use App\Subscription;
use App\UsageLogger;

header('Content-Type: application/json');

$user = Auth::user();
if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'Oturum bulunamadı']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek']);
    exit;
}

if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz oturum anahtarı']);
    exit;
}

$subscription = Subscription::activeForUser((int) $user['id']);
if (!$subscription) {
    echo json_encode(['status' => 'error', 'message' => 'Aktif paket bulunamadı']);
    exit;
}

$remaining = Subscription::usageLeft((int) $user['id']);
if ($remaining !== null && $remaining <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Aylık limitiniz doldu']);
    exit;
}

$data = trim($_POST['data'] ?? '');
$color = trim($_POST['color'] ?? '#0d6efd');
$background = trim($_POST['background'] ?? '#0b132b');
$logo = trim($_POST['logo'] ?? '');

if ($data === '') {
    echo json_encode(['status' => 'error', 'message' => 'İçerik boş olamaz']);
    exit;
}

$logoPath = null;
if ($logo !== '') {
    $logoPath = __DIR__ . '/..' . $logo;
    if (!file_exists($logoPath)) {
        $logoPath = null;
    }
}

try {
    $imageData = QrService::generate($data, [
        'color' => $color,
        'background' => $background,
    ], $logoPath);
    $base64 = 'data:image/png;base64,' . base64_encode($imageData);
    UsageLogger::log((int) $user['id'], 'client_qr', 'success', 'Panel üretimi');
    echo json_encode(['status' => 'success', 'image' => $base64]);
} catch (Throwable $e) {
    UsageLogger::log((int) $user['id'], 'client_qr', 'error', $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'QR kod oluşturulamadı']);
}
