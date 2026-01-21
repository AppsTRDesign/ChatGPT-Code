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

define('DEFAULT_MAP_LIMIT', 200);
define('HOME_SEARCH_LIMIT', 9);
if (!defined('BASE_TITLE')) {
    define('BASE_TITLE', getenv('MAPS_BASE_TITLE') ?: 'GuideXY');
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

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function require_login_json(): void
{
    if (empty($_SESSION['user_id'])) {
        json_response(['error' => 'unauthorized'], 401);
    }
}

function current_user(): ?array
{
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }
    return get_user_by_id($id);
}

function get_user_by_id(int $userId): ?array
{
    $db = get_pdo();
    $stmt = $db->prepare('SELECT id, name, email, role, avatar_url, profile_photo FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function require_user_payload(array $data, bool $requireRole = false): array
{
    $userId = (int)($data['user_id'] ?? 0);
    $userName = trim((string)($data['user_name'] ?? $data['name'] ?? ''));
    $userEmail = trim((string)($data['user_email'] ?? $data['email'] ?? ''));
    $userRole = trim((string)($data['user_role'] ?? $data['role'] ?? ''));

    if ($userId <= 0) {
        json_response(['error' => 'missing_user'], 422);
    }

    if ($requireRole && $userRole === '') {
        json_response(['error' => 'missing_role'], 422);
    }

    return [
        'id' => $userId,
        'name' => $userName,
        'email' => $userEmail,
        'role' => $userRole,
    ];
}

function can_edit_place(array $place, ?array $user): bool
{
    if (!$user) {
        return false;
    }

    if ($user['role'] === 'admin') {
        return true;
    }

    if (
        $user['role'] === 'business_owner' &&
        (int)$place['claimed_by'] === (int)$user['id']
    ) {
        return true;
    }

    return false;
}

function decode_json($value): array
{
    if (!$value) {
        return [];
    }
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
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

function render_lazy_img(string $src, int $width = 48, int $height = 48, array $attrs = []): string
{
    $safeSrc = htmlspecialchars($src ?: (BASE_URL . '/assets/img/default-user.webp'), ENT_QUOTES, 'UTF-8');
    $attrString = '';
    foreach ($attrs as $key => $value) {
        $attrString .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' .
            htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
    }
    return '<img src="' . $safeSrc . '" width="' . $width . '" height="' . $height . '"' . $attrString . ' loading="lazy" />';
}

function slugify(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $find = ["/Ğ/","/Ü/","/Ş/","/İ/","/Ö/","/Ç/","/ğ/","/ü/","/ş/","/ı/","/ö/","/ç/"];
    $replace = ["G","U","S","I","O","C","g","u","s","i","o","c"];
    $text = preg_replace("/[^0-9a-zA-ZĞÜŞİÖÇğüşıöç]/"," ",$text);
    $text = preg_replace($find,$replace,$text);
    $text = preg_replace("/ +/"," ",$text);
    $text = preg_replace("/ /","-",$text);
    $text = preg_replace("/\s/","",$text);
    $text = strtolower($text);
    $text = preg_replace("/^-/","",$text);
    $text = preg_replace("/-$/","",$text);
    return $text;
}

function build_place_slug(array $place): string
{
    $slug = slugify($place['name'] ?? 'isletme');
    $id = $place['id'] ?? 0;
    return "isletme/{$id}-{$slug}";
}

function getPlaceCurrentStatusList(int $placeId): array
{
    return [
        'status' => 'unknown',
        'text' => 'Bilinmiyor',
    ];
}

function getPlaceCurrentStatus(int $placeId): array
{
    date_default_timezone_set("Europe/Istanbul");

    $db = get_pdo();
    $currentDay = (int)date('N'); // 1 = Pazartesi
    $currentTime = date('H:i');

    $stmt = $db->prepare("
        SELECT day, open_time, close_time, is_24h, is_closed
        FROM place_hours
        WHERE place_id = :pid
        ORDER BY day ASC
    ");
    $stmt->execute([':pid' => $placeId]);
    $hours = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$hours) {
        return [
            'status' => 'unknown',
            'text' => 'Çalışma saatleri bulunamadı',
        ];
    }

    $today = $hours[$currentDay - 1] ?? null;
    if (!$today) {
        return [
            'status' => 'unknown',
            'text' => 'Çalışma saatleri bulunamadı',
        ];
    }

    if (!empty($today['is_closed'])) {
        return [
            'status' => 'closed',
            'text' => 'Bugün kapalı',
        ];
    }

    if (!empty($today['is_24h'])) {
        return [
            'status' => 'open',
            'text' => 'Şu an açık • 24 saat hizmet veriyor',
        ];
    }

    $open = $today['open_time'];
    $close = $today['close_time'];

    if (!$open || !$close) {
        return [
            'status' => 'unknown',
            'text' => 'Çalışma saatleri bulunamadı',
        ];
    }

    $crossDay = ($close < $open);
    if ($crossDay) {
        if ($currentTime >= $open || $currentTime <= $close) {
            return [
                'status' => 'open',
                'text' => "Şu an açık • {$close}’da kapanıyor",
            ];
        }
        return [
            'status' => 'closed',
            'text' => "{$open}’de açılacak",
        ];
    }

    if ($currentTime >= $open && $currentTime <= $close) {
        return [
            'status' => 'open',
            'text' => "Şu an açık • {$close}’da kapanıyor",
        ];
    }

    if ($currentTime < $open) {
        return [
            'status' => 'closed',
            'text' => "{$open}’de açılacak",
        ];
    }

    return [
        'status' => 'closed',
        'text' => 'Kapalı',
    ];
}

function generateBusinessDesc(
    $name,
    $category,
    $city,
    $town = null,
    $country = 'Türkiye',
    $rating = null,
    $reviewCount = null
) {
    $name     = trim((string)$name);
    $category = ucfirst(trim((string)$category));
    $city     = ucfirst(trim((string)$city));
    $town     = $town ? ucfirst(trim((string)$town)) : null;
    $country  = ucfirst(trim((string)$country));

    $ratingVal = $rating ? number_format((float)$rating, 1) : null;
    $reviewTxt = $reviewCount ? "$reviewCount yorum" : null;

    if ($ratingVal >= 4.7) {
        $ratingTone = "yüksek kullanıcı memnuniyeti ile öne çıkan";
    } elseif ($ratingVal >= 4.2) {
        $ratingTone = "kullanıcılar tarafından sıkça tercih edilen";
    } elseif ($ratingVal >= 3.7) {
        $ratingTone = "kullanıcı değerlendirmeleri bulunan";
    } else {
        $ratingTone = "kullanıcı yorumları ile listelenen";
    }

    $categoryTone = match (mb_strtolower($category)) {
        'market', 'bakkal', 'avm' =>
            "yakın çevrede günlük ihtiyaçlara hızlı erişim sağlayan",
        'restoran', 'kafe', 'kahvaltı restoranı' =>
            "yeme içme deneyimi arayan kullanıcılar için tercih edilen",
        'kuaför', 'berber', 'hizmet' =>
            "hizmet kalitesi ve erişilebilirliği ile öne çıkan",
        'eczane', 'klinik', 'hastane' =>
            "güven ve hassasiyet gerektiren alanlarda hizmet sunan",
        'otel', 'konaklama', 'apart' =>
            "konaklama tercihleri arasında değerlendirilen",
        default =>
            "yakın çevrede hizmet arayan kullanıcılar için listelenen"
    };

    $desc  = "<p>";
    $desc .= "<strong>$name</strong>, ";

    if ($town) {
        $desc .= "<strong>$town</strong> ilçesinde, ";
    }

    $desc .= "<strong>$city</strong> şehrinde ve ";
    $desc .= "<strong>$country</strong> genelinde ";
    $desc .= "$categoryTone bir <strong>$category</strong> işletmesidir.";

    if ($ratingVal && $reviewTxt) {
        $desc .= "<br><br>";
        $desc .= "Kullanıcı değerlendirmelerine göre ";
        $desc .= "<strong>$ratingVal</strong> ";
        $desc .= "<i class='fa-solid fa-star text-warning'></i> ";
        $desc .= "ortalama puan ve ";
        $desc .= "<strong>$reviewTxt</strong> ";
        $desc .= "<i class='fa-solid fa-comment text-success'></i> ";
        $desc .= "ile $ratingTone işletmeler arasında yer almaktadır.";
    }

    $desc .= "</p>";

    $desc .= "<p>";
    $desc .= "<strong>$city</strong>";

    if ($town) {
        $desc .= " ve <strong>$town</strong>";
    }

    $desc .= " bölgesinde <strong>$category</strong> arayışında olan kullanıcılar için ";
    $desc .= "<strong>$name</strong>, ";
    $desc .= "gerçek kullanıcı yorumları, güncel puanlamalar ve doğrulanmış bilgiler ";
    $desc .= "ile <strong>güvenilir bir referans noktası</strong> olarak değerlendirilmektedir.";
    $desc .= "</p>";

    $desc .= "<p>";
    $desc .= "<strong>$name</strong> hakkında yer alan tüm bilgiler; ";
    $desc .= "işletme detayları, kullanıcı deneyimleri ve değerlendirmeler dahil olmak üzere ";
    $desc .= "<strong>" . BASE_TITLE . " doğrulanmış işletme verileri sistemi</strong> ";
    $desc .= "kapsamında düzenli olarak güncellenmektedir.";
    $desc .= "</p>";

    $desc .= "<ul>";
    $desc .= "<li><strong>Konum:</strong> $city" . ($town ? " / $town" : "") . "</li>";
    $desc .= "<li><strong>Kategori:</strong> $category</li>";
    if ($ratingVal) {
        $desc .= "<li><strong>Ortalama Puan:</strong> $ratingVal</li>";
    }
    if ($reviewTxt) {
        $desc .= "<li><strong>Yorum Sayısı:</strong> $reviewTxt</li>";
    }
    $desc .= "</ul>";

    $desc .= "<p><small>";
    if ($town) {
        $desc .= "<strong>$town</strong> ve <strong>$city</strong> bölgesindeki ";
    } else {
        $desc .= "<strong>$city</strong> bölgesindeki ";
    }
    $desc .= "<strong>$category</strong> işletmeleri arasında ";
    $desc .= "<strong>$name</strong>, ";
    $desc .= "yerel arama sonuçlarında görünürlüğü yüksek ";
    $desc .= "işletmelerden biri olarak listelenmektedir.";
    $desc .= "</small></p>";

    return $desc;
}
