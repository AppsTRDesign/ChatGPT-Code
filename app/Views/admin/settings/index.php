<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h1 class="h5 mb-0">Sistem Ayarları</h1>
        <button class="btn btn-primary" form="settings-form">Tümünü Kaydet</button>
    </div>
    <div class="card-body">
        <form id="settings-form" data-ajax="true" data-json="true" data-endpoint="/admin/settings/save" data-reset="false">
            <div class="row g-4">
                <div class="col-12 col-xl-4">
                    <div class="card border-0 h-100 bg-light-subtle">
                        <div class="card-body">
                            <h2 class="h6 text-uppercase text-muted">Genel Bilgiler</h2>
                            <div class="mb-3">
                                <label class="form-label">Site Adı</label>
                                <input type="text" name="general[site_name]" class="form-control" value="<?= htmlspecialchars($general['site_name'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alt Başlık</label>
                                <input type="text" name="general[tagline]" class="form-control" value="<?= htmlspecialchars($general['tagline'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta Açıklaması</label>
                                <textarea name="general[meta_description]" class="form-control" rows="3"><?= htmlspecialchars($general['meta_description'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Header HTML</label>
                                <textarea name="general[header_html]" class="form-control" rows="2"><?= htmlspecialchars($general['header_html'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="form-label">Footer HTML</label>
                                <textarea name="general[footer_html]" class="form-control" rows="2"><?= htmlspecialchars($general['footer_html'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card border-0 h-100 bg-light-subtle">
                        <div class="card-body">
                            <h2 class="h6 text-uppercase text-muted">Ödeme Ayarları</h2>
                            <div class="form-check form-switch mb-3">
                                <input type="checkbox" class="form-check-input" id="iyzico-enabled" name="payments[iyzico_enabled]" value="1" <?= !empty($payments['iyzico_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="iyzico-enabled">IyziCo Aktif</label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input type="checkbox" class="form-check-input" id="bank-enabled" name="payments[bank_enabled]" value="1" <?= !empty($payments['bank_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="bank-enabled">Havale / EFT Aktif</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Banka Bilgileri</label>
                                <textarea name="payments[bank_details]" class="form-control" rows="3" placeholder="Banka adı, IBAN, açıklama"><?= htmlspecialchars($payments['bank_details'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Manuel Ödeme Notu</label>
                                <textarea name="payments[manual_note]" class="form-control" rows="2"><?= htmlspecialchars($payments['manual_note'] ?? '') ?></textarea>
                            </div>
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="invoice-required" name="payments[invoice_required]" value="1" <?= !empty($payments['invoice_required']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="invoice-required">Fatura Bilgisi Zorunlu</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card border-0 h-100 bg-light-subtle">
                        <div class="card-body">
                            <h2 class="h6 text-uppercase text-muted">E-posta Ayarları</h2>
                            <div class="form-check form-switch mb-3">
                                <input type="checkbox" class="form-check-input" id="mail-enabled" name="mail[enabled]" value="1" <?= !empty($mail['enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="mail-enabled">Mail Gönderimi Aktif</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gönderim Yöntemi</label>
                                <select name="mail[driver]" class="form-select">
                                    <option value="smtp" <?= (($mail['driver'] ?? '') === 'smtp') ? 'selected' : '' ?>>SMTP</option>
                                    <option value="phpmail" <?= (($mail['driver'] ?? '') === 'phpmail') ? 'selected' : '' ?>>PHP mail()</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sunucu</label>
                                <input type="text" name="mail[host]" class="form-control" value="<?= htmlspecialchars($mail['host'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kullanıcı Adı</label>
                                <input type="text" name="mail[username]" class="form-control" value="<?= htmlspecialchars($mail['username'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Şifre</label>
                                <input type="password" name="mail[password]" class="form-control" value="<?= htmlspecialchars($mail['password'] ?? '') ?>">
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label">Port</label>
                                    <input type="number" name="mail[port]" class="form-control" value="<?= htmlspecialchars($mail['port'] ?? '587') ?>">
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
                                <input type="text" name="mail[from_name]" class="form-control" value="<?= htmlspecialchars($mail['from_name'] ?? '') ?>">
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Gönderen E-posta</label>
                                <input type="email" name="mail[from_email]" class="form-control" value="<?= htmlspecialchars($mail['from_email'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mt-1">
                <div class="col-12 col-xl-4">
                    <div class="card border-0 h-100 bg-light-subtle">
                        <div class="card-body">
                            <h2 class="h6 text-uppercase text-muted">Bildirim &amp; API</h2>
                            <div class="mb-3">
                                <label class="form-label">Varsayılan Rapor E-postası</label>
                                <input type="email" name="notifications[default_report_email]" class="form-control" value="<?= htmlspecialchars($notifications['default_report_email'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Base URL</label>
                                <input type="text" name="notifications[base_url]" class="form-control" value="<?= htmlspecialchars($notifications['base_url'] ?? '') ?>" placeholder="https://webpush.noasoft.org">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Public VAPID</label>
                                <input type="text" name="notifications[vapid_public]" class="form-control" value="<?= htmlspecialchars($notifications['vapid_public'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Private VAPID</label>
                                <input type="text" name="notifications[vapid_private]" class="form-control" value="<?= htmlspecialchars($notifications['vapid_private'] ?? '') ?>">
                            </div>
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="notifications-geo" name="notifications[geo_enabled]" value="1" <?= !empty($notifications['geo_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notifications-geo">GeoIP Hedefleme Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card border-0 h-100 bg-light-subtle">
                        <div class="card-body">
                            <h2 class="h6 text-uppercase text-muted">IyziCo Ayarları</h2>
                            <div class="mb-3">
                                <label class="form-label">API Key</label>
                                <input type="text" name="iyzico[api_key]" class="form-control" value="<?= htmlspecialchars($iyzico['api_key'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Secret Key</label>
                                <input type="text" name="iyzico[secret_key]" class="form-control" value="<?= htmlspecialchars($iyzico['secret_key'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Base URL</label>
                                <input type="text" name="iyzico[base_url]" class="form-control" value="<?= htmlspecialchars($iyzico['base_url'] ?? 'https://sandbox-api.iyzipay.com') ?>">
                            </div>
                            <div>
                                <label class="form-label">Webhook Secret</label>
                                <input type="text" name="iyzico[webhook_secret]" class="form-control" value="<?= htmlspecialchars($iyzico['webhook_secret'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card border-0 h-100 bg-light-subtle">
                        <div class="card-body">
                            <h2 class="h6 text-uppercase text-muted">Entegrasyonlar</h2>
                            <div class="form-check form-switch mb-3">
                                <input type="checkbox" class="form-check-input" id="firebase-enabled" name="firebase[enabled]" value="1" <?= !empty($firebase['enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="firebase-enabled">Firebase Sosyal Giriş Aktif</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Firebase API Key</label>
                                <input type="text" name="firebase[api_key]" class="form-control" value="<?= htmlspecialchars($firebase['api_key'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Firebase Project ID</label>
                                <input type="text" name="firebase[project_id]" class="form-control" value="<?= htmlspecialchars($firebase['project_id'] ?? '') ?>">
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input type="checkbox" class="form-check-input" id="analytics-enabled" name="analytics[enabled]" value="1" <?= !empty($analytics['enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="analytics-enabled">Google Analytics Aktif</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">GA Ölçüm ID</label>
                                <input type="text" name="analytics[tracking_id]" class="form-control" value="<?= htmlspecialchars($analytics['tracking_id'] ?? '') ?>" placeholder="G-XXXXXXX">
                            </div>
                            <div>
                                <label class="form-label">Tag Manager Container</label>
                                <input type="text" name="analytics[container_id]" class="form-control" value="<?= htmlspecialchars($analytics['container_id'] ?? '') ?>" placeholder="GTM-XXXX">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 mb-0 text-uppercase text-muted">Logo</h2>
                    <small class="text-muted">Dropzone ile yeni logo yükleyin</small>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <?php if (!empty($branding['logo'])): ?>
                        <img src="<?= htmlspecialchars($branding['logo']) ?>" alt="Logo" class="img-fluid mb-2" id="logo-preview">
                    <?php else: ?>
                        <div class="text-muted small mb-2" id="logo-preview">Henüz logo yüklenmedi.</div>
                    <?php endif; ?>
                </div>
                <form action="/admin/settings/upload-brand" class="dropzone" id="logo-dropzone" data-dropzone="true" data-type="logo" data-preview="#logo-preview">
                    <input type="hidden" name="type" value="logo">
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 mb-0 text-uppercase text-muted">Favicon</h2>
                    <small class="text-muted">ICO / PNG formatında yükleyin</small>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <?php if (!empty($branding['favicon'])): ?>
                        <img src="<?= htmlspecialchars($branding['favicon']) ?>" alt="Favicon" class="img-fluid mb-2" id="favicon-preview" style="max-width: 96px;">
                    <?php else: ?>
                        <div class="text-muted small mb-2" id="favicon-preview">Henüz favicon yüklenmedi.</div>
                    <?php endif; ?>
                </div>
                <form action="/admin/settings/upload-brand" class="dropzone" id="favicon-dropzone" data-dropzone="true" data-type="favicon" data-preview="#favicon-preview">
                    <input type="hidden" name="type" value="favicon">
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
