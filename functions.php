<?php
declare(strict_types=1);

use DeviceDetector\DeviceDetector;
use GeoIp2\Database\Reader;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use PHPMailer\PHPMailer\PHPMailer;
use Stripe\StripeClient;

const DEFAULT_ALLOWED_MIME_TYPES = [
    'image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain',
    'application/zip', 'application/x-rar-compressed',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];

function schemaColumnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
    $stmt->execute([
        ':table' => $table,
        ':column' => $column,
    ]);
    return (int) $stmt->fetchColumn() > 0;
}

function schemaIndexExists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index');
    $stmt->execute([
        ':table' => $table,
        ':index' => $index,
    ]);
    return (int) $stmt->fetchColumn() > 0;
}

function schemaConstraintExists(PDO $pdo, string $table, string $constraint): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND CONSTRAINT_NAME = :constraint');
    $stmt->execute([
        ':table' => $table,
        ':constraint' => $constraint,
    ]);
    return (int) $stmt->fetchColumn() > 0;
}

function ensureDatabaseSchema(PDO $pdo): void
{
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    storage_limit BIGINT NOT NULL,
    max_concurrent_uploads INT NOT NULL,
    features TEXT NOT NULL,
    allowed_mime_types TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

    if (!schemaColumnExists($pdo, 'packages', 'allowed_mime_types')) {
        $pdo->exec('ALTER TABLE packages ADD COLUMN allowed_mime_types TEXT DEFAULT NULL AFTER features');
    }

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client', 'admin') NOT NULL DEFAULT 'client',
    package_id INT DEFAULT NULL,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_users_packages FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    name VARCHAR(120) NOT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    is_protected TINYINT(1) NOT NULL DEFAULT 0,
    password_hash VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_folders_user (user_id),
    INDEX idx_folders_parent (parent_id),
    CONSTRAINT fk_folders_owner FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_folders_parent FOREIGN KEY (parent_id) REFERENCES folders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    size BIGINT NOT NULL,
    type VARCHAR(100) NOT NULL,
    uploader_ip VARCHAR(45) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT DEFAULT NULL,
    folder_id INT DEFAULT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    share_token VARCHAR(64) DEFAULT NULL,
    share_created_at DATETIME DEFAULT NULL,
    share_expires_at DATETIME DEFAULT NULL,
    INDEX (user_id),
    INDEX (folder_id),
    UNIQUE KEY uniq_share_token (share_token),
    CONSTRAINT fk_files_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_files_folders FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    CONSTRAINT fk_password_resets_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'TRY',
    provider ENUM('iyzico','stripe','bank_transfer') NOT NULL DEFAULT 'bank_transfer',
    status ENUM('pending', 'paid', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    reference VARCHAR(191) DEFAULT NULL,
    payload JSON DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transactions_packages FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    INDEX idx_transactions_provider (provider),
    INDEX idx_transactions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
    if (!schemaColumnExists($pdo, 'transactions', 'currency')) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN currency VARCHAR(10) NOT NULL DEFAULT 'TRY' AFTER amount");
    }
    if (!schemaColumnExists($pdo, 'transactions', 'provider')) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN provider ENUM('iyzico','stripe','bank_transfer') NOT NULL DEFAULT 'bank_transfer' AFTER currency");
    }
    if (!schemaColumnExists($pdo, 'transactions', 'reference')) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN reference VARCHAR(191) DEFAULT NULL AFTER status");
    }
    if (!schemaColumnExists($pdo, 'transactions', 'payload')) {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN payload JSON DEFAULT NULL AFTER reference");
    }
    if (!schemaColumnExists($pdo, 'transactions', 'paid_at')) {
        $pdo->exec('ALTER TABLE transactions ADD COLUMN paid_at DATETIME DEFAULT NULL AFTER payload');
    }
    if (!schemaColumnExists($pdo, 'transactions', 'updated_at')) {
        $pdo->exec('ALTER TABLE transactions ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
    }
    if (!schemaIndexExists($pdo, 'transactions', 'idx_transactions_provider')) {
        $pdo->exec('ALTER TABLE transactions ADD INDEX idx_transactions_provider (provider)');
    }
    if (!schemaIndexExists($pdo, 'transactions', 'idx_transactions_status')) {
        $pdo->exec('ALTER TABLE transactions ADD INDEX idx_transactions_status (status)');
    }

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS file_access_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    share_token VARCHAR(64) DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    country VARCHAR(120) DEFAULT NULL,
    city VARCHAR(120) DEFAULT NULL,
    latitude DECIMAL(10,6) DEFAULT NULL,
    longitude DECIMAL(10,6) DEFAULT NULL,
    device_type VARCHAR(50) DEFAULT NULL,
    os VARCHAR(100) DEFAULT NULL,
    browser VARCHAR(100) DEFAULT NULL,
    platform VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_access_file (file_id),
    INDEX idx_access_token (share_token),
    CONSTRAINT fk_access_file FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    CONSTRAINT fk_access_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS realtime_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    channel VARCHAR(120) NOT NULL,
    payload JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_events_channel (channel),
    INDEX idx_events_user (user_id),
    CONSTRAINT fk_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS retention_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    archive_after_days INT DEFAULT NULL,
    delete_after_days INT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

    if (!schemaColumnExists($pdo, 'files', 'retention_policy_id')) {
        $pdo->exec('ALTER TABLE files ADD COLUMN retention_policy_id INT DEFAULT NULL AFTER share_expires_at');
        $pdo->exec('ALTER TABLE files ADD CONSTRAINT fk_files_retention FOREIGN KEY (retention_policy_id) REFERENCES retention_policies(id) ON DELETE SET NULL');
    }
    if (!schemaColumnExists($pdo, 'files', 'folder_id')) {
        $pdo->exec('ALTER TABLE files ADD COLUMN folder_id INT DEFAULT NULL AFTER user_id');
    }
    if (!schemaColumnExists($pdo, 'files', 'is_public')) {
        $pdo->exec('ALTER TABLE files ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0 AFTER folder_id');
    }
    if (!schemaColumnExists($pdo, 'files', 'share_token')) {
        $pdo->exec('ALTER TABLE files ADD COLUMN share_token VARCHAR(64) DEFAULT NULL AFTER is_public');
    }
    if (!schemaColumnExists($pdo, 'files', 'share_created_at')) {
        $pdo->exec('ALTER TABLE files ADD COLUMN share_created_at DATETIME DEFAULT NULL AFTER share_token');
    }
    if (!schemaColumnExists($pdo, 'files', 'share_expires_at')) {
        $pdo->exec('ALTER TABLE files ADD COLUMN share_expires_at DATETIME DEFAULT NULL AFTER share_created_at');
    }
    if (!schemaIndexExists($pdo, 'files', 'idx_files_folder_id')) {
        $pdo->exec('ALTER TABLE files ADD INDEX idx_files_folder_id (folder_id)');
    }
    if (!schemaIndexExists($pdo, 'files', 'uniq_share_token')) {
        $pdo->exec('ALTER TABLE files ADD UNIQUE INDEX uniq_share_token (share_token)');
    }
    if (!schemaConstraintExists($pdo, 'files', 'fk_files_folders')) {
        $pdo->exec('ALTER TABLE files ADD CONSTRAINT fk_files_folders FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_auth(bool $admin = false): void
{
    $ajax = is_ajax_request();

    if (!current_user()) {
        $message = 'Bu işlemi gerçekleştirmek için giriş yapmalısınız. Lütfen giriş yapın veya üye olun.';
        if ($ajax) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => $message]);
        } else {
            header('Location: ' . BASE_URL . '/login');
        }
        exit;
    }

    if ($admin && !is_admin()) {
        if ($ajax) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Yalnızca yöneticiler bu işlemi gerçekleştirebilir.']);
        } else {
            header('Location: ' . BASE_URL . '/client');
        }
        exit;
    }
}

