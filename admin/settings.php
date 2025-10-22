<?php
require __DIR__ . '/header.php';

use App\Helpers;
use App\Payment;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Payment::update([
        'iyzico_enabled' => isset($_POST['iyzico_enabled']) ? 1 : 0,
        'iyzico_api_key' => trim($_POST['iyzico_api_key'] ?? ''),
        'iyzico_secret_key' => trim($_POST['iyzico_secret_key'] ?? ''),
        'bank_account' => trim($_POST['bank_account'] ?? ''),
        'bank_enabled' => isset($_POST['bank_enabled']) ? 1 : 0,
    ]);
    Helpers::flash('message', 'Ayarlar güncellendi.');
    redirect('/admin/settings.php');
}

$settings = Payment::settings();
?>
<h1 class="h3 mb-4">Ödeme Ayarları</h1>
<div class="card p-4">
    <form method="post">
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="iyzico_enabled" name="iyzico_enabled" <?= $settings['iyzico_enabled'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="iyzico_enabled">İyzico (Kredi Kartı) Aktif</label>
        </div>
        <div class="mb-3">
            <label class="form-label">İyzico API Key</label>
            <input type="text" class="form-control" name="iyzico_api_key" value="<?= Helpers::e($settings['iyzico_api_key']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">İyzico Secret Key</label>
            <input type="text" class="form-control" name="iyzico_secret_key" value="<?= Helpers::e($settings['iyzico_secret_key']) ?>">
        </div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="bank_enabled" name="bank_enabled" <?= $settings['bank_enabled'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="bank_enabled">Banka Havalesi Aktif</label>
        </div>
        <div class="mb-3">
            <label class="form-label">Banka Bilgileri</label>
            <textarea class="form-control" name="bank_account" rows="4" placeholder="Banka adı, IBAN, açıklama"><?= Helpers::e($settings['bank_account']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Kaydet</button>
    </form>
</div>
<?php require __DIR__ . '/footer.php'; ?>
