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
$settings = fetch_settings($pdo);
$activeProviders = [
    'iyzico' => !empty($settings['iyzico_enabled']),
    'stripe' => !empty($settings['stripe_enabled']),
    'bank_transfer' => !empty($settings['bank_transfer_enabled']),
];
$enabledProviders = array_keys(array_filter($activeProviders));

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
                $storageBytes = (int) $pkg['storage_limit'];
                $maxUploadBytes = isset($pkg['max_upload_size']) ? (int) $pkg['max_upload_size'] : 0;
                return [
                    'id' => (int) $pkg['id'],
                    'name' => $pkg['name'],
                    'price' => (float) $pkg['price'],
                    'storage_limit' => $storageBytes,
                    'storage_limit_mb' => $storageBytes > 0 ? (int) round($storageBytes / 1048576) : 0,
                    'max_concurrent_uploads' => (int) $pkg['max_concurrent_uploads'],
                    'max_upload_size' => $maxUploadBytes,
                    'max_upload_size_mb' => $maxUploadBytes > 0 ? (int) ceil($maxUploadBytes / 1048576) : null,
                    'features' => $features,
                    'share_analytics_enabled' => isset($pkg['share_analytics_enabled']) ? (int) $pkg['share_analytics_enabled'] : 0,
                ];
            }, $stmt->fetchAll() ?: []);
            $activePackage = package_for_user($pdo, (int) current_user()['id']);
            echo json_encode([
                'status' => 'success',
                'data' => $packages,
                'active_package_id' => $activePackage ? (int) $activePackage['id'] : null,
                'payment_providers' => $activeProviders,
                'bank_instructions' => $settings['bank_transfer_instructions'] ?? '',
                'currency' => $settings['payment_currency'] ?? 'TRY',
            ]);
            break;

        case 'purchase-package':
            $packageId = (int) ($payload['package_id'] ?? 0);
            $provider = $payload['provider'] ?? 'bank_transfer';
            if (!in_array($provider, $enabledProviders, true)) {
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

        case 'payment-proof':
            $transactionId = (int) ($payload['transaction_id'] ?? 0);
            if ($transactionId <= 0) {
                throw new RuntimeException('Geçersiz işlem numarası.');
            }
            $transaction = fetch_transaction($pdo, $transactionId);
            if (!$transaction || (int) $transaction['user_id'] !== (int) current_user()['id']) {
                throw new RuntimeException('İşlem bulunamadı.');
            }
            if (($transaction['provider'] ?? '') !== 'bank_transfer') {
                throw new RuntimeException('Bu işlem için dekont yüklenemez.');
            }
            if (!$activeProviders['bank_transfer']) {
                throw new RuntimeException('Havale/EFT şu an aktif değil.');
            }

            if (empty($_FILES['file'])) {
                throw new RuntimeException('Dekont dosyası bulunamadı.');
            }

            $files = $_FILES['file'];
            $entries = [];
            if (is_array($files['name'])) {
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    $file = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                    ];
                    [$mime] = validate_uploaded_file($file, null, ['pdf', 'jpeg', 'jpg', 'png']);
                    $entries[] = ['path' => store_payment_proof($file), 'mime' => $mime];
                }
            } else {
                [$mime] = validate_uploaded_file($files, null, ['pdf', 'jpeg', 'jpg', 'png']);
                $entries[] = ['path' => store_payment_proof($files), 'mime' => $mime];
            }

            $note = trim((string) ($payload['note'] ?? ''));
            $existingStmt = $pdo->prepare('SELECT attachments FROM payment_notifications WHERE transaction_id = :transaction_id LIMIT 1');
            $existingStmt->execute([':transaction_id' => $transactionId]);
            $existing = $existingStmt->fetchColumn();
            $existingList = [];
            if ($existing) {
                $decoded = json_decode((string) $existing, true);
                if (is_array($decoded)) {
                    $existingList = $decoded;
                }
            }
            $merged = array_values(array_merge($existingList, $entries));

            $pdo->prepare('INSERT INTO payment_notifications (transaction_id, user_id, provider, amount, currency, status, attachments, note)
                VALUES (:transaction_id, :user_id, :provider, :amount, :currency, :status, :attachments, :note)
                ON DUPLICATE KEY UPDATE attachments = VALUES(attachments), note = VALUES(note), status = VALUES(status), updated_at = NOW()')
                ->execute([
                    ':transaction_id' => $transactionId,
                    ':user_id' => current_user()['id'],
                    ':provider' => 'bank_transfer',
                    ':amount' => $transaction['amount'],
                    ':currency' => $transaction['currency'],
                    ':status' => 'pending',
                    ':attachments' => json_encode($merged, JSON_THROW_ON_ERROR),
                    ':note' => $note,
                ]);

            echo json_encode(['status' => 'success', 'message' => 'Dekontunuz alındı. Yönetici onayı bekleniyor.', 'transaction_id' => $transactionId]);
            break;

        case 'list-shared-files':
            $user = current_user();
            $stmt = $pdo->prepare('SELECT id, filename, share_token, share_created_at, share_expires_at FROM files WHERE user_id = :uid AND share_token IS NOT NULL ORDER BY share_created_at DESC');
            $stmt->execute([':uid' => $user['id']]);
            $shared = array_map(static function (array $row): array {
                return [
                    'id' => (int) $row['id'],
                    'filename' => $row['filename'],
                    'share_token' => $row['share_token'],
                    'share_created_at' => $row['share_created_at'],
                    'share_expires_at' => $row['share_expires_at'],
                    'share_url' => $row['share_token'] ? BASE_URL . '/s/' . $row['share_token'] : null,
                ];
            }, $stmt->fetchAll() ?: []);
            echo json_encode(['status' => 'success', 'data' => $shared]);
            break;

        case 'share-analytics':
            $user = current_user();
            $settings = fetch_settings($pdo);
            $shareStatsEnabled = isset($settings['share_stats_enabled']) ? (int) $settings['share_stats_enabled'] : 1;
            $package = package_for_user($pdo, (int) $user['id']);
            $packageAllowsAnalytics = !$package || (int) ($package['share_analytics_enabled'] ?? 0) === 1;
            if ($shareStatsEnabled !== 1 || !$packageAllowsAnalytics) {
                throw new RuntimeException('Paylaşım analitiği mevcut paketinizde aktif değil.');
            }
            $stats = share_statistics($pdo, (int) $user['id']);
            echo json_encode(['status' => 'success', 'data' => $stats]);
            break;

        default:
            throw new RuntimeException('Geçersiz işlem.');
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
