<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

render_header('İletişim');
?>
<main class="container">
    <h1>İletişim</h1>
    <div class="contact-grid">
        <div>
            <p><strong>Adres:</strong> <?= htmlspecialchars(settings('site_address')) ?></p>
            <p><strong>Telefon:</strong> <?= htmlspecialchars(settings('contact_phone')) ?></p>
            <p><strong>E-posta:</strong> <?= htmlspecialchars(settings('contact_email')) ?></p>
        </div>
        <div class="map">
            <iframe src="<?= htmlspecialchars(settings('map_embed')) ?>" loading="lazy"></iframe>
        </div>
    </div>
    <form class="contact-form" data-ajax="contact" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-grid">
            <input type="text" name="name" placeholder="Ad Soyad" required>
            <input type="email" name="email" placeholder="E-posta" required>
            <textarea name="message" placeholder="Mesajınız" rows="4" required></textarea>
        </div>
        <button class="btn primary" type="submit">Gönder</button>
    </form>
</main>
<?php
render_footer();
?>