function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'dosya';
}

function store_file(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO files (filename, stored_name, size, type, uploader_ip, uploaded_at, user_id, folder_id, is_public) VALUES (:filename, :stored_name, :size, :type, :uploader_ip, NOW(), :user_id, :folder_id, :is_public)');
    $stmt->execute([
        ':filename' => $data['filename'],
        ':stored_name' => $data['stored_name'],
        ':size' => $data['size'],
        ':type' => $data['type'],
        ':uploader_ip' => $data['uploader_ip'],
        ':user_id' => $data['user_id'],
        ':folder_id' => $data['folder_id'] ?? null,
        ':is_public' => !empty($data['is_public']) ? 1 : 0,
    ]);
    return (int) $pdo->lastInsertId();
}

function fetch_file(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT f.*, u.email AS uploader_email FROM files f LEFT JOIN users u ON u.id = f.user_id WHERE f.id = :id');
    $stmt->execute([':id' => $id]);
    $file = $stmt->fetch();
    return $file ?: null;
}

function fetch_shared_file(PDO $pdo, string $token): ?array
{
    $stmt = $pdo->prepare('SELECT f.*, u.email AS uploader_email FROM files f LEFT JOIN users u ON u.id = f.user_id WHERE f.share_token = :token');
    $stmt->execute([':token' => $token]);
    $file = $stmt->fetch();
    return $file ?: null;
}

function fetch_folder(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM folders WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $folder = $stmt->fetch();
    return $folder ?: null;
}

function folder_path(PDO $pdo, array $folder): string
{
    $segments = [$folder['name']];
    $parentId = (int) ($folder['parent_id'] ?? 0);
    while ($parentId) {
        $parent = fetch_folder($pdo, $parentId);
        if (!$parent) {
            break;
        }
        array_unshift($segments, $parent['name']);
        $parentId = (int) ($parent['parent_id'] ?? 0);
    }
    return implode(' / ', $segments);
}

function folder_ancestor_ids(PDO $pdo, int $folderId): array
{
    $ancestors = [];
    $current = fetch_folder($pdo, $folderId);
    while ($current && !empty($current['parent_id'])) {
        $parentId = (int) $current['parent_id'];
        $ancestors[] = $parentId;
        $current = fetch_folder($pdo, $parentId);
    }
    return $ancestors;
}

function list_user_folders(PDO $pdo, array $user, bool $isAdmin): array
{
    if ($isAdmin) {
        $stmt = $pdo->query('SELECT * FROM folders ORDER BY user_id, name');
    } else {
        $stmt = $pdo->prepare('SELECT * FROM folders WHERE user_id = :uid ORDER BY name');
        $stmt->execute([':uid' => $user['id']]);
    }
    $folders = $stmt->fetchAll() ?: [];
    return array_map(static function (array $folder) use ($pdo, $isAdmin): array {
        $path = folder_path($pdo, $folder);
        if ($isAdmin) {
            $path = '[Kullanıcı #' . $folder['user_id'] . '] ' . $path;
        }
        return [
            'id' => (int) $folder['id'],
            'name' => $folder['name'],
            'user_id' => (int) $folder['user_id'],
            'path' => $path,
            'parent_id' => $folder['parent_id'] ? (int) $folder['parent_id'] : null,
            'is_public' => (int) $folder['is_public'],
            'is_protected' => (int) $folder['is_protected'],
            'ancestors' => folder_ancestor_ids($pdo, (int) $folder['id']),
        ];
    }, $folders);
}

function folder_breadcrumbs(PDO $pdo, ?int $folderId, array $user, bool $isAdmin): array
{
    $breadcrumbs = [];
    $currentId = $folderId;
    while ($currentId) {
        $folder = fetch_folder($pdo, $currentId);
        if (!$folder) {
            break;
        }
        if (!$isAdmin && (int) $folder['user_id'] !== (int) $user['id']) {
            break;
        }
        array_unshift($breadcrumbs, [
            'id' => (int) $folder['id'],
            'name' => $folder['name'],
        ]);
        $currentId = $folder['parent_id'] ? (int) $folder['parent_id'] : null;
    }
    array_unshift($breadcrumbs, ['id' => null, 'name' => 'Ana Depo']);
    return $breadcrumbs;
}

function folder_is_descendant(PDO $pdo, int $folderId, int $potentialDescendant): bool
{
    $current = $potentialDescendant;
    while ($current) {
        if ($current === $folderId) {
            return true;
        }
        $folder = fetch_folder($pdo, $current);
        if (!$folder) {
            break;
        }
        $current = $folder['parent_id'] ? (int) $folder['parent_id'] : null;
    }
    return false;
}

