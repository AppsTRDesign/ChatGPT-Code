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

$payload = $_POST;
if (empty($payload) && ($input = file_get_contents('php://input'))) {
    $decoded = json_decode($input, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

$csrf = $payload['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verify_csrf($csrf)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz güvenlik belirteci.']);
    exit;
}

$action = $payload['action'] ?? '';
try {
    switch ($action) {
        case 'list':
            $user = current_user();
            $query = 'SELECT f.*, u.name AS owner_name FROM files f LEFT JOIN users u ON u.id = f.user_id';
            $params = [];
            if (!is_admin()) {
                $query .= ' WHERE f.user_id = :uid';
                $params[':uid'] = $user['id'];
            }
            $query .= ' ORDER BY f.uploaded_at DESC';
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $files = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'files' => $files]);
            break;

        case 'delete':
            $fileId = (int) ($payload['file_id'] ?? 0);
            if ($fileId <= 0) {
                throw new RuntimeException('Geçersiz dosya.');
            }
            $file = fetch_file($pdo, $fileId);
            if (!$file) {
                throw new RuntimeException('Dosya bulunamadı.');
            }
            $user = current_user();
            if (!is_admin() && (int) $file['user_id'] !== (int) $user['id']) {
                throw new RuntimeException('Bu dosyayı silme yetkiniz yok.');
            }
            $pdo->prepare('DELETE FROM files WHERE id = :id')->execute([':id' => $fileId]);
            $storedPath = __DIR__ . '/../uploads/' . $file['stored_name'];
            if (is_file($storedPath)) {
                @unlink($storedPath);
            }
            echo json_encode(['status' => 'success', 'message' => 'Dosya silindi.', 'removeSelector' => "#file-{$fileId}"]);
            break;

        case 'rename':
            $fileId = (int) ($payload['file_id'] ?? 0);
            $newName = trim($payload['filename'] ?? '');
            if ($fileId <= 0 || strlen($newName) < 3) {
                throw new RuntimeException('Geçersiz veri.');
            }
            $file = fetch_file($pdo, $fileId);
            if (!$file) {
                throw new RuntimeException('Dosya bulunamadı.');
            }
            $user = current_user();
            if (!is_admin() && (int) $file['user_id'] !== (int) $user['id']) {
                throw new RuntimeException('Bu dosyayı düzenleme yetkiniz yok.');
            }
            $pdo->prepare('UPDATE files SET filename = :name WHERE id = :id')->execute([
                ':name' => $newName,
                ':id' => $fileId,
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Dosya adı güncellendi.']);
            break;

        default:
            throw new RuntimeException('Geçersiz işlem.');
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
