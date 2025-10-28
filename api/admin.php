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
        case 'list-users':
            $stmt = $pdo->query('SELECT u.*, p.name AS package_name FROM users u LEFT JOIN packages p ON p.id = u.package_id ORDER BY u.created_at DESC');
            $users = array_map(static function (array $user): array {
                return [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'package_id' => $user['package_id'] ? (int) $user['package_id'] : null,
                    'package_name' => $user['package_name'] ?? null,
                    'email_verified' => (int) $user['email_verified'],
                    'created_at' => $user['created_at'],
                ];
            }, $stmt->fetchAll() ?: []);
            echo json_encode(['status' => 'success', 'data' => $users]);
            break;

        case 'list-packages':
            $stmt = $pdo->query('SELECT * FROM packages ORDER BY price ASC');
            $packages = array_map(static function (array $package): array {
                $features = [];
                if (!empty($package['features'])) {
                    $decoded = json_decode($package['features'], true);
                    if (is_array($decoded)) {
                        $features = $decoded;
                    }
                }
                $mimeList = [];
                if (!empty($package['allowed_mime_types'])) {
                    $decoded = json_decode($package['allowed_mime_types'], true);
                    if (is_array($decoded)) {
                        $mimeList = $decoded;
                    }
                }
                return [
                    'id' => (int) $package['id'],
                    'name' => $package['name'],
                    'storage_limit' => (int) $package['storage_limit'],
                    'max_concurrent_uploads' => (int) $package['max_concurrent_uploads'],
                    'features' => $features,
                    'allowed_mime_types' => $mimeList,
                    'price' => (float) $package['price'],
                    'is_active' => (int) $package['is_active'],
                    'plesk_service_plan' => $package['plesk_service_plan'] ?? null,
                ];
            }, $stmt->fetchAll() ?: []);
            echo json_encode(['status' => 'success', 'data' => $packages]);
            break;

        case 'list-files':
            $stmt = $pdo->query('SELECT f.*, u.name AS owner_name FROM files f LEFT JOIN users u ON u.id = f.user_id ORDER BY f.uploaded_at DESC');
            $rows = $stmt->fetchAll() ?: [];
            $folderIds = array_unique(array_filter(array_map(static fn($row) => $row['folder_id'] ? (int) $row['folder_id'] : null, $rows)));
            $folderMap = [];
            if ($folderIds) {
                $placeholders = implode(',', array_fill(0, count($folderIds), '?'));
                $folderStmt = $pdo->prepare("SELECT * FROM folders WHERE id IN ($placeholders)");
                $folderStmt->execute($folderIds);
                foreach ($folderStmt->fetchAll() as $folder) {
                    $folderMap[(int) $folder['id']] = [
                        'name' => $folder['name'],
                        'path' => folder_path($pdo, $folder),
                    ];
                }
            }
            $files = array_map(static function (array $row) use ($folderMap): array {
                $extension = pathinfo($row['filename'], PATHINFO_EXTENSION);
                $slug = slugify(pathinfo($row['filename'], PATHINFO_FILENAME));
                $downloadUrl = BASE_URL . '/file/' . $row['id'] . '-' . $slug . ($extension ? '.' . strtolower($extension) : '');
                $folderId = $row['folder_id'] ? (int) $row['folder_id'] : null;
                $folderData = $folderId && isset($folderMap[$folderId]) ? $folderMap[$folderId] : null;
                return [
                    'id' => (int) $row['id'],
                    'filename' => $row['filename'],
                    'stored_name' => $row['stored_name'],
                    'size' => (int) $row['size'],
                    'type' => $row['type'],
                    'uploaded_at' => $row['uploaded_at'],
                    'owner_name' => $row['owner_name'] ?? null,
                    'owner_id' => (int) $row['user_id'],
                    'folder_id' => $folderId,
                    'folder_path' => $folderData['path'] ?? 'Ana Depo',
                    'download_url' => $downloadUrl,
                    'direct_url' => BASE_URL . '/uploads/' . $row['stored_name'],
                ];
            }, $rows);
            echo json_encode(['status' => 'success', 'data' => $files]);
            break;

        case 'list-transactions':
            $stmt = $pdo->query('SELECT t.*, u.name AS user_name, u.email AS user_email, p.name AS package_name FROM transactions t LEFT JOIN users u ON u.id = t.user_id LEFT JOIN packages p ON p.id = t.package_id ORDER BY t.created_at DESC');
            $transactions = array_map(static function (array $row): array {
                $labels = [
                    'pending' => 'Beklemede',
                    'paid' => 'Ödendi',
                    'failed' => 'Başarısız',
                    'cancelled' => 'İptal',
                    'refunded' => 'İade',
                ];
                return [
                    'id' => (int) $row['id'],
                    'user_id' => (int) $row['user_id'],
                    'user_name' => $row['user_name'] ?? '—',
                    'user_email' => $row['user_email'] ?? '—',
                    'package_id' => (int) $row['package_id'],
                    'package_name' => $row['package_name'] ?? '—',
                    'amount' => (float) $row['amount'],
                    'currency' => $row['currency'] ?? 'TRY',
                    'provider' => $row['provider'],
                    'status' => $row['status'],
                    'status_label' => $labels[$row['status']] ?? ucfirst($row['status']),
                    'reference' => $row['reference'],
                    'created_at' => $row['created_at'],
                ];
            }, $stmt->fetchAll() ?: []);
            echo json_encode(['status' => 'success', 'data' => $transactions]);
            break;

        case 'update-transaction':
            $transactionId = (int) ($payload['transaction_id'] ?? 0);
            $newStatus = $payload['status'] ?? '';
            $allowed = ['paid', 'failed', 'cancelled'];
            if ($transactionId <= 0 || !in_array($newStatus, $allowed, true)) {
                throw new RuntimeException('Geçersiz işlem verisi.');
            }
            $transaction = fetch_transaction($pdo, $transactionId);
            if (!$transaction) {
                throw new RuntimeException('İşlem bulunamadı.');
            }
            complete_transaction($pdo, $transactionId, $newStatus);
            echo json_encode(['status' => 'success', 'message' => 'İşlem durumu güncellendi.']);
            break;

        case 'list-payment-notifications':
            $stmt = $pdo->query('SELECT n.*, u.name AS user_name, u.email AS user_email, p.name AS package_name, t.amount, t.currency FROM payment_notifications n INNER JOIN users u ON u.id = n.user_id LEFT JOIN transactions t ON t.id = n.transaction_id LEFT JOIN packages p ON p.id = t.package_id ORDER BY n.created_at DESC');
            $notifications = array_map(static function (array $row): array {
                $attachments = [];
                if (!empty($row['attachments'])) {
                    $decoded = json_decode($row['attachments'], true);
                    if (is_array($decoded)) {
                        $attachments = array_map(static function ($item): array {
                            $path = is_array($item) ? ($item['path'] ?? '') : '';
                            return [
                                'path' => $path,
                                'url' => $path ? BASE_URL . '/uploads/' . ltrim($path, '/') : null,
                                'mime' => is_array($item) ? ($item['mime'] ?? null) : null,
                            ];
                        }, $decoded);
                    }
                }
                $labels = [
                    'pending' => 'Beklemede',
                    'approved' => 'Onaylandı',
                    'rejected' => 'Reddedildi',
                    'insufficient' => 'Eksik ödeme',
                ];
                return [
                    'id' => (int) $row['id'],
                    'transaction_id' => $row['transaction_id'] ? (int) $row['transaction_id'] : null,
                    'user_id' => (int) $row['user_id'],
                    'user_name' => $row['user_name'],
                    'user_email' => $row['user_email'],
                    'package_name' => $row['package_name'] ?? '—',
                    'amount' => (float) ($row['amount'] ?? 0),
                    'currency' => $row['currency'] ?? 'TRY',
                    'provider' => $row['provider'],
                    'status' => $row['status'],
                    'status_label' => $labels[$row['status']] ?? ucfirst($row['status']),
                    'attachments' => $attachments,
                    'note' => $row['note'],
                    'created_at' => $row['created_at'],
                ];
            }, $stmt->fetchAll() ?: []);
            echo json_encode(['status' => 'success', 'data' => $notifications]);
            break;

        case 'update-payment-notification':
            $notificationId = (int) ($payload['notification_id'] ?? 0);
            $status = $payload['status'] ?? '';
            $allowedStatuses = ['approved', 'rejected', 'insufficient'];
            if ($notificationId <= 0 || !in_array($status, $allowedStatuses, true)) {
                throw new RuntimeException('Geçersiz bildirim verisi.');
            }
            $stmt = $pdo->prepare('SELECT * FROM payment_notifications WHERE id = :id');
            $stmt->execute([':id' => $notificationId]);
            $notification = $stmt->fetch();
            if (!$notification) {
                throw new RuntimeException('Bildirim bulunamadı.');
            }
            $pdo->prepare('UPDATE payment_notifications SET status = :status, updated_at = NOW() WHERE id = :id')->execute([
                ':status' => $status,
                ':id' => $notificationId,
            ]);
            if (!empty($notification['transaction_id'])) {
                $txStatus = $status === 'approved' ? 'paid' : 'failed';
                complete_transaction($pdo, (int) $notification['transaction_id'], $txStatus);
            }
            push_realtime_event($pdo, 'transactions', [
                'type' => 'payment_notification_updated',
                'notification_id' => $notificationId,
                'status' => $status,
            ], (int) $notification['user_id']);
            echo json_encode(['status' => 'success', 'message' => 'Bildirim güncellendi.']);
            break;

        case 'plesk-test':
            $settings = fetch_settings($pdo);
            if (empty($settings['plesk_api_url']) || empty($settings['plesk_api_login']) || empty($settings['plesk_api_password'])) {
                throw new RuntimeException('Plesk API ayarları eksik.');
            }
            $endpoint = rtrim($settings['plesk_api_url'], '/') . '/api/v2/servers';
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD => $settings['plesk_api_login'] . ':' . $settings['plesk_api_password'],
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_TIMEOUT => 10,
            ]);
            $response = curl_exec($ch);
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new RuntimeException('Plesk bağlantısı sağlanamadı: ' . $error);
            }
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code >= 200 && $code < 300) {
                echo json_encode(['status' => 'success', 'message' => 'Plesk API bağlantısı başarılı.']);
            } else {
                throw new RuntimeException('Plesk API beklenmedik yanıt döndürdü. HTTP ' . $code);
            }
            break;

        case 'ws-test':
            $settings = fetch_settings($pdo);
            if (empty($settings['realtime_ws_url'])) {
                throw new RuntimeException('WebSocket URL yapılandırılmamış.');
            }
            $parts = parse_url($settings['realtime_ws_url']);
            if (!$parts || empty($parts['host'])) {
                throw new RuntimeException('Geçersiz WebSocket URL bilgisi.');
            }
            $port = $parts['port'] ?? (($parts['scheme'] ?? 'ws') === 'wss' ? 443 : 80);
            $errno = 0;
            $errstr = '';
            $socket = @fsockopen($parts['host'], $port, $errno, $errstr, 5);
            if (!$socket) {
                throw new RuntimeException('WebSocket sunucusuna bağlanılamadı: ' . $errstr);
            }
            fclose($socket);
            echo json_encode(['status' => 'success', 'message' => 'WebSocket bağlantısı kurulabiliyor.']);
            break;

        case 'usage-timeseries':
            $requestedRanges = $payload['ranges'] ?? ['daily'];
            if (!is_array($requestedRanges)) {
                $requestedRanges = [$requestedRanges];
            }
            $uniqueRanges = array_values(array_unique(array_filter($requestedRanges, static fn($range) => is_string($range))));
            if (!$uniqueRanges) {
                $uniqueRanges = ['daily'];
            }
            $series = [];
            foreach ($uniqueRanges as $range) {
                $series[$range] = collect_usage_timeseries($pdo, $range);
            }
            echo json_encode(['status' => 'success', 'data' => $series]);
            break;


        case 'update-settings':
            $fields = [
                'meta_title' => trim($payload['meta_title'] ?? ''),
                'meta_description' => trim($payload['meta_description'] ?? ''),
                'meta_keywords' => trim($payload['meta_keywords'] ?? ''),
                'social_title' => trim($payload['social_title'] ?? ''),
                'social_description' => trim($payload['social_description'] ?? ''),
                'twitter_handle' => trim($payload['twitter_handle'] ?? ''),
                'header_html' => $payload['header_html'] ?? '',
                'footer_html' => $payload['footer_html'] ?? '',
                'mail_enabled' => !empty($payload['mail_enabled']) ? 1 : 0,
                'mail_method' => in_array(($payload['mail_method'] ?? 'smtp'), ['smtp', 'phpmail'], true) ? $payload['mail_method'] : 'smtp',
                'mail_host' => trim($payload['mail_host'] ?? ''),
                'mail_port' => (int) ($payload['mail_port'] ?? 0),
                'mail_username' => trim($payload['mail_username'] ?? ''),
                'mail_password' => trim($payload['mail_password'] ?? ''),
                'mail_encryption' => trim($payload['mail_encryption'] ?? ''),
                'mail_from_name' => trim($payload['mail_from_name'] ?? ''),
                'mail_from_address' => trim($payload['mail_from_address'] ?? ''),
                'analytics_code' => $payload['analytics_code'] ?? '',
                'analytics_enabled' => !empty($payload['analytics_enabled']) ? 1 : 0,
                'allowed_mime_types' => trim($payload['allowed_mime_types'] ?? ''),
                'share_expiry_minutes' => (int) ($payload['share_expiry_minutes'] ?? 1440),
                'public_sharing_enabled' => !empty($payload['public_sharing_enabled']) ? 1 : 0,
                'folder_passwords_enabled' => !empty($payload['folder_passwords_enabled']) ? 1 : 0,
                'share_download_delay' => max(0, (int) ($payload['share_download_delay'] ?? 0)),
                'share_password_required' => !empty($payload['share_password_required']) ? 1 : 0,
                'share_stats_enabled' => !empty($payload['share_stats_enabled']) ? 1 : 0,
                'ad_dashboard_html' => $payload['ad_dashboard_html'] ?? '',
                'ad_share_top_html' => $payload['ad_share_top_html'] ?? '',
                'ad_share_bottom_html' => $payload['ad_share_bottom_html'] ?? '',
                'payment_currency' => strtoupper(trim($payload['payment_currency'] ?? 'TRY')),
                'iyzico_enabled' => !empty($payload['iyzico_enabled']) ? 1 : 0,
                'iyzico_api_key' => trim($payload['iyzico_api_key'] ?? ''),
                'iyzico_secret_key' => trim($payload['iyzico_secret_key'] ?? ''),
                'iyzico_base_url' => trim($payload['iyzico_base_url'] ?? ''),
                'stripe_enabled' => !empty($payload['stripe_enabled']) ? 1 : 0,
                'stripe_api_key' => trim($payload['stripe_api_key'] ?? ''),
                'stripe_publishable_key' => trim($payload['stripe_publishable_key'] ?? ''),
                'stripe_webhook_secret' => trim($payload['stripe_webhook_secret'] ?? ''),
                'bank_transfer_enabled' => !empty($payload['bank_transfer_enabled']) ? 1 : 0,
                'bank_transfer_instructions' => $payload['bank_transfer_instructions'] ?? '',
                'auto_archive_enabled' => !empty($payload['auto_archive_enabled']) ? 1 : 0,
                'auto_delete_enabled' => !empty($payload['auto_delete_enabled']) ? 1 : 0,
                'archive_after_days' => max(0, (int) ($payload['archive_after_days'] ?? 0)) ?: null,
                'delete_after_days' => max(0, (int) ($payload['delete_after_days'] ?? 0)) ?: null,
                'geoip_database_path' => trim($payload['geoip_database_path'] ?? ''),
                'realtime_updates_enabled' => !empty($payload['realtime_updates_enabled']) ? 1 : 0,
                'plesk_api_url' => trim($payload['plesk_api_url'] ?? ''),
                'plesk_api_login' => trim($payload['plesk_api_login'] ?? ''),
                'plesk_api_password' => trim($payload['plesk_api_password'] ?? ''),
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
            $bannerName = $settings['brand_banner'] ?? null;
            $socialImageName = $settings['social_image'] ?? null;
            if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                [$mime] = validate_uploaded_file($_FILES['logo'], $pdo, ['image/jpeg', 'image/png', 'image/svg+xml', 'image/gif']);
                if (!str_starts_with($mime, 'image/')) {
                    throw new RuntimeException('Logo yalnızca görsel olmalıdır.');
                }
                $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $logoName = 'logo_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
                move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/../uploads/' . $logoName);
            }
            if (!empty($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
                [$mime] = validate_uploaded_file($_FILES['favicon'], $pdo, ['image/png', 'image/x-icon', 'image/svg+xml', 'image/gif']);
                if (!str_starts_with($mime, 'image/')) {
                    throw new RuntimeException('Favicon yalnızca görsel olmalıdır.');
                }
                $ext = pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION);
                $faviconName = 'favicon_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
                move_uploaded_file($_FILES['favicon']['tmp_name'], __DIR__ . '/../uploads/' . $faviconName);
            }
            if (!empty($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
                [$mime] = validate_uploaded_file($_FILES['banner'], $pdo, ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);
                if (!str_starts_with($mime, 'image/')) {
                    throw new RuntimeException('Banner yalnızca görsel olmalıdır.');
                }
                $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
                $bannerName = 'banner_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
                move_uploaded_file($_FILES['banner']['tmp_name'], __DIR__ . '/../uploads/' . $bannerName);
                $socialImageName = $bannerName;
            }
            $allowedMimeList = [];
            if ($fields['allowed_mime_types'] !== '') {
                $allowedMimeList = array_filter(array_map('trim', preg_split('/[,\n]+/', $fields['allowed_mime_types']) ?: []));
            }
            $allowedMimeJson = json_encode(array_values(array_unique($allowedMimeList)));

            $stmt = $pdo->prepare('UPDATE settings SET meta_title = :meta_title, meta_description = :meta_description, meta_keywords = :meta_keywords, social_title = :social_title, social_description = :social_description, social_image = :social_image, twitter_handle = :twitter_handle, brand_banner = :brand_banner, header_html = :header_html, footer_html = :footer_html, logo = :logo, favicon = :favicon, mail_enabled = :mail_enabled, mail_method = :mail_method, mail_host = :mail_host, mail_port = :mail_port, mail_username = :mail_username, mail_password = :mail_password, mail_encryption = :mail_encryption, mail_from_name = :mail_from_name, mail_from_address = :mail_from_address, analytics_code = :analytics_code, analytics_enabled = :analytics_enabled, allowed_mime_types = :allowed_mime_types, share_expiry_minutes = :share_expiry_minutes, public_sharing_enabled = :public_sharing_enabled, folder_passwords_enabled = :folder_passwords_enabled, share_download_delay = :share_download_delay, share_password_required = :share_password_required, share_stats_enabled = :share_stats_enabled, ad_dashboard_html = :ad_dashboard_html, ad_share_top_html = :ad_share_top_html, ad_share_bottom_html = :ad_share_bottom_html, payment_currency = :payment_currency, iyzico_enabled = :iyzico_enabled, iyzico_api_key = :iyzico_api_key, iyzico_secret_key = :iyzico_secret_key, iyzico_base_url = :iyzico_base_url, stripe_enabled = :stripe_enabled, stripe_api_key = :stripe_api_key, stripe_publishable_key = :stripe_publishable_key, stripe_webhook_secret = :stripe_webhook_secret, bank_transfer_enabled = :bank_transfer_enabled, bank_transfer_instructions = :bank_transfer_instructions, auto_archive_enabled = :auto_archive_enabled, auto_delete_enabled = :auto_delete_enabled, archive_after_days = :archive_after_days, delete_after_days = :delete_after_days, geoip_database_path = :geoip_database_path, realtime_updates_enabled = :realtime_updates_enabled, plesk_api_url = :plesk_api_url, plesk_api_login = :plesk_api_login, plesk_api_password = :plesk_api_password LIMIT 1');
            $stmt->execute([
                ':meta_title' => $fields['meta_title'],
                ':meta_description' => $fields['meta_description'],
                ':meta_keywords' => $fields['meta_keywords'],
                ':social_title' => $fields['social_title'] ?: $fields['meta_title'],
                ':social_description' => $fields['social_description'] ?: $fields['meta_description'],
                ':social_image' => $socialImageName,
                ':twitter_handle' => $fields['twitter_handle'],
                ':brand_banner' => $bannerName,
                ':header_html' => $fields['header_html'],
                ':footer_html' => $fields['footer_html'],
                ':logo' => $logoName,
                ':favicon' => $faviconName,
                ':mail_enabled' => $fields['mail_enabled'],
                ':mail_method' => $fields['mail_method'],
                ':mail_host' => $fields['mail_host'],
                ':mail_port' => $fields['mail_port'] ?: null,
                ':mail_username' => $fields['mail_username'],
                ':mail_password' => $fields['mail_password'],
                ':mail_encryption' => $fields['mail_encryption'],
                ':mail_from_name' => $fields['mail_from_name'],
                ':mail_from_address' => $fields['mail_from_address'],
                ':analytics_code' => $fields['analytics_code'],
                ':analytics_enabled' => $fields['analytics_enabled'],
                ':allowed_mime_types' => $allowedMimeJson,
                ':share_expiry_minutes' => $fields['share_expiry_minutes'] ?: 1440,
                ':public_sharing_enabled' => $fields['public_sharing_enabled'],
                ':folder_passwords_enabled' => $fields['folder_passwords_enabled'],
                ':share_download_delay' => $fields['share_download_delay'],
                ':share_password_required' => $fields['share_password_required'],
                ':share_stats_enabled' => $fields['share_stats_enabled'],
                ':ad_dashboard_html' => $fields['ad_dashboard_html'],
                ':ad_share_top_html' => $fields['ad_share_top_html'],
                ':ad_share_bottom_html' => $fields['ad_share_bottom_html'],
                ':payment_currency' => $fields['payment_currency'],
                ':iyzico_enabled' => $fields['iyzico_enabled'],
                ':iyzico_api_key' => $fields['iyzico_api_key'],
                ':iyzico_secret_key' => $fields['iyzico_secret_key'],
                ':iyzico_base_url' => $fields['iyzico_base_url'],
                ':stripe_enabled' => $fields['stripe_enabled'],
                ':stripe_api_key' => $fields['stripe_api_key'],
                ':stripe_publishable_key' => $fields['stripe_publishable_key'],
                ':stripe_webhook_secret' => $fields['stripe_webhook_secret'],
                ':bank_transfer_enabled' => $fields['bank_transfer_enabled'],
                ':bank_transfer_instructions' => $fields['bank_transfer_instructions'],
                ':auto_archive_enabled' => $fields['auto_archive_enabled'],
                ':auto_delete_enabled' => $fields['auto_delete_enabled'],
                ':archive_after_days' => $fields['archive_after_days'],
                ':delete_after_days' => $fields['delete_after_days'],
                ':geoip_database_path' => $fields['geoip_database_path'],
                ':realtime_updates_enabled' => $fields['realtime_updates_enabled'],
                ':plesk_api_url' => $fields['plesk_api_url'],
                ':plesk_api_login' => $fields['plesk_api_login'],
                ':plesk_api_password' => $fields['plesk_api_password'],
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Ayarlar güncellendi.']);
            break;

        case 'save-package':
            $packageId = isset($payload['id']) ? (int) $payload['id'] : null;
            $rawFeatures = $payload['features'] ?? '[]';
            if (is_string($rawFeatures)) {
                $decodedFeatures = json_decode($rawFeatures, true);
                if (!is_array($decodedFeatures)) {
                    $decodedFeatures = array_filter(array_map('trim', explode(',', $rawFeatures)));
                }
            } else {
                $decodedFeatures = is_array($rawFeatures) ? $rawFeatures : [];
            }
            $featuresJson = json_encode(array_values(array_filter($decodedFeatures, static fn($item) => $item !== '')));

            $rawMime = $payload['allowed_mime_types'] ?? '';
            if (is_string($rawMime) && $rawMime !== '') {
                $decodedMime = json_decode($rawMime, true);
                if (is_array($decodedMime)) {
                    $allowedMimeJson = json_encode(array_values(array_filter(array_map('trim', $decodedMime))));
                } else {
                    $allowedMimeJson = json_encode(array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $rawMime) ?: []))));
                }
            } elseif (is_array($rawMime)) {
                $allowedMimeJson = json_encode(array_values(array_filter(array_map('trim', $rawMime))));
            } else {
                $allowedMimeJson = null;
            }

            $data = [
                ':name' => trim($payload['name'] ?? ''),
                ':storage' => (int) ($payload['storage_limit'] ?? 0),
                ':uploads' => (int) ($payload['max_concurrent_uploads'] ?? 1),
                ':features' => $featuresJson,
                ':price' => (float) ($payload['price'] ?? 0),
                ':active' => !empty($payload['is_active']) ? 1 : 0,
                ':allowed_mime_types' => $allowedMimeJson,
                ':plan' => trim($payload['plesk_service_plan'] ?? ''),
            ];
            if (strlen($data[':name']) < 3) {
                throw new RuntimeException('Paket adı en az 3 karakter olmalı.');
            }
            if ($packageId) {
                $stmt = $pdo->prepare('UPDATE packages SET name = :name, storage_limit = :storage, max_concurrent_uploads = :uploads, features = :features, allowed_mime_types = :allowed_mime_types, plesk_service_plan = :plan, price = :price, is_active = :active WHERE id = :id');
                $stmt->execute($data + [':id' => $packageId]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO packages (name, storage_limit, max_concurrent_uploads, features, allowed_mime_types, plesk_service_plan, price, is_active) VALUES (:name, :storage, :uploads, :features, :allowed_mime_types, :plan, :price, :active)');
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
