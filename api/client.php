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
            $provider = $payload['provider'] ?? 'bank_transfer';
            $allowedProviders = ['iyzico', 'stripe', 'bank_transfer'];
            if (!in_array($provider, $allowedProviders, true)) {
                throw new RuntimeException('Geçersiz ödeme yöntemi.');
            }
            if ($packageId <= 0) {
                throw new RuntimeException('Geçersiz paket seçimi.');
            }
            $stmt = $pdo->prepare('SELECT * FROM packages WHERE id = :id AND is_active = 1');
            $stmt->execute([':id' => $packageId]);
            $pkg = $stmt->fetch();
            if (!$pkg) {
                throw new RuntimeException('Paket bulunamadı.');
            }
            $settings = fetch_settings($pdo);
            $user = current_user();
            $returnUrl = BASE_URL . '/client/packages.php?payment=success';
            $successUrl = $provider === 'iyzico'
                ? BASE_URL . '/api/payment.php?provider=iyzico&return=' . urlencode($returnUrl)
                : $returnUrl;
            $cancelUrl = BASE_URL . '/client/packages.php?payment=cancel';
            $result = initiate_payment($pdo, (int) $user['id'], $packageId, $provider, $successUrl, $cancelUrl);

            if ($provider === 'bank_transfer') {
                $transactionId = $result['transaction_id'];
                notify_user(
                    $pdo,
                    $user['email'],
                    'Paket satın alma talebiniz alındı',
                    '<p>Havale/EFT ile ödeme talebiniz oluşturuldu. Ödeme bilgileriniz yönetici tarafından incelenecektir.</p>'
                );
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Ödeme talebiniz oluşturuldu. Yönetici onayından sonra paketiniz aktifleşecektir.',
                    'transaction_id' => $transactionId,
                    'instructions' => $settings['bank_transfer_instructions'] ?? '',
                ]);
            } else {
                echo json_encode([
                    'status' => 'success',
                    'payment_url' => $result['payment_url'] ?? '',
                    'transaction_id' => $result['transaction_id'] ?? null,
                ]);
            }
            break;

        default:
            throw new RuntimeException('Geçersiz işlem.');
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
