<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

admin_header('PayTR Ayarları');
?>
<section class="panel">
    <p>PayTR entegrasyon dosyaları <code>/includes</code> klasörü üzerinden okunacak şekilde hazırlandı.</p>
    <form class="admin-form" data-ajax="paytr" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <label>PayTR Aktif<input type="checkbox" name="paytr_active" value="1" <?= settings('paytr_active') === '1' ? 'checked' : '' ?>></label>
        <label>Merchant ID<input type="text" name="paytr_merchant_id" value="<?= htmlspecialchars(settings('paytr_merchant_id')) ?>"></label>
        <label>Merchant Key<input type="text" name="paytr_merchant_key" value="<?= htmlspecialchars(settings('paytr_merchant_key')) ?>"></label>
        <label>Merchant Salt<input type="text" name="paytr_merchant_salt" value="<?= htmlspecialchars(settings('paytr_merchant_salt')) ?>"></label>
        <label>Başarılı Ödeme URL<input type="text" name="paytr_success_url" value="<?= htmlspecialchars(settings('paytr_success_url')) ?>"></label>
        <label>Başarısız Ödeme URL<input type="text" name="paytr_fail_url" value="<?= htmlspecialchars(settings('paytr_fail_url')) ?>"></label>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<?php
admin_footer();
?>
