<?php
function permalink($text){
    $find = array("/Ğ/","/Ü/","/Ş/","/İ/","/Ö/","/Ç/","/ğ/","/ü/","/ş/","/ı/","/ö/","/ç/");
    $degis = array("G","U","S","I","O","C","g","u","s","i","o","c");
    $text = preg_replace("/[^0-9a-zA-ZÄzÜŞİÖÇğüşıöç]/"," ",$text);
    $text = preg_replace($find,$degis,$text);
    $text = preg_replace("/ +/"," ",$text);
    $text = preg_replace("/ /","-",$text);
    $text = preg_replace("/\s/","",$text);
    $text = strtolower($text);
    $text = preg_replace("/^-/","",$text);
    $text = preg_replace("/-$/","",$text);
    return $text;
}

function slugify(string $text): string
{
    return permalink($text) ?: 'isletme';
}

function build_place_slug(array $place): string
{
    $slug = slugify($place['name'] ?? 'isletme');
    $id = $place['id'] ?? 0;
    return "isletme/{$id}-{$slug}";
}

function category_url(?string $slug): string
{
    return $slug ? '/kategoriler/' . urlencode($slug) : '/kategoriler';
}

function gravatar_url(?string $email, int $size = 64): string
{
    if (!$email) {
        return "https://www.gravatar.com/avatar/?d=mp&s={$size}";
    }
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s={$size}";
}

function format_rating($value): string
{
    return $value !== null ? number_format((float) $value, 1) : '-';
}

function paginate(int $page, int $limit): array
{
    $page = max(1, $page);
    $offset = ($page - 1) * $limit;
    return [$offset, $limit];
}

function render_meta(string $title, string $description = ''): string
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeDesc = htmlspecialchars($description ?: $title, ENT_QUOTES, 'UTF-8');
    return "<title>{$safeTitle}</title>\n" .
        "<meta name=\"description\" content=\"{$safeDesc}\">\n" .
        "<meta name=\"keywords\" content=\"{$safeTitle}\">\n" .
        "<meta property=\"og:title\" content=\"{$safeTitle}\">\n" .
        "<meta property=\"og:description\" content=\"{$safeDesc}\">\n" .
        "<meta property=\"og:type\" content=\"website\">\n" .
        "<meta name=\"twitter:card\" content=\"summary_large_image\">\n" .
        "<meta name=\"twitter:title\" content=\"{$safeTitle}\">\n" .
        "<meta name=\"twitter:description\" content=\"{$safeDesc}\">\n";
}

function decode_json($value)
{
    if (!$value) {
        return [];
    }
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

