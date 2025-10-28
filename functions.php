<?php
declare(strict_types=1);

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
    status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transactions_packages FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
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
        mail_host VARCHAR(255) DEFAULT NULL,
        mail_port INT DEFAULT NULL,
        mail_username VARCHAR(255) DEFAULT NULL,
        mail_password VARCHAR(255) DEFAULT NULL,
        mail_encryption VARCHAR(10) DEFAULT NULL,
        analytics_code TEXT DEFAULT NULL,
        analytics_enabled TINYINT(1) DEFAULT 0,
        allowed_mime_types TEXT DEFAULT NULL,
        share_expiry_minutes INT DEFAULT 1440,
        public_sharing_enabled TINYINT(1) DEFAULT 1,
        folder_passwords_enabled TINYINT(1) DEFAULT 1,
        share_download_delay INT DEFAULT 0,
        ad_dashboard_html TEXT DEFAULT NULL,
        ad_share_top_html TEXT DEFAULT NULL,
        ad_share_bottom_html TEXT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

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
    if (!schemaColumnExists($pdo, 'settings', 'ad_dashboard_html')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN ad_dashboard_html TEXT DEFAULT NULL AFTER share_download_delay');
    }
    if (!schemaColumnExists($pdo, 'settings', 'ad_share_top_html')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN ad_share_top_html TEXT DEFAULT NULL AFTER ad_dashboard_html');
    }
    if (!schemaColumnExists($pdo, 'settings', 'ad_share_bottom_html')) {
        $pdo->exec('ALTER TABLE settings ADD COLUMN ad_share_bottom_html TEXT DEFAULT NULL AFTER ad_share_top_html');
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
    if ($count === 0) {
        $defaultMime = json_encode([
            'image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain',
            'application/zip', 'application/x-rar-compressed',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
        $stmt = $pdo->prepare('INSERT INTO settings (meta_title, meta_description, header_html, footer_html, analytics_enabled, allowed_mime_types, share_expiry_minutes, public_sharing_enabled, folder_passwords_enabled, share_download_delay)
            VALUES (:title, :description, :header, :footer, :enabled, :mime, :expiry, :public_share, :folder_password, :delay)');
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