function list_folder_contents(PDO $pdo, array $user, ?int $folderId, bool $isAdmin, array $options = []): array
{
    $currentFolder = null;
    if ($folderId) {
        $currentFolder = fetch_folder($pdo, $folderId);
        if (!$currentFolder) {
            throw new RuntimeException('Klasör bulunamadı.');
        }
        if (!$isAdmin && (int) $currentFolder['user_id'] !== (int) $user['id']) {
            throw new RuntimeException('Bu klasöre erişim yetkiniz yok.');
        }
    }

    $page = max(1, (int) ($options['page'] ?? 1));
    $perPage = max(1, min(96, (int) ($options['per_page'] ?? ($isAdmin ? 100 : 24))));
    $sortKey = strtolower((string) ($options['sort'] ?? 'name'));
    $direction = strtoupper((string) ($options['direction'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

    $sortMap = [
        'name' => 'filename',
        'size' => 'size',
        'date' => 'uploaded_at',
    ];
    $column = $sortMap[$sortKey] ?? 'filename';

    if ($isAdmin) {
        $folderStmt = $pdo->prepare('SELECT f.*, (SELECT COUNT(*) FROM files fi WHERE fi.folder_id = f.id) AS file_count FROM folders f WHERE (:folderId IS NULL AND f.parent_id IS NULL) OR f.parent_id = :folderId ORDER BY f.name');
        $folderStmt->execute([':folderId' => $folderId]);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM files WHERE (:folderId IS NULL AND folder_id IS NULL) OR folder_id = :folderId');
        $countStmt->execute([':folderId' => $folderId]);

        $fileStmt = $pdo->prepare("SELECT f.*, u.name AS owner_name FROM files f LEFT JOIN users u ON u.id = f.user_id WHERE ((:folderId IS NULL AND f.folder_id IS NULL) OR f.folder_id = :folderId) ORDER BY {$column} {$direction} LIMIT :limit OFFSET :offset");
        $fileStmt->bindValue(':folderId', $folderId, $folderId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $fileStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $fileStmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $fileStmt->execute();
        $totalFiles = (int) $countStmt->fetchColumn();
    } else {
        $folderStmt = $pdo->prepare('SELECT f.*, (SELECT COUNT(*) FROM files fi WHERE fi.folder_id = f.id) AS file_count FROM folders f WHERE f.user_id = :uid AND ((:folderId IS NULL AND f.parent_id IS NULL) OR f.parent_id = :folderId) ORDER BY f.name');
        $folderStmt->execute([':uid' => $user['id'], ':folderId' => $folderId]);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM files WHERE user_id = :uid AND ((:folderId IS NULL AND folder_id IS NULL) OR folder_id = :folderId)');
        $countStmt->execute([':uid' => $user['id'], ':folderId' => $folderId]);

        $fileStmt = $pdo->prepare("SELECT * FROM files WHERE user_id = :uid AND ((:folderId IS NULL AND folder_id IS NULL) OR folder_id = :folderId) ORDER BY {$column} {$direction} LIMIT :limit OFFSET :offset");
        $fileStmt->bindValue(':uid', $user['id'], PDO::PARAM_INT);
        $fileStmt->bindValue(':folderId', $folderId, $folderId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $fileStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $fileStmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $fileStmt->execute();
        $totalFiles = (int) $countStmt->fetchColumn();
    }

    return [
        'current' => $currentFolder,
        'folders' => $folderStmt->fetchAll() ?: [],
        'files' => $fileStmt->fetchAll() ?: [],
        'breadcrumbs' => folder_breadcrumbs($pdo, $folderId, $user, $isAdmin),
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $totalFiles,
            'total_pages' => (int) max(1, (int) ceil(max(1, $totalFiles) / $perPage)),
            'sort' => $sortKey,
            'direction' => strtolower($direction),
        ],
    ];
}

function delete_file_record(PDO $pdo, array $file): void
{
    $pdo->prepare('DELETE FROM files WHERE id = :id')->execute([':id' => $file['id']]);
    $storedPath = __DIR__ . '/uploads/' . $file['stored_name'];
    if (is_file($storedPath)) {
        @unlink($storedPath);
    }
}

function delete_folder_recursive(PDO $pdo, int $folderId): void
{
    $stmtFiles = $pdo->prepare('SELECT * FROM files WHERE folder_id = :id');
    $stmtFiles->execute([':id' => $folderId]);
    foreach ($stmtFiles->fetchAll() as $file) {
        delete_file_record($pdo, $file);
    }
    $stmtFolders = $pdo->prepare('SELECT id FROM folders WHERE parent_id = :id');
    $stmtFolders->execute([':id' => $folderId]);
    foreach ($stmtFolders->fetchAll() as $child) {
        delete_folder_recursive($pdo, (int) $child['id']);
    }
    $pdo->prepare('DELETE FROM folders WHERE id = :id')->execute([':id' => $folderId]);
}

function generate_share_token(): string
{
    return bin2hex(random_bytes(16));
}

function share_file(PDO $pdo, array $file, int $expiryMinutes): array
{
    $attempts = 0;
    $token = generate_share_token();
    $expiryMinutes = max(1, $expiryMinutes);
    $expiresAt = (new DateTimeImmutable('now'))->add(new DateInterval('PT' . $expiryMinutes . 'M'));
    $stmt = $pdo->prepare('UPDATE files SET share_token = :token, share_created_at = NOW(), share_expires_at = :expires WHERE id = :id');
    while (true) {
        try {
            $stmt->execute([
                ':token' => $token,
                ':expires' => $expiresAt->format('Y-m-d H:i:s'),
                ':id' => $file['id'],
            ]);
            break;
        } catch (PDOException $e) {
            if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062 && $attempts < 5) {
                $token = generate_share_token();
                $attempts++;
                continue;
            }
            throw $e;
        }
    }
    return [
        'token' => $token,
        'expires_at' => $expiresAt->format(DateTimeInterface::ATOM),
        'url' => BASE_URL . '/s/' . $token,
    ];
}

function revoke_share(PDO $pdo, int $fileId): void
{
    $pdo->prepare('UPDATE files SET share_token = NULL, share_created_at = NULL, share_expires_at = NULL WHERE id = :id')->execute([':id' => $fileId]);
}

function is_share_active(array $file): bool
{
    if (empty($file['share_token'])) {
        return false;
    }
    if (!empty($file['share_expires_at'])) {
        $expiresAt = new DateTimeImmutable($file['share_expires_at']);
        if ($expiresAt < new DateTimeImmutable('now')) {
            return false;
        }
    }
    return true;
}

function share_expiry_minutes(PDO $pdo): int
{
    $settings = fetch_settings($pdo);
    $minutes = (int) ($settings['share_expiry_minutes'] ?? 1440);
    return max(1, $minutes);
}

function public_sharing_allowed(PDO $pdo): bool
{
    $settings = fetch_settings($pdo);
    return !empty($settings['public_sharing_enabled']);
}

function folder_passwords_allowed(PDO $pdo): bool
{
    $settings = fetch_settings($pdo);
    return !empty($settings['folder_passwords_enabled']);
}

function add_folder_to_zip(PDO $pdo, ZipArchive $zip, int $folderId, string $basePath = ''): void
{
    $folder = fetch_folder($pdo, $folderId);
    if (!$folder) {
        return;
    }
    $currentPath = $basePath === '' ? $folder['name'] : $basePath . '/' . $folder['name'];
    if (!empty($currentPath)) {
        $zip->addEmptyDir($currentPath);
    }
    $stmtFiles = $pdo->prepare('SELECT * FROM files WHERE folder_id = :id');
    $stmtFiles->execute([':id' => $folderId]);
    foreach ($stmtFiles->fetchAll() as $file) {
        $sourcePath = __DIR__ . '/uploads/' . $file['stored_name'];
        if (is_file($sourcePath)) {
            $zip->addFile($sourcePath, ($currentPath ? $currentPath . '/' : '') . $file['filename']);
        }
    }
    $stmtFolders = $pdo->prepare('SELECT id FROM folders WHERE parent_id = :id');
    $stmtFolders->execute([':id' => $folderId]);
    foreach ($stmtFolders->fetchAll() as $child) {
        add_folder_to_zip($pdo, $zip, (int) $child['id'], $currentPath);
    }
}

function create_folder_archive(PDO $pdo, int $folderId, array $user, bool $isAdmin): array
{
    $folder = fetch_folder($pdo, $folderId);
    if (!$folder) {
        throw new RuntimeException('Klasör bulunamadı.');
    }
    if (!$isAdmin && (int) $folder['user_id'] !== (int) $user['id']) {
        throw new RuntimeException('Bu klasörü arşivleme yetkiniz yok.');
    }
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('ZipArchive desteği mevcut değil.');
    }
    $archivesDir = __DIR__ . '/uploads/archives';
    if (!is_dir($archivesDir) && !mkdir($archivesDir, 0755, true) && !is_dir($archivesDir)) {
        throw new RuntimeException('Arşiv klasörü oluşturulamadı.');
    }
    $token = bin2hex(random_bytes(10));
    $archiveName = slugify($folder['name'] ?: 'klasor') . '-' . $token . '.zip';
    $archivePath = $archivesDir . '/' . $archiveName;
    $zip = new ZipArchive();
    if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Arşiv oluşturulamadı.');
    }
    add_folder_to_zip($pdo, $zip, $folderId);
    $zip->close();
    $_SESSION['archives'] = $_SESSION['archives'] ?? [];
    $_SESSION['archives'][$token] = [
        'path' => $archivePath,
        'name' => $folder['name'] . '.zip',
        'user_id' => (int) $user['id'],
        'created_at' => time(),
    ];
    return [
        'token' => $token,
        'download_url' => BASE_URL . '/download.php?archive=' . $token,
    ];
}

function create_files_archive(PDO $pdo, array $fileIds, array $user, bool $isAdmin, ?int $targetFolderId = null): array
{
    $fileIds = array_values(array_unique(array_map('intval', $fileIds)));
    $fileIds = array_filter($fileIds, static fn (int $id): bool => $id > 0);
    if (empty($fileIds)) {
        throw new RuntimeException('Seçilen dosya bulunamadı.');
    }

    $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id IN ($placeholders)");
    $stmt->execute($fileIds);
    $files = $stmt->fetchAll();
    if (!$files) {
        throw new RuntimeException('Dosyalar bulunamadı.');
    }

    $baseFolder = $files[0]['folder_id'] !== null ? (int) $files[0]['folder_id'] : null;
    $ownerId = (int) $files[0]['user_id'];

    foreach ($files as $file) {
        if (!$isAdmin && (int) $file['user_id'] !== (int) $user['id']) {
            throw new RuntimeException('Size ait olmayan dosyalar seçtiniz.');
        }
        if ((int) $file['user_id'] !== $ownerId) {
            throw new RuntimeException('Farklı kullanıcılara ait dosyalar birleştirilemez.');
        }
    }

    $destinationFolderId = $targetFolderId !== null ? $targetFolderId : $baseFolder;
    if ($destinationFolderId) {
        $destinationFolder = fetch_folder($pdo, $destinationFolderId);
        if (!$destinationFolder) {
            throw new RuntimeException('Hedef klasör bulunamadı.');
        }
        if (!$isAdmin && (int) $destinationFolder['user_id'] !== $ownerId) {
            throw new RuntimeException('Hedef klasör size ait değil.');
        }
    }

    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('ZipArchive desteği mevcut değil.');
    }

    $archiveStored = bin2hex(random_bytes(12)) . '.zip';
    $archivePath = __DIR__ . '/uploads/' . $archiveStored;
    $zip = new ZipArchive();
    if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Arşiv oluşturulamadı.');
    }
    foreach ($files as $file) {
        $source = __DIR__ . '/uploads/' . $file['stored_name'];
        if (is_file($source)) {
            $zip->addFile($source, $file['filename']);
        }
    }
    $zip->close();

    $archiveFilename = 'Arsiv-' . date('Ymd-His') . '.zip';
    $newFileId = store_file($pdo, [
        'filename' => $archiveFilename,
        'stored_name' => $archiveStored,
        'size' => filesize($archivePath),
        'type' => 'application/zip',
        'uploader_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'user_id' => $isAdmin ? $ownerId : $user['id'],
        'folder_id' => $destinationFolderId,
    ]);

    $slug = slugify(pathinfo($archiveFilename, PATHINFO_FILENAME));
    $downloadUrl = BASE_URL . '/file/' . $newFileId . '-' . $slug . '.zip';

    return [
        'file_id' => $newFileId,
        'filename' => $archiveFilename,
        'download_url' => $downloadUrl,
    ];
}

