<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Helpers;
use App\TokenManager;
use App\Subscription;
use App\QrService;
use App\UsageLogger;

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$data = [];

if ($method === 'GET') {
    $data = $_GET;
} elseif (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    if ($raw !== false && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Geçersiz JSON']);
            exit;
        }
        $data = $decoded;
    }
} else {
    $data = $_POST;
    if (!$data) {
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
    }
}

if (!is_array($data) || $data === []) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Boş veya desteklenmeyen istek']);
    exit;
}

$token = $data['token'] ?? null;
if (!$token && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $token = trim($matches[1]);
    }
}

if (!$token) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Token gerekli']);
    exit;
}

$tokenRow = TokenManager::validate($token);
if (!$tokenRow) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Token geçersiz']);
    exit;
}

$userId = (int) $tokenRow['user_id'];
$subscription = Subscription::activeForUser($userId);
if (!$subscription) {
    UsageLogger::log($userId, 'api_qr', 'error', 'Paket yok');
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Aktif paket bulunamadı']);
    exit;
}

$remaining = Subscription::usageLeft($userId);
if ($remaining !== null && $remaining <= 0) {
    UsageLogger::log($userId, 'api_qr', 'error', 'Limit dolu');
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Aylık limitiniz doldu']);
    exit;
}

$content = trim($data['data'] ?? '');
if ($content === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'İçerik boş olamaz']);
    exit;
}

$color = $data['color'] ?? '#0d6efd';
$background = $data['background'] ?? '#0b132b';

$logoPath = null;
$tempFile = null;

try {
    if (!empty($data['logo_url'])) {
        $tempFile = tempnam(__DIR__ . '/../uploads/temp', 'api_logo_');
        $image = @file_get_contents($data['logo_url']);
        if ($image === false) {
            throw new RuntimeException('Logo indirilemedi');
        }
        file_put_contents($tempFile, $image);
        $logoPath = $tempFile;
    } elseif ($method !== 'GET' && !empty($data['logo_upload'])) {
        $tempFile = tempnam(__DIR__ . '/../uploads/temp', 'api_logo_');
        $binary = base64_decode($data['logo_upload'], true);
        if ($binary === false) {
            throw new RuntimeException('Logo verisi çözümlenemedi');
        }
        file_put_contents($tempFile, $binary);
        $logoPath = $tempFile;
    }

    $imageData = QrService::generate($content, [
        'color' => $color,
        'background' => $background,
    ], $logoPath);

    UsageLogger::log($userId, 'api_qr', 'success', 'API isteği');

    if ($method === 'GET' && strtolower((string) ($data['format'] ?? '')) !== 'json') {
        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo $imageData;
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'image' => base64_encode($imageData),
            'mime' => 'image/png',
        ]);
    }
} catch (Throwable $e) {
    UsageLogger::log($userId, 'api_qr', 'error', $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'QR kod oluşturulamadı']);
} finally {
    if ($tempFile && file_exists($tempFile)) {
        unlink($tempFile);
    }
}
