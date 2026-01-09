<?php

define('DB_DSN', getenv('MAPS_API_DSN') ?: 'mysql:host=localhost;dbname=maps;charset=utf8mb4');
define('DB_USER', getenv('MAPS_API_DB_USER') ?: 'maps_user');
define('DB_PASS', getenv('MAPS_API_DB_PASS') ?: 'change-me');

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

function slugify(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $find = ["/Ğ/","/Ü/","/Ş/","/İ/","/Ö/","/Ç/","/ğ/","/ü/","/ş/","/ı/","/ö/","/ç/"];
    $replace = ["G","U","S","I","O","C","g","u","s","i","o","c"];
    $text = preg_replace("/[^0-9a-zA-ZÄzÜŞİÖÇğüşıöç]/"," ",$text);
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
        $desc .= " Kullanıcı değerlendirmelerine göre ";
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
