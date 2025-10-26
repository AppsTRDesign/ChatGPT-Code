<?php
$config = require __DIR__ . '/config.php';

date_default_timezone_set('UTC');

session_start();

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    global $config;
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['db']['host'],
        $config['db']['port'],
        $config['db']['name'],
        $config['db']['charset']
    );

    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function base_url(string $path = ''): string
{
    global $config;
    $base = rtrim($config['base_url'], '/');
    return $base . '/' . ltrim($path, '/');
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . base_url('login'));
        exit;
    }
}

function login(string $username): void
{
    $_SESSION['user'] = $username;
}

function logout(): void
{
    unset($_SESSION['user'], $_SESSION['csrf_token']);
    session_regenerate_id(true);
}

function verify_credentials(string $username, string $password): bool
{
    global $config;
    return hash_equals($config['auth']['username'], $username)
        && password_verify($password, $config['auth']['password_hash']);
}

function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function sanitize_filename(string $filename): string
{
    $filename = preg_replace('/[^A-Za-z0-9._-]/u', '-', $filename);
    return trim($filename, '-');
}

function create_slug(string $filename): string
{
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $name));
    $slug = trim($slug, '-');
    return $slug ?: 'dosya';
}

function store_file(array $file, string $uploaderIp): ?int
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Dosya yükleme hatası: ' . $file['error']);
    }

    global $config;

    if ($file['size'] > $config['security']['max_file_size']) {
        throw new RuntimeException('Dosya boyutu sınırı aşıldı.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $config['security']['allowed_mime_types'], true)) {
        throw new RuntimeException('İzin verilmeyen dosya türü.');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $storedName = bin2hex(random_bytes(16));
    if ($extension) {
        $storedName .= '.' . $extension;
    }

    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Upload dizini oluşturulamadı.');
    }

    $destination = $uploadDir . '/' . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Dosya kaydedilemedi.');
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare('INSERT INTO files (filename, stored_name, size, type, uploader_ip, uploaded_at) VALUES (:filename, :stored_name, :size, :type, :uploader_ip, NOW())');
    $stmt->execute([
        ':filename' => $file['name'],
        ':stored_name' => $storedName,
        ':size' => $file['size'],
        ':type' => $mimeType,
        ':uploader_ip' => $uploaderIp,
    ]);

    return (int) $pdo->lastInsertId();
}

function get_file(int $id): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM files WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $file = $stmt->fetch();
    return $file ?: null;
}

function get_all_files(): array
{
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT * FROM files ORDER BY uploaded_at DESC');
    return $stmt->fetchAll();
}

function update_file_name(int $id, string $filename): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE files SET filename = :filename WHERE id = :id');
    $stmt->execute([
        ':filename' => $filename,
        ':id' => $id,
    ]);
}

function delete_file(int $id): void
{
    $pdo = get_pdo();
    $file = get_file($id);
    if (!$file) {
        return;
    }

    $path = __DIR__ . '/uploads/' . $file['stored_name'];
    if (is_file($path)) {
        unlink($path);
    }

    $stmt = $pdo->prepare('DELETE FROM files WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function get_storage_stats(): array
{
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT COUNT(*) AS total_files, COALESCE(SUM(size), 0) AS total_size FROM files');
    return $stmt->fetch();
}

function format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }

    $units = ['KB', 'MB', 'GB', 'TB'];
    $bytes = $bytes / 1024;
    foreach ($units as $unit) {
        if ($bytes < 1024) {
            return sprintf('%.2f %s', $bytes, $unit);
        }
        $bytes /= 1024;
    }

    return sprintf('%.2f PB', $bytes);
}

function safe_output(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function get_request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    return '/' . ltrim($uri ?? '/', '/');
}
