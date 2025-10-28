<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require_auth();

$csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verify_csrf($csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz güvenlik belirteci.']);
    exit;
}

if (empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Dosya bulunamadı.']);
    exit;
}

$file = $_FILES['file'];
try {
    $user = current_user();
    $package = package_for_user($pdo, (int) $user['id']);
    $allowedExtensions = allowed_extensions($pdo, $package['id'] ?? null);
    $maxUploadSize = $package && !empty($package['max_upload_size']) ? (int) $package['max_upload_size'] : null;
    [$mimeType, $size, $extension] = validate_uploaded_file($file, $pdo, $allowedExtensions, $maxUploadSize);
    $folderId = isset($_POST['folder_id']) ? (int) $_POST['folder_id'] : null;
    if ($folderId) {
        $folder = fetch_folder($pdo, $folderId);
        if (!$folder || (!is_admin() && (int) $folder['user_id'] !== (int) $user['id'])) {
            throw new RuntimeException('Klasör erişim yetkiniz yok.');
        }
    }
    if (!can_upload($pdo, (int) $user['id'], $size)) {
        throw new RuntimeException('Depo alanı limitini aştınız.');
    }
    $storedName = bin2hex(random_bytes(16)) . ($extension ? '.' . $extension : '');
    $destination = __DIR__ . '/../uploads/' . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Dosya kaydedilemedi.');
    }
    $fileId = store_file($pdo, [
        'filename' => $file['name'],
        'stored_name' => $storedName,
        'size' => $size,
        'type' => $mimeType,
        'uploader_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'user_id' => $user['id'],
        'folder_id' => $folderId,
    ]);
    $slug = slugify(pathinfo($file['name'], PATHINFO_FILENAME));
    $fileUrl = BASE_URL . '/file/' . $fileId . '-' . $slug . ($extension ? '.' . $extension : '');
    echo json_encode([
        'status' => 'success',
        'message' => 'Dosya başarıyla yüklendi.',
        'fileUrl' => $fileUrl,
        'fileId' => $fileId,
        'folder_id' => $folderId,
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
