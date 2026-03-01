<?php
require_once __DIR__ . '/helpers.php';

function render_footer(): void
{
    $siteName = settings('site_name', 'Çiçek');
    $phone = settings('contact_phone');
    $email = settings('contact_email');
    $address = settings('site_address');
    $pages = db()->query('SELECT title, slug FROM pages ORDER BY title ASC')->fetchAll(PDO::FETCH_ASSOC);
    $socialLinks = db()->query('SELECT * FROM social_links ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
    echo "<footer class=\"site-footer\">\n<div class=\"container\">\n";
    echo "<div class=\"footer-grid\">\n";
    echo "<div class=\"footer-brand\">\n<strong>{$siteName}</strong>\n<p>{$address}</p>\n</div>\n";
    $phoneTitle = htmlspecialchars($siteName . ' Telefon Numarası');
    $emailTitle = htmlspecialchars($siteName . ' Mail Adresi');
    $phoneEscaped = htmlspecialchars($phone);
    $emailEscaped = htmlspecialchars($email);
    echo "<div class=\"footer-contact\">\n";
    echo "<p>Telefon: <a href=\"tel:{$phoneEscaped}\" title=\"{$phoneTitle}\">{$phoneEscaped}</a></p>\n";
    echo "<p>E-posta: <a href=\"mailto:{$emailEscaped}\" title=\"{$emailTitle}\">{$emailEscaped}</a></p>\n";
    echo "</div>\n";
    echo "<div class=\"footer-links\">\n<h4>Sayfalar</h4>\n<ul>\n";
    foreach ($pages as $page) {
        $title = htmlspecialchars($page['title']);
        echo "<li><a href=\"/page/{$page['slug']}\">{$title}</a></li>\n";
    }
    echo "</ul>\n</div>\n";
    echo "<div class=\"footer-social\">\n<h4>Bizi Takip Edin</h4>\n<div class=\"social-icons\">\n";
    foreach ($socialLinks as $link) {
        $url = htmlspecialchars($link['url']);
        $icon = htmlspecialchars($link['icon_class']);
        $label = htmlspecialchars($link['label']);
        echo "<a href=\"{$url}\" target=\"_blank\" rel=\"noopener\" aria-label=\"{$label}\" title=\"{$label}\"><i class=\"{$icon}\"></i></a>\n";
    }
    echo "</div>\n</div>\n";
    echo "</div>\n";
    $footerHtml = settings('footer_html');
    if ($footerHtml) {
        echo "<div class=\"footer-html\">{$footerHtml}</div>\n";
    }
    echo "<div class=\"footer-bottom\">© " . date('Y') . " {$siteName}.</div>\n";
    echo "</div>\n</footer>\n";
    echo "<script src=\"https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js\"></script>\n";
    echo "<script src=\"https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js\"></script>\n";
    if (settings('lightbox_provider') === 'lightbox2') {
        echo "<script src=\"https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js\"></script>\n";
    } else {
        echo "<script src=\"https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js\"></script>\n";
    }
    echo "<script src=\"https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js\"></script>\n";
    echo "<script src=\"/assets/js/app.js\"></script>\n";
    echo "<script src=\"/assets/js/gallery.js\"></script>\n";
    echo "<script src=\"/assets/js/checkout.js\"></script>\n";
    echo "</body>\n</html>";
}
