<?php
declare(strict_types=1);

function ensureDatabaseSchema(PDO $pdo): void
{
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    storage_limit BIGINT NOT NULL,
    max_concurrent_uploads INT NOT NULL,
    features TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

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
CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    size BIGINT NOT NULL,
    type VARCHAR(100) NOT NULL,
    uploader_ip VARCHAR(45) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT DEFAULT NULL,
    INDEX (user_id),
    CONSTRAINT fk_files_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
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
    status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transactions_packages FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
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
    if (!current_user()) {
        http_response_code(401);
        exit(json_encode(['status' => 'error', 'message' => 'Authentication required.']));
    }
    if ($admin && !is_admin()) {
        http_response_code(403);
        exit(json_encode(['status' => 'error', 'message' => 'Admin privileges required.']));
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
    $stmt = $pdo->prepare('INSERT INTO files (filename, stored_name, size, type, uploader_ip, uploaded_at, user_id) VALUES (:filename, :stored_name, :size, :type, :uploader_ip, NOW(), :user_id)');
    $stmt->execute([
        ':filename' => $data['filename'],
        ':stored_name' => $data['stored_name'],
        ':size' => $data['size'],
        ':type' => $data['type'],
        ':uploader_ip' => $data['uploader_ip'],
        ':user_id' => $data['user_id'],
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

function format_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    return number_format($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
}

function ensureDefaultPackages(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM packages')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare('INSERT INTO packages (name, storage_limit, max_concurrent_uploads, features, price) VALUES
            (:name1, :storage1, :upload1, :features1, :price1),
            (:name2, :storage2, :upload2, :features2, :price2),
            (:name3, :storage3, :upload3, :features3, :price3)');
        $stmt->execute([
            ':name1' => 'Başlangıç',
            ':storage1' => 524288000,
            ':upload1' => 2,
            ':features1' => json_encode(['Temel depolama', 'Sınırlı destek']),
            ':price1' => 0.00,
            ':name2' => 'Profesyonel',
            ':storage2' => 2147483648,
            ':upload2' => 5,
            ':features2' => json_encode(['Gelişmiş depolama', 'Öncelikli destek', 'Analitik raporlar']),
            ':price2' => 14.99,
            ':name3' => 'Kurumsal',
            ':storage3' => 5368709120,
            ':upload3' => 10,
            ':features3' => json_encode(['Sınırsız paylaşım', 'Takım yönetimi', 'Özel SLA']),
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
        mail_host VARCHAR(255) DEFAULT NULL,
        mail_port INT DEFAULT NULL,
        mail_username VARCHAR(255) DEFAULT NULL,
        mail_password VARCHAR(255) DEFAULT NULL,
        mail_encryption VARCHAR(10) DEFAULT NULL,
        analytics_code TEXT DEFAULT NULL,
        analytics_enabled TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $count = (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare('INSERT INTO settings (meta_title, meta_description, header_html, footer_html, analytics_enabled) VALUES (:title, :description, :header, :footer, :enabled)');
        $stmt->execute([
            ':title' => 'NoaSoft Dosya Deposu',
            ':description' => 'Güvenli ve hızlı dosya yükleme platformu.',
            ':header' => "<div class='topbar'>Hoş geldiniz!</div>",
            ':footer' => '<p>© ' . date('Y') . ' NoaSoft</p>',
            ':enabled' => 0,
        ]);
    }
}


function fetch_settings(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM settings LIMIT 1');
    $settings = $stmt->fetch();
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

function validate_uploaded_file(array $file): array
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
    $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'application/pdf',
        'text/plain', 'application/zip', 'application/x-rar-compressed',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
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
    $count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, email_verified) VALUES (:name, :email, :password_hash, 'admin', 1)");
        $stmt->execute([
            ':name' => 'Sistem Yöneticisi',
            ':email' => 'admin@fileupload.noasoft.org',
            ':password_hash' => password_hash('ChangeMe123!', PASSWORD_DEFAULT),
        ]);
    }
}

