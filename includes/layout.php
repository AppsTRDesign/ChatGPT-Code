<?php
require_once __DIR__ . '/helpers.php';

function render_header(string $title = ''): void
{
    $siteTitle = settings('site_name', 'Çiçek');
    $metaTitle = settings('meta_title', $siteTitle);
    $metaDescription = settings('meta_description', '');
    $themeColor = settings('theme_color', '#E85D75');
    $pageTitle = $title ? $title . ' | ' . $siteTitle : $metaTitle;
    echo "<!DOCTYPE html>\n";
    echo "<html lang=\"tr\">\n<head>\n";
    echo "<meta charset=\"UTF-8\">\n";
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
    echo "<meta name=\"theme-color\" content=\"{$themeColor}\">\n";
    echo "<style>:root{--theme-color: {$themeColor};}</style>\n";
    echo "<title>{$pageTitle}</title>\n";
    echo "<meta name=\"description\" content=\"{$metaDescription}\">\n";
    echo "<meta property=\"og:title\" content=\"{$pageTitle}\">\n";
    echo "<meta property=\"og:description\" content=\"{$metaDescription}\">\n";
    echo "<meta property=\"og:type\" content=\"website\">\n";
    echo "<meta property=\"twitter:card\" content=\"summary_large_image\">\n";
    $logo = settings('logo');
    $favicon = settings('favicon');
    if ($favicon) {
        echo "<link rel=\"icon\" href=\"{$favicon}\">\n";
    }
    echo "<link rel=\"stylesheet\" href=\"/assets/css/style.css\">\n";
    echo "<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css\">\n";
    echo "</head>\n<body>\n";
    echo "<header class=\"site-header\">\n<div class=\"container\">\n";
    echo "<div class=\"logo\"><a href=\"/\">\" . ($logo ? \"<img src=\\\"{$logo}\\\" alt=\\\"{$siteTitle}\\\">\" : $siteTitle) . \"</a></div>\n";
    echo "<nav class=\"main-nav\" id=\"mainNav\">\n";
    echo "<a href=\"/\">Ana Sayfa</a>\n";
    echo "<a href=\"/content.php\">İçerikler</a>\n";
    echo "<a href=\"/faq.php\">SSS</a>\n";
    echo "<a href=\"/contact.php\">İletişim</a>\n";
    echo "<a href=\"/account.php\">Üyelik</a>\n";
    echo "</nav>\n";
    echo "<button class=\"nav-toggle\" id=\"navToggle\" aria-label=\"Menüyü Aç\">☰</button>\n";
    echo "</div>\n</header>\n";
}

function render_footer(): void
{
    $siteName = settings('site_name', 'Çiçek');
    $phone = settings('contact_phone');
    $email = settings('contact_email');
    $address = settings('site_address');
    echo "<footer class=\"site-footer\">\n<div class=\"container\">\n";
    echo "<div><strong>{$siteName}</strong><p>{$address}</p></div>\n";
    echo "<div><p>Telefon: {$phone}</p><p>E-posta: {$email}</p></div>\n";
    echo "<div><p>© " . date('Y') . " {$siteName}.</p></div>\n";
    echo "</div>\n</footer>\n";
    echo "<script src=\"https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js\"></script>\n";
    echo "<script src=\"/assets/js/app.js\"></script>\n";
    echo "</body>\n</html>";
}