function create_selection_archive(PDO $pdo, array $fileIds, array $folderIds, array $user, bool $isAdmin, ?int $contextFolderId = null): array
{
    $fileIds = array_values(array_unique(array_map('intval', $fileIds)));
    $folderIds = array_values(array_unique(array_map('intval', $folderIds)));
    $fileIds = array_filter($fileIds, static fn (int $id): bool => $id > 0);
    $folderIds = array_filter($folderIds, static fn (int $id): bool => $id > 0);

    if (empty($fileIds) && empty($folderIds)) {
        throw new RuntimeException('Arşivlenecek öğe seçilmedi.');
    }

    $ownerId = (int) $user['id'];
    $resolvedOwner = null;

    $files = [];
    if (!empty($fileIds)) {
        $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
        $stmt = $pdo->prepare("SELECT * FROM files WHERE id IN ($placeholders)");
        $stmt->execute($fileIds);
        $files = $stmt->fetchAll();
        if (!$files) {
            throw new RuntimeException('Dosyalar bulunamadı.');
        }
        foreach ($files as $file) {
            if (!$isAdmin && (int) $file['user_id'] !== $ownerId) {
                throw new RuntimeException('Size ait olmayan dosyalar seçtiniz.');
            }
            $resolvedOwner ??= (int) $file['user_id'];
            if ($resolvedOwner !== (int) $file['user_id']) {
                throw new RuntimeException('Farklı kullanıcılara ait öğeler seçilemez.');
            }
        }
    }

    $folders = [];
    if (!empty($folderIds)) {
        $placeholders = implode(',', array_fill(0, count($folderIds), '?'));
        $stmt = $pdo->prepare("SELECT * FROM folders WHERE id IN ($placeholders)");
        $stmt->execute($folderIds);
        $folders = $stmt->fetchAll();
        if (!$folders) {
            throw new RuntimeException('Klasörler bulunamadı.');
        }
        foreach ($folders as $folder) {
            if (!$isAdmin && (int) $folder['user_id'] !== $ownerId) {
                throw new RuntimeException('Size ait olmayan klasör seçtiniz.');
            }
            $resolvedOwner ??= (int) $folder['user_id'];
            if ($resolvedOwner !== (int) $folder['user_id']) {
                throw new RuntimeException('Farklı kullanıcılara ait klasörler seçilemez.');
            }
        }
    }

    $resolvedOwner ??= $ownerId;

    $destinationFolderId = $contextFolderId !== null ? $contextFolderId : null;
    if ($destinationFolderId) {
        $targetFolder = fetch_folder($pdo, $destinationFolderId);
        if (!$targetFolder) {
            throw new RuntimeException('Hedef klasör bulunamadı.');
        }
        if (!$isAdmin && (int) $targetFolder['user_id'] !== $resolvedOwner) {
            throw new RuntimeException('Hedef klasör size ait değil.');
        }
    }

    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('ZipArchive desteği mevcut değil.');
    }

    $archiveStored = bin2hex(random_bytes(14)) . '.zip';
    $archivePath = __DIR__ . '/uploads/' . $archiveStored;
    $zip = new ZipArchive();
    if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Arşiv oluşturulamadı.');
    }

    foreach ($files as $file) {
        $source = __DIR__ . '/uploads/' . $file['stored_name'];
        if (is_file($source)) {
            $zip->addFile($source, $file['filename']);
        }
    }

    foreach ($folders as $folder) {
        add_folder_to_zip($pdo, $zip, (int) $folder['id']);
    }

    $zip->close();

    $archiveFilename = 'Secili-' . date('Ymd-His') . '.zip';
    $newFileId = store_file($pdo, [
        'filename' => $archiveFilename,
        'stored_name' => $archiveStored,
        'size' => filesize($archivePath),
        'type' => 'application/zip',
        'uploader_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'user_id' => $resolvedOwner,
        'folder_id' => $destinationFolderId,
    ]);

    $slug = slugify(pathinfo($archiveFilename, PATHINFO_FILENAME));
    $downloadUrl = BASE_URL . '/file/' . $newFileId . '-' . $slug . '.zip';

    return [
        'file_id' => $newFileId,
        'filename' => $archiveFilename,
        'download_url' => $downloadUrl,
    ];
}

function format_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    return number_format($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
}

function collect_usage_timeseries(PDO $pdo, string $range = 'daily'): array
{
    $range = strtolower($range);
    switch ($range) {
        case 'weekly':
            $sql = "SELECT DATE_FORMAT(uploaded_at, '%x-W%v') AS label, MIN(DATE(uploaded_at)) AS sort_key, COUNT(*) AS uploads, COALESCE(SUM(size), 0) AS bytes FROM files WHERE uploaded_at >= DATE_SUB(NOW(), INTERVAL 12 WEEK) GROUP BY DATE_FORMAT(uploaded_at, '%x-W%v') ORDER BY sort_key";
            break;
        case 'monthly':
            $sql = "SELECT DATE_FORMAT(uploaded_at, '%Y-%m') AS label, MIN(DATE(uploaded_at)) AS sort_key, COUNT(*) AS uploads, COALESCE(SUM(size), 0) AS bytes FROM files WHERE uploaded_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(uploaded_at, '%Y-%m') ORDER BY sort_key";
            break;
        case 'yearly':
            $sql = "SELECT DATE_FORMAT(uploaded_at, '%Y') AS label, MIN(DATE(uploaded_at)) AS sort_key, COUNT(*) AS uploads, COALESCE(SUM(size), 0) AS bytes FROM files WHERE uploaded_at >= DATE_SUB(NOW(), INTERVAL 5 YEAR) GROUP BY DATE_FORMAT(uploaded_at, '%Y') ORDER BY sort_key";
            break;
        case 'daily':
        default:
            $sql = "SELECT DATE(uploaded_at) AS label, DATE(uploaded_at) AS sort_key, COUNT(*) AS uploads, COALESCE(SUM(size), 0) AS bytes FROM files WHERE uploaded_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) GROUP BY DATE(uploaded_at) ORDER BY sort_key";
            break;
    }

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    return array_map(static function (array $row): array {
        return [
            'label' => (string) $row['label'],
            'uploads' => (int) $row['uploads'],
            'bytes' => (int) $row['bytes'],
        ];
    }, $rows);
}

