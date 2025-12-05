<?php
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9ğüşöçıİçŞĞÜÖ\-\s]/u', '', $text);
    $text = preg_replace('/[\s_]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'isletme';
}

function build_place_slug(array $place): string
{
    $slug = slugify($place['name'] ?? 'isletme');
    $id = $place['id'] ?? 0;
    return "isletme/{$id}-{$slug}";
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

