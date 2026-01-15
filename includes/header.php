<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

function render_header(string $title = '', array $meta = []): void
{
    $siteTitle = settings('site_name', 'Çiçek');
    $metaTitle = settings('meta_title', $siteTitle);
    $themeColor = settings('theme_color', '#E85D75');
    $pageTitle = $meta['title'] ?? ($title ? $title . ' | ' . $siteTitle : $metaTitle);
    $metaDescription = $meta['description'] ?? settings('meta_description', '');
    $logo = settings('logo');
    $favicon = settings('favicon');
    $metaImage = $meta['image'] ?? '';

    echo "<!DOCTYPE html>\n";
    echo "<html lang=\"tr\">\n<head>\n";
    echo "<meta charset=\"UTF-8\">\n";
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
    echo "<meta name=\"theme-color\" content=\"{$themeColor}\">\n";
    echo "<style>:root{--theme-color: {$themeColor};}</style>\n";
    echo "<title>{$pageTitle}</title>\n";
    echo "<meta name=\"description\" content=\"{$metaDescription}\">\n";
    echo "<meta name=\"csrf-token\" content=\"" . csrf_token() . "\">\n";
    echo "<meta property=\"og:title\" content=\"{$pageTitle}\">\n";
    echo "<meta property=\"og:description\" content=\"{$metaDescription}\">\n";
    echo "<meta property=\"og:type\" content=\"website\">\n";
    echo "<meta property=\"twitter:title\" content=\"{$pageTitle}\">\n";
    echo "<meta property=\"twitter:description\" content=\"{$metaDescription}\">\n";
    echo "<meta property=\"twitter:card\" content=\"summary_large_image\">\n";
    if ($metaImage) {
        $safeImage = htmlspecialchars($metaImage);
        echo "<meta property=\"og:image\" content=\"{$safeImage}\">\n";
        echo "<meta property=\"twitter:image\" content=\"{$safeImage}\">\n";
    }
    if ($favicon) {
        echo "<link rel=\"icon\" href=\"{$favicon}\">\n";
    }
    echo "<link rel=\"stylesheet\" href=\"/assets/css/style.css\">\n";
    echo "<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css\">\n";
    echo "<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css\">\n";
    if (settings('lightbox_provider') === 'lightbox2') {
        echo "<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css\">\n";
    } else {
        echo "<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css\">\n";
    }
    echo "<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css\">\n";
    echo "</head>\n<body>\n";
    echo "<header class=\"site-header\">\n<div class=\"container\">\n";
    if ($logo) {
        $logoTag = "<img src=\"{$logo}\" alt=\"{$siteTitle}\">";
        echo "<div class=\"logo\"><a href=\"/\">{$logoTag}</a></div>\n";
    } else {
        echo "<div class=\"logo\"><a href=\"/\">{$siteTitle}</a></div>\n";
    }
    $categories = db()->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $categoryChildren = [];
    foreach ($categories as $category) {
        $parentId = $category['parent_id'] ? (int) $category['parent_id'] : 0;
        $categoryChildren[$parentId][] = $category;
    }
    $user = current_user();
    echo "<nav class=\"main-nav\" id=\"mainNav\">\n";
    echo "<a href=\"/\">Ana Sayfa</a>\n";
    if (!empty($categoryChildren[0])) {
        echo "<div class=\"nav-dropdown\">\n";
        echo "<span>Kategoriler</span>\n";
        echo "<div class=\"dropdown-menu\">\n";
        echo "<a href=\"/kategoriler\">Tüm Kategoriler</a>\n";
        foreach ($categoryChildren[0] as $category) {
            echo "<a href=\"" . category_url($category) . "\">" . htmlspecialchars($category['name']) . "</a>\n";
            foreach ($categoryChildren[(int) $category['id']] ?? [] as $child) {
                echo "<a class=\"nav-child\" href=\"" . category_url($child) . "\">" . htmlspecialchars($category['name'] . ' › ' . $child['name']) . "</a>\n";
            }
        }
        echo "</div>\n</div>\n";
    }
    echo "<a href=\"/icerikler\">İçerikler</a>\n";
    echo "<a href=\"/sepet\">Sepet</a>\n";
    echo "<a href=\"/sss\">SSS</a>\n";
    echo "<a href=\"/iletisim\">İletişim</a>\n";
    if ($user) {
        echo "<a href=\"/profil\">Profil</a>\n";
        echo "<a href=\"/cikis\">Çıkış</a>\n";
    } else {
        echo "<a href=\"/giris\">Giriş Yap</a>\n";
        echo "<a href=\"/kayit\">Kayıt Ol</a>\n";
    }
    echo "</nav>\n";
    echo "<button class=\"nav-toggle\" id=\"navToggle\" aria-label=\"Menüyü Aç\">☰</button>\n";
    echo "</div>\n</header>\n";
}