function ensureDefaultPackages(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM packages')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare('INSERT INTO packages (name, storage_limit, max_concurrent_uploads, features, allowed_mime_types, price) VALUES
            (:name1, :storage1, :upload1, :features1, :mime1, :price1),
            (:name2, :storage2, :upload2, :features2, :mime2, :price2),
            (:name3, :storage3, :upload3, :features3, :mime3, :price3)');
        $stmt->execute([
            ':name1' => 'Başlangıç',
            ':storage1' => 524288000,
            ':upload1' => 2,
            ':features1' => json_encode(['Temel depolama', 'Sınırlı destek']),
            ':mime1' => null,
            ':price1' => 0.00,
            ':name2' => 'Profesyonel',
            ':storage2' => 2147483648,
            ':upload2' => 5,
            ':features2' => json_encode(['Gelişmiş depolama', 'Öncelikli destek', 'Analitik raporlar']),
            ':mime2' => null,
            ':price2' => 14.99,
            ':name3' => 'Kurumsal',
            ':storage3' => 5368709120,
            ':upload3' => 10,
            ':features3' => json_encode(['Sınırsız paylaşım', 'Takım yönetimi', 'Özel SLA']),
            ':mime3' => null,
            ':price3' => 49.99,
        ]);
    }
}

function ensureDefaultSettings(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        meta_title VARCHAR(255) DEFAULT NULL,
        meta_description TEXT DEFAULT NULL,
        header_html TEXT DEFAULT NULL,
        footer_html TEXT DEFAULT NULL,
        logo VARCHAR(255) DEFAULT NULL,
        favicon VARCHAR(255) DEFAULT NULL,
        mail_enabled TINYINT(1) DEFAULT 0,
        mail_method ENUM(\'phpmail\', \'smtp\') DEFAULT \'smtp\',
        mail_host VARCHAR(255) DEFAULT NULL,
        mail_port INT DEFAULT NULL,
        mail_username VARCHAR(255) DEFAULT NULL,
        mail_password VARCHAR(255) DEFAULT NULL,
        mail_encryption VARCHAR(10) DEFAULT NULL,
        mail_from_name VARCHAR(150) DEFAULT NULL,
        mail_from_address VARCHAR(191) DEFAULT NULL,
        analytics_code TEXT DEFAULT NULL,
        analytics_enabled TINYINT(1) DEFAULT 0,
        allowed_mime_types TEXT DEFAULT NULL,
        share_expiry_minutes INT DEFAULT 1440,
        public_sharing_enabled TINYINT(1) DEFAULT 1,
        folder_passwords_enabled TINYINT(1) DEFAULT 1,
        share_download_delay INT DEFAULT 0,
        share_password_required TINYINT(1) DEFAULT 0,
        share_stats_enabled TINYINT(1) DEFAULT 1,
        ad_dashboard_html TEXT DEFAULT NULL,
        ad_share_top_html TEXT DEFAULT NULL,
        ad_share_bottom_html TEXT DEFAULT NULL,
        payment_currency VARCHAR(10) DEFAULT \'TRY\',
        iyzico_enabled TINYINT(1) DEFAULT 0,
        iyzico_api_key VARCHAR(191) DEFAULT NULL,
        iyzico_secret_key VARCHAR(191) DEFAULT NULL,
        iyzico_base_url VARCHAR(191) DEFAULT NULL,
        stripe_enabled TINYINT(1) DEFAULT 0,
        stripe_api_key VARCHAR(191) DEFAULT NULL,
        stripe_publishable_key VARCHAR(191) DEFAULT NULL,
        stripe_webhook_secret VARCHAR(191) DEFAULT NULL,
        bank_transfer_enabled TINYINT(1) DEFAULT 1,
        bank_transfer_instructions TEXT DEFAULT NULL,
        auto_archive_enabled TINYINT(1) DEFAULT 0,
        auto_delete_enabled TINYINT(1) DEFAULT 0,
        archive_after_days INT DEFAULT NULL,
        delete_after_days INT DEFAULT NULL,
        geoip_database_path VARCHAR(255) DEFAULT NULL,
        realtime_updates_enabled TINYINT(1) DEFAULT 0,
        plesk_api_url VARCHAR(255) DEFAULT NULL,
        plesk_api_login VARCHAR(191) DEFAULT NULL,
        plesk_api_password VARCHAR(191) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    if (!schemaColumnExists($pdo, 'settings', 'mail_enabled')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN mail_enabled TINYINT(1) DEFAULT 0 AFTER favicon');
    }
    if (!schemaColumnExists($pdo, 'settings', 'mail_method')) {
        $pdo->exec("ALTER TABLE settings ADD COLUMN mail_method ENUM('phpmail','smtp') DEFAULT 'smtp' AFTER mail_enabled");
    }
    if (!schemaColumnExists($pdo, 'settings', 'mail_from_name')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN mail_from_name VARCHAR(150) DEFAULT NULL AFTER mail_encryption');
    }
    if (!schemaColumnExists($pdo, 'settings', 'mail_from_address')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN mail_from_address VARCHAR(191) DEFAULT NULL AFTER mail_from_name');
    }
    if (!schemaColumnExists($pdo, 'settings', 'allowed_mime_types')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN allowed_mime_types TEXT DEFAULT NULL AFTER analytics_enabled');
    }
    if (!schemaColumnExists($pdo, 'settings', 'share_expiry_minutes')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN share_expiry_minutes INT DEFAULT 1440 AFTER allowed_mime_types');
    }
    if (!schemaColumnExists($pdo, 'settings', 'public_sharing_enabled')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN public_sharing_enabled TINYINT(1) DEFAULT 1 AFTER share_expiry_minutes');
    }
    if (!schemaColumnExists($pdo, 'settings', 'folder_passwords_enabled')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN folder_passwords_enabled TINYINT(1) DEFAULT 1 AFTER public_sharing_enabled');
    }
    if (!schemaColumnExists($pdo, 'settings', 'share_download_delay')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN share_download_delay INT DEFAULT 0 AFTER folder_passwords_enabled');
    }
    if (!schemaColumnExists($pdo, 'settings', 'share_password_required')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN share_password_required TINYINT(1) DEFAULT 0 AFTER share_download_delay');
    }
    if (!schemaColumnExists($pdo, 'settings', 'share_stats_enabled')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN share_stats_enabled TINYINT(1) DEFAULT 1 AFTER share_password_required');
    }
    if (!schemaColumnExists($pdo, 'settings', 'ad_dashboard_html')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN ad_dashboard_html TEXT DEFAULT NULL AFTER share_stats_enabled');
    }
    if (!schemaColumnExists($pdo, 'settings', 'ad_share_top_html')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN ad_share_top_html TEXT DEFAULT NULL AFTER ad_dashboard_html');
    }
    if (!schemaColumnExists($pdo, 'settings', 'ad_share_bottom_html')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN ad_share_bottom_html TEXT DEFAULT NULL AFTER ad_share_top_html');
    }
    if (!schemaColumnExists($pdo, 'settings', 'payment_currency')) {
        $pdo->exec("ALTER TABLE settings ADD COLUMN payment_currency VARCHAR(10) DEFAULT 'TRY' AFTER ad_share_bottom_html");
    }
    if (!schemaColumnExists($pdo, 'settings', 'iyzico_enabled')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN iyzico_enabled TINYINT(1) DEFAULT 0 AFTER payment_currency');
    }
    if (!schemaColumnExists($pdo, 'settings', 'iyzico_api_key')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN iyzico_api_key VARCHAR(191) DEFAULT NULL AFTER iyzico_enabled');
    }
    if (!schemaColumnExists($pdo, 'settings', 'iyzico_secret_key')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN iyzico_secret_key VARCHAR(191) DEFAULT NULL AFTER iyzico_api_key');
    }
    if (!schemaColumnExists($pdo, 'settings', 'iyzico_base_url')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN iyzico_base_url VARCHAR(191) DEFAULT NULL AFTER iyzico_secret_key');
    }
    if (!schemaColumnExists($pdo, 'settings', 'stripe_enabled')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN stripe_enabled TINYINT(1) DEFAULT 0 AFTER iyzico_base_url');
    }
    if (!schemaColumnExists($pdo, 'settings', 'stripe_api_key')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN stripe_api_key VARCHAR(191) DEFAULT NULL AFTER stripe_enabled');
    }
    if (!schemaColumnExists($pdo, 'settings', 'stripe_publishable_key')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN stripe_publishable_key VARCHAR(191) DEFAULT NULL AFTER stripe_api_key');
    }
    if (!schemaColumnExists($pdo, 'settings', 'stripe_webhook_secret')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN stripe_webhook_secret VARCHAR(191) DEFAULT NULL AFTER stripe_publishable_key');
    }
    if (!schemaColumnExists($pdo, 'settings', 'bank_transfer_enabled')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN bank_transfer_enabled TINYINT(1) DEFAULT 1 AFTER stripe_webhook_secret');
    }
    if (!schemaColumnExists($pdo, 'settings', 'bank_transfer_instructions')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN bank_transfer_instructions TEXT DEFAULT NULL AFTER bank_transfer_enabled');
    }
    if (!schemaColumnExists($pdo, 'settings', 'auto_archive_enabled')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN auto_archive_enabled TINYINT(1) DEFAULT 0 AFTER bank_transfer_instructions');
    }
    if (!schemaColumnExists($pdo, 'settings', 'auto_delete_enabled')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN auto_delete_enabled TINYINT(1) DEFAULT 0 AFTER auto_archive_enabled');
    }
    if (!schemaColumnExists($pdo, 'settings', 'archive_after_days')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN archive_after_days INT DEFAULT NULL AFTER auto_delete_enabled');
    }
    if (!schemaColumnExists($pdo, 'settings', 'delete_after_days')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN delete_after_days INT DEFAULT NULL AFTER archive_after_days');
    }
    if (!schemaColumnExists($pdo, 'settings', 'geoip_database_path')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN geoip_database_path VARCHAR(255) DEFAULT NULL AFTER delete_after_days');
    }
    if (!schemaColumnExists($pdo, 'settings', 'realtime_updates_enabled')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN realtime_updates_enabled TINYINT(1) DEFAULT 0 AFTER geoip_database_path');
    }
    if (!schemaColumnExists($pdo, 'settings', 'plesk_api_url')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN plesk_api_url VARCHAR(255) DEFAULT NULL AFTER realtime_updates_enabled');
    }
    if (!schemaColumnExists($pdo, 'settings', 'plesk_api_login')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN plesk_api_login VARCHAR(191) DEFAULT NULL AFTER plesk_api_url');
    }
    if (!schemaColumnExists($pdo, 'settings', 'plesk_api_password')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN plesk_api_password VARCHAR(191) DEFAULT NULL AFTER plesk_api_login');
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
    if ($count === 0) {
        $defaultMime = json_encode([
            'image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain',
            'application/zip', 'application/x-rar-compressed',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
        $stmt = $pdo->prepare('INSERT INTO settings (meta_title, meta_description, header_html, footer_html, analytics_enabled, allowed_mime_types, share_expiry_minutes, public_sharing_enabled, folder_passwords_enabled, share_download_delay, payment_currency, bank_transfer_enabled)
            VALUES (:title, :description, :header, :footer, :enabled, :mime, :expiry, :public_share, :folder_password, :delay, :currency, :bank_enabled)');
        $stmt->execute([
            ':title' => 'NoaSoft Dosya Deposu',
            ':description' => 'Güvenli ve hızlı dosya yükleme platformu.',
            ':header' => '',
            ':footer' => '<p>© ' . date('Y') . ' NoaSoft</p>',
            ':enabled' => 0,
            ':mime' => $defaultMime,
            ':expiry' => 1440,
            ':public_share' => 1,
            ':folder_password' => 1,
            ':delay' => 0,
            ':currency' => 'TRY',
            ':bank_enabled' => 1,
        ]);
    }
}

function is_ajax_request(): bool
{
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }

    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (str_contains($script, '/api/')) {
        return true;
    }

    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return stripos($accept, 'application/json') !== false;
}


function fetch_settings(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM settings LIMIT 1');
    $settings = $stmt->fetch();
    if ($settings) {
        $settings['header_html'] = str_replace([
            "<div class='topbar'>Hoş geldiniz!</div>",
            '<div class=\"topbar\">Hoş geldiniz!</div>'
        ], '', $settings['header_html'] ?? '');
    }
    return $settings ?: [];
}

function require_login_redirect(): void
{
    if (!current_user()) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}

function redirect_if_authenticated(): void
{
    if (current_user()) {
        header('Location: ' . BASE_URL . '/client');
        exit;
    }
}

function allowed_mime_types(PDO $pdo, ?int $packageId = null): array
{
    static $cache = [];
    $key = $packageId ? 'pkg_' . $packageId : 'settings';
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $list = [];
    if ($packageId) {
        $stmt = $pdo->prepare('SELECT allowed_mime_types FROM packages WHERE id = :id');
        $stmt->execute([':id' => $packageId]);
        $rawPackage = $stmt->fetchColumn();
        if ($rawPackage) {
            $decoded = json_decode((string) $rawPackage, true);
            if (is_array($decoded)) {
                $list = $decoded;
            } else {
                $list = preg_split('/[,\n]/', (string) $rawPackage) ?: [];
            }
        }
    }

    if (empty($list)) {
        $settings = fetch_settings($pdo);
        $raw = $settings['allowed_mime_types'] ?? '';
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $list = $decoded;
            } else {
                $list = preg_split('/[,\n]/', $raw) ?: [];
            }
        }
    }

    $list = array_filter(array_map('trim', $list));
    if (empty($list)) {
        $list = DEFAULT_ALLOWED_MIME_TYPES;
    }

    $cache[$key] = array_values(array_unique($list));
    return $cache[$key];
}

function validate_uploaded_file(array $file, ?PDO $pdo = null, ?array $allowedOverride = null): array
{
    $maxSize = 50 * 1024 * 1024;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Dosya yüklenemedi.');
    }
    if ($file['size'] > $maxSize) {
        throw new RuntimeException('Dosya boyutu 50MB sınırını aşıyor.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedTypes = $allowedOverride ?? ($pdo ? allowed_mime_types($pdo) : DEFAULT_ALLOWED_MIME_TYPES);
    if (!in_array($mimeType, $allowedTypes, true)) {
        throw new RuntimeException('Desteklenmeyen dosya türü.');
    }
    return [$mimeType, $file['size']];
}

function package_for_user(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT p.* FROM users u LEFT JOIN packages p ON p.id = u.package_id WHERE u.id = :id');
    $stmt->execute([':id' => $userId]);
    $pkg = $stmt->fetch();
    return $pkg ?: null;
}

function user_storage_usage(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total_files, COALESCE(SUM(size), 0) AS total_size FROM files WHERE user_id = :id');
    $stmt->execute([':id' => $userId]);
    $data = $stmt->fetch();
    return $data ?: ['total_files' => 0, 'total_size' => 0];
}

function can_upload(PDO $pdo, int $userId, int $fileSize): bool
{
    $package = package_for_user($pdo, $userId);
    if (!$package) {
        return true;
    }
    $usage = user_storage_usage($pdo, $userId);
    if (($usage['total_size'] + $fileSize) > (int) $package['storage_limit']) {
        return false;
    }
    return true;
}
function find_user_by_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function login_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'package_id' => $user['package_id'],
        'email_verified' => $user['email_verified'],
    ];
}

function logout_user(): void
{
    unset($_SESSION['user']);
}

function ensure_admin_exists(PDO $pdo): void
{
    $targetEmail = 'admin@noasoft.org';
    $targetPassword = password_hash('admin', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $targetEmail]);
    $existing = $stmt->fetch();

    if ($existing) {
        $pdo->prepare('UPDATE users SET role = "admin", name = :name, password_hash = :hash, email_verified = 1 WHERE id = :id')->execute([
            ':name' => 'Sistem Yöneticisi',
            ':hash' => $targetPassword,
            ':id' => $existing['id'],
        ]);
        return;
    }

    $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
    $firstAdmin = $adminStmt->fetch();
    if ($firstAdmin) {
        $pdo->prepare('UPDATE users SET email = :email, name = :name, password_hash = :hash, email_verified = 1 WHERE id = :id')->execute([
            ':email' => $targetEmail,
            ':name' => 'Sistem Yöneticisi',
            ':hash' => $targetPassword,
            ':id' => $firstAdmin['id'],
        ]);
        return;
    }

    $insert = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, email_verified) VALUES (:name, :email, :password_hash, 'admin', 1)");
    $insert->execute([
        ':name' => 'Sistem Yöneticisi',
        ':email' => $targetEmail,
        ':password_hash' => $targetPassword,
    ]);
}

