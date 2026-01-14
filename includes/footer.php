<?php
require_once __DIR__ . '/helpers.php';

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
