<?php
require_once __DIR__ . '/../config/config.php';

use App\Helpers;
use App\TokenManager;
use App\Subscription;
use App\QrHistory;
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

if (isset($tokenRow['email_verified']) && (int) $tokenRow['email_verified'] === 0) {
    UsageLogger::log((int) $tokenRow['user_id'], 'api_qr', 'error', 'E-posta doğrulanmadı');
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Hesabınız doğrulanmadı. Lütfen e-posta onayı yapın.']);
    exit;
}

$userId = (int) $tokenRow['user_id'];
$subscription = Subscription::activeForUser($userId);
if (!$subscription) {
    UsageLogger::log($userId, 'api_qr', 'error', 'Paket yok veya süresi doldu');
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Aktif paket bulunamadı veya kullanım süresi sona erdi']);
    exit;
}

$remaining = Subscription::usageLeft($userId);
if ($remaining !== null && $remaining <= 0) {
    UsageLogger::log($userId, 'api_qr', 'error', 'Limit dolu');
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Paket kullanım limitiniz doldu']);
    exit;
}

$type = strtolower(trim((string) ($data['type'] ?? '')));
if ($type === '') {
    if (isset($data['data'])) {
        $data['custom_data'] = $data['data'];
    }
    $type = 'custom';
}

try {
    $content = QrService::buildContent($type, $data);
} catch (\InvalidArgumentException $e) {
    UsageLogger::log($userId, 'api_qr', 'error', $e->getMessage());
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

$color = $data['color'] ?? '#0d6efd';
$background = $data['background'] ?? '#0b132b';
$backgroundTransparent = false;
if (isset($data['background_transparent'])) {
    $backgroundTransparent = filter_var($data['background_transparent'], FILTER_VALIDATE_BOOLEAN);
}
if (is_string($background) && strtolower($background) === 'transparent') {
    $backgroundTransparent = true;
}
if ($backgroundTransparent) {
    $background = 'transparent';
}
$width = isset($data['width']) ? (int) $data['width'] : 512;
$height = isset($data['height']) ? (int) $data['height'] : 512;
$aspectRatio = isset($data['aspect_ratio']) ? trim((string) $data['aspect_ratio']) : '';
$width = max(128, min($width, 2048));

if ($aspectRatio !== '' && $aspectRatio !== 'custom' && preg_match('/^(\d+):(\d+)$/', $aspectRatio, $matches)) {
    $ratioWidth = (int) $matches[1];
    $ratioHeight = (int) $matches[2];
    if ($ratioWidth > 0 && $ratioHeight > 0) {
        $height = (int) round($width * ($ratioHeight / $ratioWidth));
    }
}

$height = max(128, min($height, 2048));

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

    $formatParam = strtolower((string) ($data['format'] ?? 'png'));
    $outputParam = strtolower((string) ($data['output'] ?? ''));
    $formatList = $data['formats'] ?? [];
    if (is_string($formatList)) {
        $formatList = array_map('trim', explode(',', $formatList));
    }
    if (!is_array($formatList)) {
        $formatList = [];
    }

    $validFormats = ['png', 'jpg', 'svg'];
    $formatParam = in_array($formatParam, $validFormats, true) ? $formatParam : 'png';

    $requestedFormats = [];
    foreach ($formatList as $fmt) {
        $fmt = strtolower((string) $fmt);
        if (in_array($fmt, $validFormats, true) && !in_array($fmt, $requestedFormats, true)) {
            $requestedFormats[] = $fmt;
        }
    }

    $embed = filter_var($data['embed'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $wantsJson = $formatParam === 'json' || $outputParam === 'json';
    $wantsImage = $outputParam === 'image' || $embed;

    if ($method === 'GET' && !$wantsJson) {
        $wantsImage = true;
    }

    $formats = $wantsImage && !$wantsJson ? [$formatParam] : ($requestedFormats ?: [$formatParam]);

    $assets = QrService::generate($content, [
        'color' => $color,
        'background' => $background,
        'width' => $width,
        'height' => $height,
        'background_transparent' => $backgroundTransparent,
    ], $logoPath, $formats);

    $history = QrHistory::record(
        $userId,
        'api',
        $type,
        $content,
        [
            'color' => $color,
            'background' => $background,
            'width' => $width,
            'height' => $height,
            'transparent_background' => $backgroundTransparent,
            'aspect_ratio' => $aspectRatio,
            'formats' => array_keys($assets),
            'logo_url' => $data['logo_url'] ?? null,
        ],
        $assets,
        [
            'request_method' => $method,
            'embed' => $embed,
            'output' => $outputParam,
            'token_id' => $tokenRow['id'] ?? null,
            'token_label' => $tokenRow['label'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]
    );

    UsageLogger::log($userId, 'api_qr', 'success', 'API isteği #' . $history['id']);
    $remainingAfter = Subscription::usageLeft($userId);

    if ($wantsImage) {
        $selectedFormat = $formats[0];
        $mime = $selectedFormat === 'jpg' ? 'image/jpeg' : ($selectedFormat === 'svg' ? 'image/svg+xml' : 'image/png');
        $binary = $assets[$selectedFormat] ?? reset($assets);
        header('Content-Type: ' . $mime);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Access-Control-Allow-Origin: *');
        header('X-QR-History-Id: ' . $history['id']);
        echo $binary;
    } else {
        $embedParams = [
            'token' => $token,
            'data' => $content,
            'output' => 'image',
            'format' => $formatParam,
            'width' => $width,
            'height' => $height,
        ];

        if ($color) {
            $embedParams['color'] = $color;
        }

        if ($backgroundTransparent) {
            $embedParams['background_transparent'] = 'true';
        } elseif ($background) {
            $embedParams['background'] = $background;
        }

        if ($aspectRatio !== '' && $aspectRatio !== 'custom') {
            $embedParams['aspect_ratio'] = $aspectRatio;
        }

        if (!empty($data['logo_url'])) {
            $embedParams['logo_url'] = $data['logo_url'];
        }

        $embedUrl = rtrim(BASE_URL, '/') . '/api/v1/qr?' . http_build_query($embedParams, '', '&', PHP_QUERY_RFC3986);

        $downloads = [];
        foreach ($assets as $format => $binary) {
            $mime = $format === 'jpg' ? 'image/jpeg' : ($format === 'svg' ? 'image/svg+xml' : 'image/png');
            $downloads[] = [
                'format' => $format,
                'mime' => $mime,
                'data' => base64_encode($binary),
                'history_url' => rtrim(BASE_URL, '/') . '/client/qr-download.php?id=' . $history['id'] . '&format=' . $format,
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'downloads' => $downloads,
            'embed_url' => $embedUrl,
            'remaining' => $remainingAfter,
            'history_id' => $history['id'],
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
