<?php
require_once __DIR__ . '/../includes/helpers.php';

function admin_header(string $title): void
{
    $siteName = settings('site_name', 'Çiçek');
    $themeColor = settings('theme_color', '#E85D75');
    echo "<!DOCTYPE html>\n<html lang=\"tr\">\n<head>\n";
    echo "<meta charset=\"UTF-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
    echo "<title>{$title} | Admin</title>\n";
    echo "<meta name=\"theme-color\" content=\"{$themeColor}\">\n";
    echo "<style>:root{--theme-color: {$themeColor};}</style>\n";
    echo "<link rel=\"stylesheet\" href=\"/assets/css/admin.css\">\n";
    echo "<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css\">\n";
    echo "<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css\">\n";
    echo "</head>\n<body class=\"admin-body\">\n";
    echo "<aside class=\"admin-sidebar\" id=\"adminSidebar\">\n";
    echo "<h2>{$siteName}</h2>\n";
    echo "<nav>\n";
    echo "<div class=\"admin-nav-group\"><span>Genel</span>\n";
    echo "<a href=\"/admin/index.php\">Dashboard</a>\n";
    echo "<a href=\"/admin/settings.php\">Ayarlar</a>\n";
    echo "</div>\n";
    echo "<div class=\"admin-nav-group\"><span>Ürünler</span>\n";
    echo "<a href=\"/admin/products.php\">Ürün Ekle</a>\n";
    echo "<a href=\"/admin/products-list.php\">Eklenenler</a>\n";
    echo "</div>\n";
    echo "<div class=\"admin-nav-group\"><span>Kategoriler</span>\n";
    echo "<a href=\"/admin/categories.php\">Kategori Ekle</a>\n";
    echo "<a href=\"/admin/categories-list.php\">Eklenenler</a>\n";
    echo "</div>\n";
    echo "<div class=\"admin-nav-group\"><span>Slider</span>\n";
    echo "<a href=\"/admin/sliders.php\">Sliderlar</a>\n";
    echo "</div>\n";
    echo "<div class=\"admin-nav-group\"><span>Siparişler</span>\n";
    echo "<a href=\"/admin/orders.php\">Siparişler</a>\n";
    echo "</div>\n";
    echo "<div class=\"admin-nav-group\"><span>Kullanıcılar</span>\n";
    echo "<a href=\"/admin/users.php\">Kullanıcılar</a>\n";
    echo "</div>\n";
    echo "<div class=\"admin-nav-group\"><span>İçerik</span>\n";
    echo "<a href=\"/admin/pages.php\">Sayfa Ekle</a>\n";
    echo "<a href=\"/admin/pages-list.php\">Eklenen Sayfalar</a>\n";
    echo "<a href=\"/admin/faqs.php\">SSS</a>\n";
    echo "</div>\n";
    echo "<div class=\"admin-nav-group\"><span>Ödeme</span>\n";
    echo "<a href=\"/admin/paytr.php\">PayTR Ayarları</a>\n";
    echo "</div>\n";
    echo "<div class=\"admin-nav-group\"><span>Çıkış</span>\n";
    echo "<a href=\"/logout.php\">Çıkış</a>\n";
    echo "</div>\n";
    echo "</nav>\n";
    echo "</aside>\n<main class=\"admin-main\">\n<header class=\"admin-header\">\n";
    echo "<button class=\"admin-nav-toggle\" id=\"adminNavToggle\" aria-label=\"Menüyü Aç\">☰</button>\n";
    echo "<h1>{$title}</h1>\n";
    echo "<input type=\"hidden\" name=\"csrf_token\" value=\"" . csrf_token() . "\">\n";
    echo "</header>\n";
}

function admin_footer(): void
{
    echo "</main>\n";
    echo "<script src=\"https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js\"></script>\n";
    echo "<script src=\"https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js\"></script>\n";
    echo "<script src=\"https://cdn.jsdelivr.net/npm/chart.js\"></script>\n";
    echo "<script src=\"/assets/js/admin.js\"></script>\n";
    echo "</body>\n</html>";
}
