<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require_auth(true);

$payload = $_POST;
if (empty($payload) && ($raw = file_get_contents('php://input'))) {
    $decoded = json_decode($raw, true);
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
        case 'stats':
            $totalFiles = (int) $pdo->query('SELECT COUNT(*) FROM files')->fetchColumn();
            $totalSize = (int) $pdo->query('SELECT COALESCE(SUM(size), 0) FROM files')->fetchColumn();
            $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'total_files' => $totalFiles,
                    'total_size' => format_bytes($totalSize),
                    'total_users' => $totalUsers,
                ],
            ]);
            break;

        case 'update-settings':
            $fields = [
                'meta_title' => trim($payload['meta_title'] ?? ''),
                'meta_description' => trim($payload['meta_description'] ?? ''),
                'header_html' => $payload['header_html'] ?? '',
                'footer_html' => $payload['footer_html'] ?? '',
                'mail_host' => trim($payload['mail_host'] ?? ''),
                'mail_port' => (int) ($payload['mail_port'] ?? 0),
                'mail_username' => trim($payload['mail_username'] ?? ''),
                'mail_password' => trim($payload['mail_password'] ?? ''),
                'mail_encryption' => trim($payload['mail_encryption'] ?? ''),
                'analytics_code' => $payload['analytics_code'] ?? '',
                'analytics_enabled' => !empty($payload['analytics_enabled']) ? 1 : 0,
                'allowed_mime_types' => trim($payload['allowed_mime_types'] ?? ''),
                'share_expiry_minutes' => (int) ($payload['share_expiry_minutes'] ?? 1440),
                'public_sharing_enabled' => !empty($payload['public_sharing_enabled']) ? 1 : 0,
                'folder_passwords_enabled' => !empty($payload['folder_passwords_enabled']) ? 1 : 0,
            ];
            $settings = fetch_settings($pdo);
            if (!$settings) {
                $pdo->prepare('INSERT INTO settings (meta_title, meta_description) VALUES (:title, :description)')->execute([
                    ':title' => $fields['meta_title'],
                    ':description' => $fields['meta_description'],
                ]);
                $settings = fetch_settings($pdo);
            }
            $logoName = $settings['logo'] ?? null;
            $faviconName = $settings['favicon'] ?? null;
            if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                [$mime] = validate_uploaded_file($_FILES['logo'], $pdo);
                if (!str_starts_with($mime, 'image/')) {
                    throw new RuntimeException('Logo yalnızca görsel olmalıdır.');
                }
                $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $logoName = 'logo_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
                move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/../uploads/' . $logoName);
            }
            if (!empty($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
                [$mime] = validate_uploaded_file($_FILES['favicon'], $pdo);
                if (!str_starts_with($mime, 'image/')) {
                    throw new RuntimeException('Favicon yalnızca görsel olmalıdır.');
                }
                $ext = pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION);
                $faviconName = 'favicon_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
                move_uploaded_file($_FILES['favicon']['tmp_name'], __DIR__ . '/../uploads/' . $faviconName);
            }
            $allowedMimeList = [];
            if ($fields['allowed_mime_types'] !== '') {
                $allowedMimeList = array_filter(array_map('trim', preg_split('/[,\n]+/', $fields['allowed_mime_types']) ?: []));
            }
            $allowedMimeJson = json_encode(array_values(array_unique($allowedMimeList)));

            $stmt = $pdo->prepare('UPDATE settings SET meta_title = :meta_title, meta_description = :meta_description, header_html = :header_html, footer_html = :footer_html, logo = :logo, favicon = :favicon, mail_host = :mail_host, mail_port = :mail_port, mail_username = :mail_username, mail_password = :mail_password, mail_encryption = :mail_encryption, analytics_code = :analytics_code, analytics_enabled = :analytics_enabled, allowed_mime_types = :allowed_mime_types, share_expiry_minutes = :share_expiry_minutes, public_sharing_enabled = :public_sharing_enabled, folder_passwords_enabled = :folder_passwords_enabled LIMIT 1');
            $stmt->execute([
                ':meta_title' => $fields['meta_title'],
                ':meta_description' => $fields['meta_description'],
                ':header_html' => $fields['header_html'],
                ':footer_html' => $fields['footer_html'],
                ':logo' => $logoName,
                ':favicon' => $faviconName,
                ':mail_host' => $fields['mail_host'],
                ':mail_port' => $fields['mail_port'] ?: null,
                ':mail_username' => $fields['mail_username'],
                ':mail_password' => $fields['mail_password'],
                ':mail_encryption' => $fields['mail_encryption'],
                ':analytics_code' => $fields['analytics_code'],
                ':analytics_enabled' => $fields['analytics_enabled'],
                ':allowed_mime_types' => $allowedMimeJson,
                ':share_expiry_minutes' => $fields['share_expiry_minutes'] ?: 1440,
                ':public_sharing_enabled' => $fields['public_sharing_enabled'],
                ':folder_passwords_enabled' => $fields['folder_passwords_enabled'],
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Ayarlar güncellendi.']);
            break;

        case 'save-package':
            $packageId = isset($payload['id']) ? (int) $payload['id'] : null;
            $data = [
                ':name' => trim($payload['name'] ?? ''),
                ':storage' => (int) ($payload['storage_limit'] ?? 0),
                ':uploads' => (int) ($payload['max_concurrent_uploads'] ?? 1),
                ':features' => json_encode($payload['features'] ?? []),
                ':price' => (float) ($payload['price'] ?? 0),
                ':active' => !empty($payload['is_active']) ? 1 : 0,
            ];
            if (strlen($data[':name']) < 3) {
                throw new RuntimeException('Paket adı en az 3 karakter olmalı.');
            }
            if ($packageId) {
                $stmt = $pdo->prepare('UPDATE packages SET name = :name, storage_limit = :storage, max_concurrent_uploads = :uploads, features = :features, price = :price, is_active = :active WHERE id = :id');
                $stmt->execute($data + [':id' => $packageId]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO packages (name, storage_limit, max_concurrent_uploads, features, price, is_active) VALUES (:name, :storage, :uploads, :features, :price, :active)');
                $stmt->execute($data);
                $packageId = (int) $pdo->lastInsertId();
            }
            echo json_encode(['status' => 'success', 'message' => 'Paket kaydedildi.', 'id' => $packageId]);
            break;

        case 'delete-package':
            $packageId = (int) ($payload['id'] ?? 0);
            if ($packageId <= 0) {
                throw new RuntimeException('Geçersiz paket.');
            }
            $pdo->prepare('DELETE FROM packages WHERE id = :id')->execute([':id' => $packageId]);
            echo json_encode(['status' => 'success', 'message' => 'Paket silindi.']);
            break;

        case 'update-user':
            $userId = (int) ($payload['id'] ?? 0);
            $name = trim($payload['name'] ?? '');
            $email = strtolower(trim($payload['email'] ?? ''));
            $role = $payload['role'] ?? 'client';
            $packageId = isset($payload['package_id']) ? (int) $payload['package_id'] : null;
            if ($userId <= 0 || strlen($name) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Geçersiz kullanıcı verisi.');
            }
            $stmt = $pdo->prepare('UPDATE users SET name = :name, email = :email, role = :role, package_id = :package_id WHERE id = :id');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':role' => $role,
                ':package_id' => $packageId ?: null,
                ':id' => $userId,
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Kullanıcı güncellendi.']);
            break;

        case 'delete-user':
            $userId = (int) ($payload['id'] ?? 0);
            if ($userId <= 0) {
                throw new RuntimeException('Geçersiz kullanıcı.');
            }
            if ((int) current_user()['id'] === $userId) {
                throw new RuntimeException('Kendi hesabınızı silemezsiniz.');
            }
            $pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $userId]);
            echo json_encode(['status' => 'success', 'message' => 'Kullanıcı silindi.']);
            break;

        case 'verify-user':
            $userId = (int) ($payload['id'] ?? 0);
            $verified = !empty($payload['verified']) ? 1 : 0;
            $pdo->prepare('UPDATE users SET email_verified = :verified WHERE id = :id')->execute([
                ':verified' => $verified,
                ':id' => $userId,
            ]);
            echo json_encode(['status' => 'success', 'message' => 'E-posta doğrulama durumu güncellendi.']);
            break;

        default:
            throw new RuntimeException('Geçersiz işlem.');
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
