<?php
require_once __DIR__ . '/config.php';

function permalink($text){
    $find = array("/Ğ/","/Ü/","/Ş/","/İ/","/Ö/","/Ç/","/ğ/","/ü/","/ş/","/ı/","/ö/","/ç/");
    $degis = array("G","U","S","I","O","C","g","u","s","i","o","c");
    $text = preg_replace("/[^0-9a-zA-ZĞÜŞİÖÇğüşıöç]/"," ",$text);
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

function city_slug(string $city): string
{
    return slugify($city);
}

function city_url(string $city): string
{
    return '/sehir/' . urlencode(city_slug($city));
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

function render_pagination_links(int $page, int $totalPages, string $baseUrl, array $query = []): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $queryString = function($p) use ($query) {
        $params = $query;
        $params['s'] = $p;
        return http_build_query($params);
    };
    $html = '<nav aria-label="Sayfalama" class="my-3"><ul class="pagination flex-wrap">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $page ? ' active' : '';
        $html .= "<li class='page-item{$active}'><a class='page-link' href='{$baseUrl}?" . $queryString($i) . "'>{$i}</a></li>";
    }
    $html .= '</ul></nav>';
    return $html;
}

function render_meta($titleOrMeta, string $description = '', ?string $image = null, ?string $url = null, ?string $keywords = null): string
{
    if (is_array($titleOrMeta)) {
        $meta = $titleOrMeta;
    } else {
        $meta = [
            'title' => $titleOrMeta,
            'description' => $description ?: $titleOrMeta,
            'image' => $image,
            'url' => $url,
            'keywords' => $keywords,
        ];
    }

    $safeTitle = htmlspecialchars($meta['title'] ?? '', ENT_QUOTES, 'UTF-8');
    $safeDesc = htmlspecialchars($meta['description'] ?? ($meta['title'] ?? ''), ENT_QUOTES, 'UTF-8');
    $safeKeywords = htmlspecialchars($meta['keywords'] ?? $safeTitle, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($meta['url'] ?? BASE_URL, ENT_QUOTES, 'UTF-8');
    $imagePath = $meta['image'] ?? (BASE_URL . '/assets/img/default.jpg');
    $safeImage = htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8');

    return "<title>{$safeTitle}</title>\n" .
        "<meta name=\"description\" content=\"{$safeDesc}\">\n" .
        "<meta name=\"keywords\" content=\"{$safeKeywords}\">\n" .
        "<meta property=\"og:type\" content=\"website\">\n" .
        "<meta property=\"og:title\" content=\"{$safeTitle}\">\n" .
        "<meta property=\"og:description\" content=\"{$safeDesc}\">\n" .
        "<meta property=\"og:url\" content=\"{$safeUrl}\">\n" .
        "<meta property=\"og:image\" content=\"{$safeImage}\">\n" .
        "<meta property=\"og:site_name\" content=\"NoaSoft Maps\">\n" .
        "<meta property=\"og:locale\" content=\"tr_TR\">\n" .
        "<meta name=\"twitter:card\" content=\"summary_large_image\">\n" .
        "<meta name=\"twitter:title\" content=\"{$safeTitle}\">\n" .
        "<meta name=\"twitter:description\" content=\"{$safeDesc}\">\n" .
        "<meta name=\"twitter:image\" content=\"{$safeImage}\">\n" .
        "<meta name=\"twitter:url\" content=\"{$safeUrl}\">\n" .
        "<meta name=\"twitter:site\" content=\"@noasoft\">\n";
}

function decode_json($value)
{
    if (!$value) {
        return [];
    }
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}
