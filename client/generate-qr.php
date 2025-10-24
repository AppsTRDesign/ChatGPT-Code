<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\QrHistory;
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

$type = strtolower(trim((string) ($_POST['qr_type'] ?? 'url')));
$content = '';
$allowedTypes = Subscription::allowedQrTypesFromRow($subscription);

if ($allowedTypes !== null && !in_array($type, $allowedTypes, true)) {
    $labels = QrService::supportedTypes();
    if ($allowedTypes === []) {
        $message = 'Paketinizde kullanılabilir QR türü bulunmuyor. Lütfen paket ayarlarınızı güncelleyin.';
    } else {
        $allowedLabels = [];
        foreach ($allowedTypes as $allowedType) {
            if (isset($labels[$allowedType])) {
                $allowedLabels[] = $labels[$allowedType];
            }
        }
        $list = $allowedLabels ? implode(', ', $allowedLabels) : 'belirlenen seçenekler';
        $message = 'Paketiniz bu QR türünü desteklemiyor. Kullanabileceğiniz türler: ' . $list;
    }

    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

try {
    $content = QrService::buildContent($type, $_POST);
} catch (\InvalidArgumentException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

$color = trim($_POST['color'] ?? '#0d6efd');
$background = trim($_POST['background'] ?? '#0b132b');
$transparentBackground = isset($_POST['transparent_background']) && $_POST['transparent_background'] === '1';
if ($transparentBackground || strtolower((string) $background) === 'transparent') {
    $background = 'transparent';
    $transparentBackground = true;
}
$width = (int) ($_POST['width'] ?? 512);
$height = (int) ($_POST['height'] ?? 512);
$aspectRatio = trim((string) ($_POST['aspect_ratio'] ?? ''));
$logo = trim($_POST['logo'] ?? '');
$formatInput = $_POST['formats'] ?? ['png'];
$formats = [];
if (is_array($formatInput)) {
    foreach ($formatInput as $format) {
        $format = strtolower((string) $format);
        if (in_array($format, ['png', 'jpg', 'svg'], true) && !in_array($format, $formats, true)) {
            $formats[] = $format;
        }
    }
}
if (!$formats) {
    $formats = ['png'];
}

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
if ($logo !== '') {
    $logoPath = __DIR__ . '/..' . $logo;
    if (!file_exists($logoPath)) {
        $logoPath = null;
    }
}

try {
    $assets = QrService::generate($content, [
        'color' => $color,
        'background' => $background,
        'width' => $width,
        'height' => $height,
        'background_transparent' => $transparentBackground,
    ], $logoPath, $formats);

    $history = QrHistory::record(
        (int) $user['id'],
        'client',
        $type,
        $content,
        [
            'color' => $color,
            'background' => $background,
            'width' => $width,
            'height' => $height,
            'transparent_background' => $transparentBackground,
            'logo' => $logo !== '' ? $logo : null,
            'formats' => $formats,
        ],
        $assets,
        [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'source' => 'panel',
        ]
    );

    UsageLogger::log((int) $user['id'], 'client_qr', 'success', 'Panel üretimi #' . $history['id']);
    $remainingAfter = Subscription::usageLeft((int) $user['id']);

    $downloads = [];
    $preview = $history['preview_data'] ?? null;
    foreach ($history['files'] as $file) {
        $format = $file['format'];
        $mime = $file['mime'] ?? ($format === 'jpg' ? 'image/jpeg' : ($format === 'svg' ? 'image/svg+xml' : 'image/png'));
        $binary = $assets[$format] ?? null;
        $dataUri = $binary !== null ? 'data:' . $mime . ';base64,' . base64_encode($binary) : null;
        if ($preview === null && $dataUri !== null) {
            $preview = $dataUri;
        }
        $downloads[] = [
            'format' => $format,
            'mime' => $mime,
            'label' => strtoupper($format),
            'data' => $dataUri,
            'url' => '/client/qr-download.php?id=' . $history['id'] . '&format=' . $format,
        ];
    }

    echo json_encode([
        'status' => 'success',
        'preview' => $preview,
        'downloads' => $downloads,
        'remaining' => $remainingAfter,
        'history_id' => $history['id'],
    ]);
} catch (Throwable $e) {
    UsageLogger::log((int) $user['id'], 'client_qr', 'error', $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'QR kod oluşturulamadı']);
}
