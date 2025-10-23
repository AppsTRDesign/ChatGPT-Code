<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\Settings;

Auth::requireRole('admin');
header('Content-Type: application/json');

$type = $_POST['type'] ?? ($_GET['type'] ?? 'logo');
$csrfToken = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
if (!Helpers::validateCsrf($csrfToken)) {
    echo json_encode(['status' => 'error', 'message' => 'Oturum doğrulaması başarısız.']);
    exit;
}
if (!isset($_FILES['file'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dosya alınamadı.']);
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Yükleme sırasında hata oluştu.']);
    exit;
}

$allowed = ['png', 'jpg', 'jpeg'];
if ($type === 'favicon') {
    $allowed[] = 'ico';
    $allowed[] = 'svg';
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Desteklenmeyen dosya formatı.']);
    exit;
}

$directory = __DIR__ . '/../uploads/branding';
if (!is_dir($directory)) {
    mkdir($directory, 0775, true);
}

$filename = sprintf('%s_%s.%s', $type, uniqid('', true), $ext);
$destination = $directory . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['status' => 'error', 'message' => 'Dosya taşınamadı.']);
    exit;
}

$relative = 'uploads/branding/' . $filename;
$key = $type === 'favicon' ? 'site_favicon' : 'site_logo';
$previous = Settings::get($key);
Settings::set($key, $relative);

if ($previous) {
    $oldPath = __DIR__ . '/../' . ltrim((string) $previous, '/');
    if (is_file($oldPath)) {
        @unlink($oldPath);
    }
}

echo json_encode(['status' => 'success', 'path' => '/' . $relative, 'type' => $type]);
