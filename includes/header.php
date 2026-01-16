<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

function render_header(string $title = '', array $meta = []): void
{
    $siteTitle = settings('site_name', 'Çiçek');
    $metaTitle = settings('meta_title', $siteTitle);
    $themeColor = settings('theme_color', '#E85D75');
    $textColor = settings('text_color', '#2c2c2c');
    $lightBg = settings('light_bg', '#fff7f9');
    $borderColor = settings('border_color', '#f3d1d8');
    $shadow = settings('shadow', '0 16px 32px rgba(0, 0, 0, 0.08)');
    $dropdownMenuBg = settings('dropdown_menu_bg', '#ffffff');
    $siteHeaderBg = settings('site_header_bg', '#ffffff');
    $siteHeaderTextColor = settings('site_header_text_color', '#2c2c2c');
    $siteFooterBg = settings('site_footer_bg', '#fdf2f4');
    $siteFooterTextColor = settings('site_footer_text_color', '#5b5b5b');
    $cartCountBg = settings('cart_count_bg', $themeColor);
    $framedSectionBorder = settings('framed_section_border', '1px solid var(--border-color)');
    $framedSectionPadding = settings('framed_section_padding', '32px');
    $framedSectionRadius = settings('framed_section_radius', '24px');
    $fontFamilyKey = settings('font_family', 'segoe-ui');
    $fontOptions = [
        'segoe-ui' => [
            'family' => "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
            'link' => '',
        ],
        'inter' => [
            'family' => "'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
            'link' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        ],
        'noto-sans' => [
            'family' => "'Noto Sans', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
            'link' => 'https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&display=swap',
        ],
        'open-sans' => [
            'family' => "'Open Sans', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
            'link' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap',
        ],
        'roboto' => [
            'family' => "'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
            'link' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap',
        ],
        'montserrat' => [
            'family' => "'Montserrat', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
            'link' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap',
        ],
        'poppins' => [
            'family' => "'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
            'link' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
        ],
    ];
    $fontData = $fontOptions[$fontFamilyKey] ?? $fontOptions['segoe-ui'];
    $fontFamily = $fontData['family'];
    $mobileMenuToggleColor = settings('mobile_menu_toggle_color', $themeColor);
    $mobileMenuTextColor = settings('mobile_menu_text_color', '#ffffff');
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
    echo "<style>:root{--theme-color: {$themeColor};--text-color: {$textColor};--light-bg: {$lightBg};--border-color: {$borderColor};--shadow: {$shadow};--font-family: {$fontFamily};--dropdown-menu-bg: {$dropdownMenuBg};--site-header-bg: {$siteHeaderBg};--site-header-text-color: {$siteHeaderTextColor};--site-footer-bg: {$siteFooterBg};--site-footer-text-color: {$siteFooterTextColor};--cart-count-bg: {$cartCountBg};--framed-section-border: {$framedSectionBorder};--framed-section-padding: {$framedSectionPadding};--framed-section-radius: {$framedSectionRadius};--mobile-menu-toggle-color: {$mobileMenuToggleColor};--mobile-menu-text-color: {$mobileMenuTextColor};}</style>\n";
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
    if ($fontData['link']) {
        $fontLink = htmlspecialchars($fontData['link']);
        echo "<link rel=\"stylesheet\" href=\"{$fontLink}\">\n";
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
    $siteWidth = settings('site_width', 'box');
    $bodyClass = $siteWidth === 'wide' ? 'site-width-wide' : 'site-width-box';
    echo "</head>\n<body class=\"{$bodyClass}\">\n";
    echo "<header class=\"site-header\">\n<div class=\"container\">\n";
    if ($logo) {
        $logoTag = "<img src=\"{$logo}\" alt=\"{$siteTitle}\" title=\"{$siteTitle}\">";
        echo "<div class=\"logo\"><a href=\"/\" title=\"{$siteTitle}\">{$logoTag}</a></div>\n";
    } else {
        echo "<div class=\"logo\"><a href=\"/\" title=\"{$siteTitle}\">{$siteTitle}</a></div>\n";
    }
    $categories = db()->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $categoryChildren = [];
    foreach ($categories as $category) {
        $parentId = $category['parent_id'] ? (int) $category['parent_id'] : 0;
        $categoryChildren[$parentId][] = $category;
    }
    foreach ($categoryChildren as &$children) {
        usort($children, static fn($a, $b) => strcmp($a['name'], $b['name']));
    }
    unset($children);
    $user = current_user();
    $cartCount = array_sum($_SESSION['cart'] ?? []);
    $userAvatar = $user['avatar'] ?? '';
    $avatarSrc = $userAvatar ?: ($favicon ?: '/assets/images/placeholder.svg');
    echo "<nav class=\"main-nav\" id=\"mainNav\">\n";
    echo "<a href=\"/\" title=\"Ana Sayfa\">Ana Sayfa</a>\n";
    if (!empty($categoryChildren[0])) {
        echo "<div class=\"nav-dropdown\">\n";
        echo "<span>Kategoriler</span>\n";
        echo "<div class=\"dropdown-menu\">\n";
        echo "<a href=\"/kategoriler\" title=\"Tüm Kategoriler\">Tüm Kategoriler</a>\n";
        foreach ($categoryChildren[0] as $category) {
            $categoryName = htmlspecialchars($category['name']);
            echo "<a href=\"" . category_url($category) . "\" title=\"{$categoryName}\">{$categoryName}</a>\n";
            foreach ($categoryChildren[(int) $category['id']] ?? [] as $child) {
                $childName = htmlspecialchars($child['name']);
                echo "<a class=\"nav-child\" href=\"" . category_url($child) . "\" title=\"{$childName}\">{$childName}</a>\n";
            }
        }
        echo "</div>\n</div>\n";
    }
    echo "<a href=\"/icerikler\" title=\"İçerikler\">İçerikler</a>\n";
    echo "<a href=\"/sepet\" title=\"Sepet\">Sepet <span class=\"cart-count\" data-cart-count>" . (int) $cartCount . "</span></a>\n";
    echo "<a href=\"/sss\" title=\"SSS\">SSS</a>\n";
    echo "<a href=\"/iletisim\" title=\"İletişim\">İletişim</a>\n";
    if ($user) {
        echo "<a href=\"/profil\" title=\"Profil\">Profil</a>\n";
        echo "<a href=\"/cikis\" title=\"Çıkış\">Çıkış</a>\n";
    } else {
        echo "<a href=\"/giris\" title=\"Giriş Yap\">Giriş Yap</a>\n";
        echo "<a href=\"/kayit\" title=\"Kayıt Ol\">Kayıt Ol</a>\n";
    }
    if ($user) {
        $userName = htmlspecialchars($user['name'] ?? '');
        $userEmail = htmlspecialchars($user['email'] ?? '');
        $avatarEscaped = htmlspecialchars($avatarSrc);
        echo "<div class=\"nav-user\">\n";
        echo "<img class=\"nav-user-avatar\" src=\"{$avatarEscaped}\" alt=\"{$userName}\">\n";
        echo "<div class=\"nav-user-info\">\n";
        echo "<strong>{$userName}</strong>\n";
        echo "<span>{$userEmail}</span>\n";
        echo "</div>\n</div>\n";
    }
    echo "</nav>\n";
    $headerHtml = settings('header_html');
    if ($headerHtml) {
        echo "<div class=\"header-html\">{$headerHtml}</div>\n";
    }
    echo "<button class=\"nav-toggle\" id=\"navToggle\" aria-label=\"Menüyü Aç\">☰</button>\n";
    echo "</div>\n</header>\n";
}