function mailer_instance(PDO $pdo): ?PHPMailer
{
    $settings = fetch_settings($pdo);
    if (empty($settings['mail_enabled'])) {
        return null;
    }
    $mailer = new PHPMailer(true);
    $mailer->CharSet = 'UTF-8';
    $mailer->isHTML(true);
    $fromAddress = $settings['mail_from_address'] ?: 'no-reply@fileupload.noasoft.org';
    $fromName = $settings['mail_from_name'] ?: 'NoaSoft Depo';
    $mailer->setFrom($fromAddress, $fromName);

    if (($settings['mail_method'] ?? 'smtp') === 'smtp') {
        $mailer->isSMTP();
        $mailer->Host = $settings['mail_host'] ?? '';
        $mailer->Port = (int) ($settings['mail_port'] ?? 587);
        $mailer->SMTPAuth = true;
        $mailer->Username = $settings['mail_username'] ?? '';
        $mailer->Password = $settings['mail_password'] ?? '';
        $encryption = $settings['mail_encryption'] ?? '';
        if ($encryption === 'tls' || $encryption === 'ssl') {
            $mailer->SMTPSecure = $encryption;
        }
    }

    return $mailer;
}

function notify_user(PDO $pdo, string $email, string $subject, string $htmlBody): void
{
    $mailer = mailer_instance($pdo);
    if (!$mailer) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $headers = implode("\r\n", [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: no-reply@fileupload.noasoft.org'
            ]);
            @mail($email, $subject, $htmlBody, $headers);
        }
        return;
    }

    try {
        $mailer->clearAllRecipients();
        $mailer->addAddress($email);
        $mailer->Subject = $subject;
        $mailer->Body = $htmlBody;
        $mailer->AltBody = strip_tags($htmlBody);
        $mailer->send();
    } catch (Throwable $e) {
        error_log('Mail gönderilemedi: ' . $e->getMessage());
    }
}

