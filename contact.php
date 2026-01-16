<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

render_header('İletişim');
?>
<main class="container contact-page">
    <div class="contact-hero">
        <div>
            <h1>İletişim</h1>
            <p>Bizimle iletişime geçin, size en kısa sürede dönüş yapalım.</p>
        </div>
        <div class="contact-card">
            <p><strong>Adres:</strong> <?= htmlspecialchars(settings('site_address')) ?></p>
            <p><strong>Telefon:</strong> <?= htmlspecialchars(settings('contact_phone')) ?></p>
            <p><strong>E-posta:</strong> <?= htmlspecialchars(settings('contact_email')) ?></p>
        </div>
    </div>
    <div class="contact-grid">
        <div class="contact-card">
            <h2>Mesaj Gönderin</h2>
            <form class="contact-form" data-ajax="contact" method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="form-grid">
                    <input type="text" name="name" placeholder="Ad Soyad" required>
                    <input type="email" name="email" placeholder="E-posta" required>
                    <textarea name="message" placeholder="Mesajınız" rows="4" required></textarea>
                </div>
                <button class="btn primary" type="submit">Gönder</button>
            </form>
        </div>
        <div class="contact-card map">
            <h2>Konum</h2>
            <div class="map-embed">
                <?php if (settings('map_embed')): ?>
                    <?= settings('map_embed') ?>
                <?php else: ?>
                    <div class="empty-state">Harita bilgisi eklenmedi.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php
render_footer();
?>
