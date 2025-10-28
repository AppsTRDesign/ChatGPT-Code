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
        case 'profile-update':
            $name = trim($payload['name'] ?? '');
            $password = $payload['password'] ?? '';
            if (strlen($name) < 3) {
                throw new RuntimeException('Ad soyad en az 3 karakter olmalı.');
            }
            $user = current_user();
            $params = [
                ':name' => $name,
                ':id' => $user['id'],
            ];
            $sql = 'UPDATE users SET name = :name';
            if ($password) {
                if (strlen($password) < 8) {
                    throw new RuntimeException('Şifre en az 8 karakter olmalı.');
                }
                $sql .= ', password_hash = :password';
                $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = :id';
            $pdo->prepare($sql)->execute($params);
            $updated = find_user_by_email($pdo, $user['email']);
            login_user($updated);
            echo json_encode(['status' => 'success', 'message' => 'Bilgileriniz güncellendi.']);
            break;

        case 'dashboard':
            $user = current_user();
            $usage = user_storage_usage($pdo, (int) $user['id']);
            $package = package_for_user($pdo, (int) $user['id']);
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'usage' => $usage,
                    'package' => $package,
                ],
            ]);
            break;

        case 'list-packages':
            $stmt = $pdo->query('SELECT * FROM packages WHERE is_active = 1 ORDER BY price ASC');
            $packages = array_map(static function (array $pkg): array {
                $features = [];
                if (!empty($pkg['features'])) {
                    $decoded = json_decode($pkg['features'], true);
                    if (is_array($decoded)) {
                        $features = $decoded;
                    }
                }
                return [
                    'id' => (int) $pkg['id'],
                    'name' => $pkg['name'],
                    'price' => (float) $pkg['price'],
                    'storage_limit' => (int) $pkg['storage_limit'],
                    'max_concurrent_uploads' => (int) $pkg['max_concurrent_uploads'],
                    'features' => $features,
                ];
            }, $stmt->fetchAll() ?: []);
            echo json_encode(['status' => 'success', 'data' => $packages]);
            break;

        case 'purchase-package':
            $packageId = (int) ($payload['package_id'] ?? 0);
            if ($packageId <= 0) {
                throw new RuntimeException('Geçersiz paket seçimi.');
            }
            $package = $pdo->prepare('SELECT * FROM packages WHERE id = :id AND is_active = 1');
            $package->execute([':id' => $packageId]);
            $pkg = $package->fetch();
            if (!$pkg) {
                throw new RuntimeException('Paket bulunamadı.');
            }
            $user = current_user();
            $pdo->prepare('INSERT INTO transactions (user_id, package_id, amount, status) VALUES (:user, :package, :amount, :status)')->execute([
                ':user' => $user['id'],
                ':package' => $packageId,
                ':amount' => $pkg['price'],
                ':status' => 'paid',
            ]);
            $pdo->prepare('UPDATE users SET package_id = :package WHERE id = :id')->execute([
                ':package' => $packageId,
                ':id' => $user['id'],
            ]);
            $updated = find_user_by_email($pdo, $user['email']);
            login_user($updated);
            echo json_encode(['status' => 'success', 'message' => 'Paketiniz güncellendi.']);
            break;

        default:
            throw new RuntimeException('Geçersiz işlem.');
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