function geoip_lookup(PDO $pdo, string $ip): array
{
    $settings = fetch_settings($pdo);
    $dbPath = $settings['geoip_database_path'] ?? '';
    if (!$dbPath || !is_readable($dbPath)) {
        return [];
    }

    try {
        $reader = new Reader($dbPath);
        $record = $reader->city($ip);
        return [
            'country' => $record->country->name ?? null,
            'city' => $record->city->name ?? null,
            'lat' => $record->location->latitude ?? null,
            'lon' => $record->location->longitude ?? null,
        ];
    } catch (Throwable $e) {
        error_log('GeoIP sorgusu başarısız: ' . $e->getMessage());
        return [];
    }
}

function device_details(string $userAgent): array
{
    $detector = new DeviceDetector($userAgent);
    $detector->discardBotInformation();
    $detector->parse();

    if ($detector->isBot()) {
        return [
            'device_type' => 'bot',
            'os' => null,
            'browser' => null,
            'platform' => null,
        ];
    }

    return [
        'device_type' => $detector->getDeviceName() ?: ($detector->getDevice() ?: null),
        'os' => $detector->getOs('name') ?: null,
        'browser' => $detector->getClient('name') ?: null,
        'platform' => $detector->getBrandName() ?: null,
    ];
}

function log_file_access(PDO $pdo, array $file, ?array $user = null, ?string $shareToken = null): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $geo = geoip_lookup($pdo, $ip);
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $device = $ua ? device_details($ua) : [];

    $stmt = $pdo->prepare('INSERT INTO file_access_logs (file_id, user_id, share_token, ip_address, country, city, latitude, longitude, device_type, os, browser, platform)
        VALUES (:file_id, :user_id, :share_token, :ip, :country, :city, :lat, :lon, :device_type, :os, :browser, :platform)');
    $stmt->execute([
        ':file_id' => $file['id'],
        ':user_id' => $user['id'] ?? null,
        ':share_token' => $shareToken,
        ':ip' => $ip,
        ':country' => $geo['country'] ?? null,
        ':city' => $geo['city'] ?? null,
        ':lat' => $geo['lat'] ?? null,
        ':lon' => $geo['lon'] ?? null,
        ':device_type' => $device['device_type'] ?? null,
        ':os' => $device['os'] ?? null,
        ':browser' => $device['browser'] ?? null,
        ':platform' => $device['platform'] ?? null,
    ]);
}

function apply_retention_policies(PDO $pdo): void
{
    $settings = fetch_settings($pdo);
    if (empty($settings['auto_archive_enabled']) && empty($settings['auto_delete_enabled'])) {
        return;
    }

    $now = new DateTimeImmutable('now');
    if (!empty($settings['auto_archive_enabled']) && !empty($settings['archive_after_days'])) {
        $threshold = $now->sub(new DateInterval('P' . (int) $settings['archive_after_days'] . 'D'))->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare('SELECT * FROM files WHERE uploaded_at <= :threshold AND (retention_policy_id IS NULL OR retention_policy_id = 0)');
        $stmt->execute([':threshold' => $threshold]);
        $files = $stmt->fetchAll();
        foreach ($files as $file) {
            archive_file($file);
        }
    }

    if (!empty($settings['auto_delete_enabled']) && !empty($settings['delete_after_days'])) {
        $threshold = $now->sub(new DateInterval('P' . (int) $settings['delete_after_days'] . 'D'))->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare('SELECT * FROM files WHERE uploaded_at <= :threshold');
        $stmt->execute([':threshold' => $threshold]);
        $files = $stmt->fetchAll();
        foreach ($files as $file) {
            delete_file_completely($pdo, (int) $file['id']);
        }
    }
}

function archive_file(array $file): void
{
    $uploadDir = __DIR__ . '/uploads';
    $archiveDir = __DIR__ . '/archives';
    if (!is_dir($archiveDir)) {
        mkdir($archiveDir, 0775, true);
    }
    $source = $uploadDir . '/' . $file['stored_name'];
    if (!is_file($source)) {
        return;
    }
    $zipPath = $archiveDir . '/' . $file['stored_name'] . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $zip->addFile($source, $file['filename']);
        $zip->close();
    }
}

function delete_file_completely(PDO $pdo, int $fileId): void
{
    $stmt = $pdo->prepare('SELECT * FROM files WHERE id = :id');
    $stmt->execute([':id' => $fileId]);
    $file = $stmt->fetch();
    if (!$file) {
        return;
    }
    $path = __DIR__ . '/uploads/' . $file['stored_name'];
    if (is_file($path)) {
        @unlink($path);
    }
    $pdo->prepare('DELETE FROM files WHERE id = :id')->execute([':id' => $fileId]);
}

