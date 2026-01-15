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
        <label>Ana Sayfa Görünümü
            <select name="homepage_layout">
                <option value="grid" <?= settings('homepage_layout') === 'grid' ? 'selected' : '' ?>>Grid</option>
                <option value="list" <?= settings('homepage_layout') === 'list' ? 'selected' : '' ?>>Liste</option>
            </select>
        </label>
        <label>Ana Sayfa Ürün Limitleri</label>
        <div class="form-grid">
            <input type="number" name="homepage_latest_limit" value="<?= htmlspecialchars(settings('homepage_latest_limit', '8')) ?>" placeholder="En Yeni">
            <input type="number" name="homepage_ordered_limit" value="<?= htmlspecialchars(settings('homepage_ordered_limit', '8')) ?>" placeholder="En Çok Sipariş">
            <input type="number" name="homepage_visited_limit" value="<?= htmlspecialchars(settings('homepage_visited_limit', '8')) ?>" placeholder="En Çok Ziyaret">
            <input type="number" name="homepage_favorited_limit" value="<?= htmlspecialchars(settings('homepage_favorited_limit', '8')) ?>" placeholder="En Çok Favori">
            <input type="number" name="reviews_per_page" value="<?= htmlspecialchars(settings('reviews_per_page', '5')) ?>" placeholder="Yorum Sayısı">
        </div>
        <label>Banka Havalesi Ayarları</label>
        <label>Havale Aktif
            <select name="bank_transfer_active">
                <option value="0" <?= settings('bank_transfer_active') === '0' ? 'selected' : '' ?>>Hayır</option>
                <option value="1" <?= settings('bank_transfer_active') === '1' ? 'selected' : '' ?>>Evet</option>
            </select>
        </label>
        <label>Banka Adı<input type="text" name="bank_name" value="<?= htmlspecialchars(settings('bank_name')) ?>"></label>
        <label>IBAN<input type="text" name="bank_iban" value="<?= htmlspecialchars(settings('bank_iban')) ?>"></label>
        <label>Alıcı Ad Soyad<input type="text" name="bank_account_name" value="<?= htmlspecialchars(settings('bank_account_name')) ?>"></label>
        <label>Renk Teması<input type="color" name="theme_color" value="<?= htmlspecialchars(settings('theme_color')) ?>"></label>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<?php
admin_footer();
?>
