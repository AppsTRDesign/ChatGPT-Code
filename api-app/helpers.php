<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_DSN', getenv('MAPS_API_DSN') ?: 'mysql:host=localhost;dbname=maps;charset=utf8mb4');
define('DB_USER', getenv('MAPS_API_DB_USER') ?: 'maps_user');
define('DB_PASS', getenv('MAPS_API_DB_PASS') ?: 'change-me');

define('MAIN_MAX_WIDTH', getenv('MAPS_REVIEW_MAIN_MAX_WIDTH') ?: 1400);
define('THUMB_MAX_WIDTH', getenv('MAPS_REVIEW_THUMB_MAX_WIDTH') ?: 400);
define('WEBP_QUALITY', getenv('MAPS_REVIEW_WEBP_QUALITY') ?: 82);

define('BASE_URL', getenv('MAPS_BASE_URL') ?: (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') .
    '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
));

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('SET NAMES utf8mb4');
    $pdo->exec('SET sql_mode="ANSI"');
    return $pdo;
}

function require_login_json(): void
{
    if (empty($_SESSION['user_id'])) {
        json_response(['error' => 'unauthorized'], 401);
    }
}

function current_user(): array
{
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id <= 0) {
        return [];
    }
    $db = get_pdo();
    $stmt = $db->prepare('SELECT id, name, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function ensure_dir(string $dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function save_webp_resized(string $src, string $dest, int $maxWidth, int $quality): bool
{
    $info = @getimagesize($src);
    if (!$info) {
        return false;
    }

    [$width, $height] = $info;
    $type = $info[2];

    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($src);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($src);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($src);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($src);
            break;
        default:
            return false;
    }

    if (!$image) {
        return false;
    }

    $scale = $width > $maxWidth ? ($maxWidth / $width) : 1;
    $newWidth = (int)floor($width * $scale);
    $newHeight = (int)floor($height * $scale);

    $canvas = imagecreatetruecolor($newWidth, $newHeight);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    imagecopyresampled(
        $canvas,
        $image,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $width,
        $height
    );

    $result = imagewebp($canvas, $dest, $quality);
    imagedestroy($canvas);
    imagedestroy($image);
    return $result;
}
