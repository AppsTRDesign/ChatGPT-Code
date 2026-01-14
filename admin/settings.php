<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

admin_header('Site Ayarları');
?>
<section class="panel">
    <form class="admin-form" data-ajax="settings" enctype="multipart/form-data" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <label>Base URL<input type="text" name="base_url" value="<?= htmlspecialchars(settings('base_url')) ?>"></label>
        <label>Site Adı<input type="text" name="site_name" value="<?= htmlspecialchars(settings('site_name')) ?>"></label>
        <label>Adres<input type="text" name="site_address" value="<?= htmlspecialchars(settings('site_address')) ?>"></label>
        <label>Harita Embed URL<input type="text" name="map_embed" value="<?= htmlspecialchars(settings('map_embed')) ?>"></label>
        <label>İletişim Telefonu<input type="text" name="contact_phone" value="<?= htmlspecialchars(settings('contact_phone')) ?>"></label>
        <label>WhatsApp Sipariş Numarası<input type="text" name="whatsapp_number" value="<?= htmlspecialchars(settings('whatsapp_number')) ?>"></label>
        <label>İletişim E-postası<input type="email" name="contact_email" value="<?= htmlspecialchars(settings('contact_email')) ?>"></label>
        <label>Meta Başlık<input type="text" name="meta_title" value="<?= htmlspecialchars(settings('meta_title')) ?>"></label>
        <label>Meta Açıklama<textarea name="meta_description" rows="3"><?= htmlspecialchars(settings('meta_description')) ?></textarea></label>
        <label>Logo<input type="file" name="logo"></label>
        <label>Favicon<input type="file" name="favicon"></label>
        <label>Galeri Lightbox
            <select name="lightbox_provider">
                <option value="glightbox" <?= settings('lightbox_provider') === 'glightbox' ? 'selected' : '' ?>>GLightbox</option>
                <option value="lightbox2" <?= settings('lightbox_provider') === 'lightbox2' ? 'selected' : '' ?>>Lightbox2</option>
            </select>
        </label>
        <label>Renk Teması<input type="color" name="theme_color" value="<?= htmlspecialchars(settings('theme_color')) ?>"></label>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<?php
admin_footer();
?>
