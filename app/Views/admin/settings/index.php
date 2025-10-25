<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h1 class="h5 mb-0">Sistem Ayarları</h1>
        <button class="btn btn-primary" form="settings-form">Kaydet</button>
    </div>
    <div class="card-body">
        <form id="settings-form" data-ajax="true" data-json="true" data-endpoint="/admin/settings/save" data-reset="false">
            <div class="row g-4">
                <div class="col-12 col-lg-4">
                    <h2 class="h6 text-uppercase text-muted">SMTP</h2>
                    <div class="mb-3">
                        <label class="form-label">Sunucu</label>
                        <input type="text" name="mail[host]" value="<?= htmlspecialchars($mail['host'] ?? '') ?>" class="form-control" placeholder="smtp.example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" name="mail[username]" value="<?= htmlspecialchars($mail['username'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şifre</label>
                        <input type="password" name="mail[password]" value="<?= htmlspecialchars($mail['password'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Port</label>
                            <input type="number" name="mail[port]" value="<?= htmlspecialchars($mail['port'] ?? '587') ?>" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Şifreleme</label>
                            <select name="mail[encryption]" class="form-select">
                                <option value="tls" <?= (($mail['encryption'] ?? '') === 'tls') ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= (($mail['encryption'] ?? '') === 'ssl') ? 'selected' : '' ?>>SSL</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Gönderen Adı</label>
                        <input type="text" name="mail[from_name]" value="<?= htmlspecialchars($mail['from_name'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Gönderen E-posta</label>
                        <input type="email" name="mail[from_email]" value="<?= htmlspecialchars($mail['from_email'] ?? '') ?>" class="form-control">
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <h2 class="h6 text-uppercase text-muted">Bildirim</h2>
                    <div class="mb-3">
                        <label class="form-label">Varsayılan Rapor E-postası</label>
                        <input type="email" name="notifications[default_report_email]" value="<?= htmlspecialchars($notifications['default_report_email'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Web Push Base URL</label>
                        <input type="text" name="notifications[base_url]" value="<?= htmlspecialchars($notifications['base_url'] ?? '') ?>" class="form-control" placeholder="https://webpush.noasoft.org">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Public VAPID Anahtarı</label>
                        <input type="text" name="notifications[vapid_public]" value="<?= htmlspecialchars($notifications['vapid_public'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Private VAPID Anahtarı</label>
                        <input type="text" name="notifications[vapid_private]" value="<?= htmlspecialchars($notifications['vapid_private'] ?? '') ?>" class="form-control">
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <h2 class="h6 text-uppercase text-muted">IyziCo</h2>
                    <div class="mb-3">
                        <label class="form-label">API Key</label>
                        <input type="text" name="iyzico[api_key]" value="<?= htmlspecialchars($iyzico['api_key'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Secret Key</label>
                        <input type="text" name="iyzico[secret_key]" value="<?= htmlspecialchars($iyzico['secret_key'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Base URL</label>
                        <input type="text" name="iyzico[base_url]" value="<?= htmlspecialchars($iyzico['base_url'] ?? 'https://sandbox-api.iyzipay.com') ?>" class="form-control">
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