function iyzico_client(PDO $pdo): ?Options
{
    $settings = fetch_settings($pdo);
    if (empty($settings['iyzico_enabled'])) {
        return null;
    }
    $options = new Options();
    $options->setApiKey($settings['iyzico_api_key'] ?? '');
    $options->setSecretKey($settings['iyzico_secret_key'] ?? '');
    $options->setBaseUrl($settings['iyzico_base_url'] ?: 'https://api.iyzipay.com');
    return $options;
}

function stripe_client(PDO $pdo): ?StripeClient
{
    $settings = fetch_settings($pdo);
    if (empty($settings['stripe_enabled']) || empty($settings['stripe_api_key'])) {
        return null;
    }
    return new Stripe\StripeClient($settings['stripe_api_key']);
}

function initiate_payment(PDO $pdo, int $userId, int $packageId, string $provider, string $successUrl, string $cancelUrl): array
{
    $stmt = $pdo->prepare('SELECT * FROM packages WHERE id = :id');
    $stmt->execute([':id' => $packageId]);
    $package = $stmt->fetch();
    if (!$package) {
        throw new RuntimeException('Paket bulunamadı.');
    }

    $settings = fetch_settings($pdo);
    $currency = $settings['payment_currency'] ?? 'TRY';

    $pdo->prepare('INSERT INTO transactions (user_id, package_id, amount, currency, provider, status) VALUES (:user_id, :package_id, :amount, :currency, :provider, :status)')->execute([
        ':user_id' => $userId,
        ':package_id' => $packageId,
        ':amount' => $package['price'],
        ':currency' => $currency,
        ':provider' => $provider,
        ':status' => 'pending',
    ]);
    $transactionId = (int) $pdo->lastInsertId();

    switch ($provider) {
        case 'iyzico':
            $options = iyzico_client($pdo);
            if (!$options) {
                throw new RuntimeException('Iyzico yapılandırması eksik.');
            }
            $request = new CreateCheckoutFormInitializeRequest();
            $request->setPrice(number_format((float) $package['price'], 2, '.', ''));
            $request->setPaidPrice(number_format((float) $package['price'], 2, '.', ''));
            $request->setCurrency($currency);
            $request->setCallbackUrl($successUrl);
            $basketItem = new BasketItem();
            $basketItem->setId((string) $packageId);
            $basketItem->setName($package['name']);
            $basketItem->setCategory1('Dosya Deposu');
            $basketItem->setItemType(BasketItemType::VIRTUAL);
            $basketItem->setPrice(number_format((float) $package['price'], 2, '.', ''));
            $request->setBasketItems([$basketItem]);
            $checkout = CheckoutFormInitialize::create($request, $options);
            $token = $checkout->getToken();
            $paymentUrl = $checkout->getPaymentPageUrl();
            $pdo->prepare('UPDATE transactions SET reference = :reference, payload = :payload WHERE id = :id')->execute([
                ':reference' => $token,
                ':payload' => json_encode(['checkout_form_content' => $checkout->getCheckoutFormContent()], JSON_THROW_ON_ERROR),
                ':id' => $transactionId,
            ]);
            return ['transaction_id' => $transactionId, 'payment_url' => $paymentUrl];
        case 'stripe':
            $client = stripe_client($pdo);
            if (!$client) {
                throw new RuntimeException('Stripe yapılandırması eksik.');
            }
            $session = $client->checkout->sessions->create([
                'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl,
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($currency),
                        'product_data' => ['name' => $package['name']],
                        'unit_amount' => (int) round($package['price'] * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'metadata' => ['transaction_id' => $transactionId],
            ]);
            $pdo->prepare('UPDATE transactions SET reference = :reference, payload = :payload WHERE id = :id')->execute([
                ':reference' => $session->id,
                ':payload' => json_encode($session->toArray(), JSON_THROW_ON_ERROR),
                ':id' => $transactionId,
            ]);
            return ['transaction_id' => $transactionId, 'payment_url' => $session->url];
        case 'bank_transfer':
        default:
            return ['transaction_id' => $transactionId];
    }
}

function complete_transaction(PDO $pdo, int $transactionId, string $status, ?string $reference = null, ?array $payload = null): void
{
    $stmt = $pdo->prepare('UPDATE transactions SET status = :status, paid_at = CASE WHEN :status = "paid" THEN NOW() ELSE paid_at END, reference = COALESCE(:reference, reference), payload = COALESCE(:payload, payload) WHERE id = :id');
    $stmt->execute([
        ':status' => $status,
        ':reference' => $reference,
        ':payload' => $payload ? json_encode($payload, JSON_THROW_ON_ERROR) : null,
        ':id' => $transactionId,
    ]);

    $transaction = fetch_transaction($pdo, $transactionId);
    if (!$transaction) {
        return;
    }

    $userStmt = $pdo->prepare('SELECT id, email, name FROM users WHERE id = :id');
    $userStmt->execute([':id' => $transaction['user_id']]);
    $user = $userStmt->fetch();

    $packageStmt = $pdo->prepare('SELECT name FROM packages WHERE id = :id');
    $packageStmt->execute([':id' => $transaction['package_id']]);
    $package = $packageStmt->fetch();
    $packageName = $package['name'] ?? 'Paket';

    if ($status === 'paid') {
        assign_package_to_user($pdo, (int) $transaction['user_id'], (int) $transaction['package_id']);
        plesk_sync_package($pdo, (int) $transaction['user_id']);
        if ($user) {
            notify_user(
                $pdo,
                $user['email'],
                'Paketiniz aktif edildi',
                '<p>"' . sanitize($packageName) . '" paketi başarıyla aktif edildi.</p>'
            );
        }
    } elseif ($status === 'failed' && $user) {
        notify_user(
            $pdo,
            $user['email'],
            'Ödeme işlemi başarısız oldu',
            '<p>"' . sanitize($packageName) . '" paketi için ödeme başarısız oldu. Lütfen tekrar deneyin.</p>'
        );
    }
}

function fetch_transaction(PDO $pdo, int $transactionId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id');
    $stmt->execute([':id' => $transactionId]);
    $tx = $stmt->fetch();
    return $tx ?: null;
}

function assign_package_to_user(PDO $pdo, int $userId, int $packageId): void
{
    $stmt = $pdo->prepare('UPDATE users SET package_id = :package_id WHERE id = :id');
    $stmt->execute([
        ':package_id' => $packageId,
        ':id' => $userId,
    ]);
}

function plesk_sync_package(PDO $pdo, int $userId): void
{
    $settings = fetch_settings($pdo);
    if (empty($settings['plesk_api_url']) || empty($settings['plesk_api_login']) || empty($settings['plesk_api_password'])) {
        return;
    }

    $package = package_for_user($pdo, $userId);
    if (!$package) {
        return;
    }

    $ch = curl_init(rtrim($settings['plesk_api_url'], '/') . '/api/v2/clients/' . $userId . '/limits');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $settings['plesk_api_login'] . ':' . $settings['plesk_api_password'],
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'disk_space' => (int) $package['storage_limit'],
            'max_concurrent_uploads' => (int) $package['max_concurrent_uploads'],
        ]),
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        error_log('Plesk eşitleme başarısız: ' . curl_error($ch));
    }
    curl_close($ch);
}

function push_realtime_event(PDO $pdo, string $channel, array $payload, ?int $userId = null): void
{
    $settings = fetch_settings($pdo);
    if (empty($settings['realtime_updates_enabled'])) {
        return;
    }
    $stmt = $pdo->prepare('INSERT INTO realtime_events (user_id, channel, payload) VALUES (:user_id, :channel, :payload)');
    $stmt->execute([
        ':user_id' => $userId,
        ':channel' => $channel,
        ':payload' => json_encode($payload, JSON_THROW_ON_ERROR),
    ]);
}

