<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

admin_header('Site Ayarları');
?>
<section class="panel">
    <form class="admin-form settings-form" data-ajax="settings" enctype="multipart/form-data" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <fieldset class="settings-group">
            <legend>Genel</legend>
            <label>Base URL<input type="text" name="base_url" value="<?= htmlspecialchars(settings('base_url')) ?>"></label>
            <label>Site Adı<input type="text" name="site_name" value="<?= htmlspecialchars(settings('site_name')) ?>"></label>
            <label>Adres<input type="text" name="site_address" value="<?= htmlspecialchars(settings('site_address')) ?>"></label>
        </fieldset>
        <fieldset class="settings-group">
            <legend>İletişim</legend>
            <label>Harita Embed (HTML)
                <textarea name="map_embed" rows="4"><?= htmlspecialchars(settings('map_embed')) ?></textarea>
            </label>
            <label>İletişim Telefonu<input type="text" name="contact_phone" value="<?= htmlspecialchars(settings('contact_phone')) ?>"></label>
            <label>WhatsApp Sipariş Numarası<input type="text" name="whatsapp_number" value="<?= htmlspecialchars(settings('whatsapp_number')) ?>"></label>
            <label>İletişim E-postası<input type="email" name="contact_email" value="<?= htmlspecialchars(settings('contact_email')) ?>"></label>
        </fieldset>
        <fieldset class="settings-group">
            <legend>SEO</legend>
            <label>Meta Başlık<input type="text" name="meta_title" value="<?= htmlspecialchars(settings('meta_title')) ?>"></label>
            <label>Meta Açıklama<textarea name="meta_description" rows="3"><?= htmlspecialchars(settings('meta_description')) ?></textarea></label>
        </fieldset>
        <fieldset class="settings-group">
            <legend>Görünüm</legend>
            <div class="settings-grid">
                <label>Logo<input type="file" name="logo"></label>
                <label>Favicon<input type="file" name="favicon"></label>
            </div>
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
            <label>Site Genişliği
                <select name="site_width">
                    <option value="box" <?= settings('site_width', 'box') === 'box' ? 'selected' : '' ?>>Box</option>
                    <option value="wide" <?= settings('site_width', 'box') === 'wide' ? 'selected' : '' ?>>Wide</option>
                </select>
            </label>
        </fieldset>
        <fieldset class="settings-group">
            <legend>Tema Renkleri</legend>
            <div class="settings-grid">
                <label>Tema Rengi
                    <input class="color-input" type="color" name="theme_color" value="<?= htmlspecialchars(settings('theme_color')) ?>">
                </label>
                <label>Mobil Menü Aç/Kapa Rengi
                    <input class="color-input" type="color" name="mobile_menu_toggle_color" value="<?= htmlspecialchars(settings('mobile_menu_toggle_color')) ?>">
                </label>
                <label>Mobil Tema Yazı Rengi
                    <input class="color-input" type="color" name="mobile_menu_text_color" value="<?= htmlspecialchars(settings('mobile_menu_text_color')) ?>">
                </label>
            </div>
        </fieldset>
        <fieldset class="settings-group">
            <legend>Ana Sayfa Limitleri</legend>
            <div class="settings-grid">
                <label>En Yeni Ürün Limiti
                    <input type="number" name="homepage_latest_limit" value="<?= htmlspecialchars(settings('homepage_latest_limit', '8')) ?>">
                </label>
                <label>En Çok Sipariş Limiti
                    <input type="number" name="homepage_ordered_limit" value="<?= htmlspecialchars(settings('homepage_ordered_limit', '8')) ?>">
                </label>
                <label>En Çok Ziyaret Limiti
                    <input type="number" name="homepage_visited_limit" value="<?= htmlspecialchars(settings('homepage_visited_limit', '8')) ?>">
                </label>
                <label>En Çok Favori Limiti
                    <input type="number" name="homepage_favorited_limit" value="<?= htmlspecialchars(settings('homepage_favorited_limit', '8')) ?>">
                </label>
                <label>İndirimli Ürün Limiti
                    <input type="number" name="homepage_discounted_limit" value="<?= htmlspecialchars(settings('homepage_discounted_limit', '8')) ?>">
                </label>
                <label>Yorum Sayısı (Sayfalama)
                    <input type="number" name="reviews_per_page" value="<?= htmlspecialchars(settings('reviews_per_page', '5')) ?>">
                </label>
            </div>
        </fieldset>
        <fieldset class="settings-group">
            <legend>Ödeme & Teslimat</legend>
            <div class="settings-grid">
                <label>KDV Oranı (%)
                    <input type="number" name="vat_rate" step="0.01" min="0" value="<?= htmlspecialchars(settings('vat_rate', '0')) ?>">
                </label>
                <label>Teslimat Ücreti
                    <input type="number" name="shipping_fee" step="0.01" min="0" value="<?= htmlspecialchars(settings('shipping_fee', '0')) ?>">
                </label>
                <label>Havale Aktif
                    <select name="bank_transfer_active">
                        <option value="0" <?= settings('bank_transfer_active') === '0' ? 'selected' : '' ?>>Hayır</option>
                        <option value="1" <?= settings('bank_transfer_active') === '1' ? 'selected' : '' ?>>Evet</option>
                    </select>
                </label>
            </div>
            <label>Banka Adı (Teslimat Bilgileri)
                <input type="text" name="bank_name" value="<?= htmlspecialchars(settings('bank_name')) ?>">
            </label>
            <label>IBAN (Teslimat Bilgileri)
                <input type="text" name="bank_iban" value="<?= htmlspecialchars(settings('bank_iban')) ?>">
            </label>
            <label>Alıcı Ad Soyad (Teslimat Bilgileri)
                <input type="text" name="bank_account_name" value="<?= htmlspecialchars(settings('bank_account_name')) ?>">
            </label>
        </fieldset>
        <fieldset class="settings-group">
            <legend>Header & Footer</legend>
            <label>Header HTML
                <textarea name="header_html" rows="4"><?= htmlspecialchars(settings('header_html')) ?></textarea>
            </label>
            <label>Footer HTML
                <textarea name="footer_html" rows="4"><?= htmlspecialchars(settings('footer_html')) ?></textarea>
            </label>
        </fieldset>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<?php
admin_footer();
?>
