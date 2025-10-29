<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
$settings = fetch_settings($pdo);
$allowedExtensionText = '';
if (!empty($settings['allowed_extensions'])) {
    $decoded = json_decode($settings['allowed_extensions'], true);
    if (is_array($decoded)) {
        $allowedExtensionText = implode("\n", $decoded);
    } else {
        $allowedExtensionText = (string) $settings['allowed_extensions'];
    }
}
$logoUrl = !empty($settings['logo']) ? BASE_URL . '/uploads/' . ltrim($settings['logo'], '/') : '';
$faviconUrl = !empty($settings['favicon']) ? BASE_URL . '/uploads/' . ltrim($settings['favicon'], '/') : '';
$bannerUrl = !empty($settings['brand_banner']) ? BASE_URL . '/uploads/' . ltrim($settings['brand_banner'], '/') : '';
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/admin-settings.js?v=1.1.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <form id="settingsForm" class="row g-4" enctype="multipart/form-data">
        <div class="col-12">
            <div class="card card-glass h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Meta &amp; İçerik Ayarları</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Meta Başlık</label>
                            <input type="text" name="meta_title" class="form-control" value="<?= sanitize($settings['meta_title'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meta Açıklama</label>
                            <input type="text" name="meta_description" class="form-control" value="<?= sanitize($settings['meta_description'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Meta Anahtar Kelimeler</label>
                            <input type="text" name="meta_keywords" class="form-control" placeholder="dosya yükleme, bulut depolama" value="<?= sanitize($settings['meta_keywords'] ?? '') ?>">
                            <small class="text-white-50">Anahtar kelimeleri virgül ile ayırabilirsiniz.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Header HTML</label>
                            <textarea name="header_html" class="form-control" rows="3"><?= sanitize($settings['header_html'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Footer HTML</label>
                            <textarea name="footer_html" class="form-control" rows="3"><?= sanitize($settings['footer_html'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card card-glass h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Sosyal &amp; Paylaşım Meta Verileri</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Sosyal Başlık</label>
                            <input type="text" name="social_title" class="form-control" value="<?= sanitize($settings['social_title'] ?? '') ?>" placeholder="Paylaşımlarda görünecek başlık">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Twitter Kullanıcı Adı</label>
                            <input type="text" name="twitter_handle" class="form-control" value="<?= sanitize($settings['twitter_handle'] ?? '') ?>" placeholder="ornekhesap">
                            <small class="text-white-50">"@" işareti eklemeden yazın.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sosyal Açıklama</label>
                            <textarea name="social_description" class="form-control" rows="3" placeholder="Paylaşımlarda görünecek açıklama"><?= sanitize($settings['social_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card card-glass h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Marka Ayarları</h2>
                    <p class="text-white-50 small mb-3">Karanlık arayüz için yüksek kontrastlı logolar önerilir.</p>
                    <div class="mb-4">
                        <label class="form-label">Logo</label>
                        <div class="dropzone dz-theme" id="logoDropzone"
                            data-existing-url="<?= sanitize($logoUrl) ?>"
                            data-existing-name="Mevcut logo"
                            data-preview-target="#logoPreview"
                            data-empty-text="Henüz logo seçilmedi.">
                            <div class="dz-message">Logo dosyanızı sürükleyin veya tıklayın.</div>
                        </div>
                        <div class="brand-preview mt-2" id="logoPreview">
                            <?php if ($logoUrl): ?>
                                <img src="<?= sanitize($logoUrl) ?>" alt="Logo önizleme" class="img-thumbnail bg-white" width="96" height="96">
                            <?php else: ?>
                                <span class="text-white-50 small d-block">Henüz logo yüklenmedi.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Favicon</label>
                        <div class="dropzone dz-theme" id="faviconDropzone"
                            data-existing-url="<?= sanitize($faviconUrl) ?>"
                            data-existing-name="Mevcut favicon"
                            data-preview-target="#faviconPreview"
                            data-empty-text="Henüz favicon seçilmedi.">
                            <div class="dz-message">Favicon dosyanızı sürükleyin veya tıklayın.</div>
                        </div>
                        <div class="brand-preview mt-2" id="faviconPreview">
                            <?php if ($faviconUrl): ?>
                                <img src="<?= sanitize($faviconUrl) ?>" alt="Favicon önizleme" class="img-thumbnail bg-white" width="48" height="48">
                            <?php else: ?>
                                <span class="text-white-50 small d-block">Henüz favicon yüklenmedi.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="form-label">Banner</label>
                        <div class="dropzone dz-theme" id="bannerDropzone"
                            data-existing-url="<?= sanitize($bannerUrl) ?>"
                            data-existing-name="Mevcut banner"
                            data-preview-target="#bannerPreview"
                            data-empty-text="Henüz banner seçilmedi.">
                            <div class="dz-message">Banner görselinizi sürükleyin veya tıklayın.</div>
                        </div>
                        <div class="brand-preview mt-2" id="bannerPreview">
                            <?php if ($bannerUrl): ?>
                                <img src="<?= sanitize($bannerUrl) ?>" alt="Banner önizleme" class="img-fluid rounded border border-light border-opacity-25">
                            <?php else: ?>
                                <span class="text-white-50 small d-block">Henüz banner yüklenmedi.</span>
                            <?php endif; ?>
                        </div>
                        <small class="text-white-50">Paylaşım sayfaları ve sosyal önizlemeler bu görseli kullanır.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card card-glass h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Analitik &amp; Takip</h2>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="analyticsEnabled" name="analytics_enabled" <?= !empty($settings['analytics_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="analyticsEnabled">Google Analytics kodunu aktif et</label>
                    </div>
                    <label class="form-label">Analytics Kodu</label>
                    <textarea name="analytics_code" class="form-control mb-3" rows="3"><?= sanitize($settings['analytics_code'] ?? '') ?></textarea>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="shareStatsEnabled" name="share_stats_enabled" <?= !empty($settings['share_stats_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="shareStatsEnabled">Paylaşım istatistiklerini topla</label>
                    </div>
                    <label class="form-label">GeoIP Veritabanı Yolu</label>
                    <input type="text" name="geoip_database_path" class="form-control mb-3" value="<?= sanitize($settings['geoip_database_path'] ?? '') ?>" placeholder="<?= sanitize(__DIR__ . '/../uploads/geo/GeoLite2-City.mmdb') ?>">
                    <div class="dropzone dz-theme" id="geoipDropzone" data-existing-name="Mevcut GeoIP" data-empty-text="Henüz GeoIP veritabanı seçilmedi.">
                        <div class="dz-message">GeoIP (.mmdb) dosyanızı sürükleyin veya tıklayın.</div>
                    </div>
                    <div class="progress progress-glass mt-3 d-none" id="geoipProgressWrapper">
                        <div class="progress-bar" id="geoipProgressBar" role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <small class="text-white-50 d-block mt-2">Yeni bir GeoLite2/GeoIP veritabanı yüklediğinizde dosya otomatik olarak <code>uploads/geo</code> klasörüne taşınır ve yol alanına işlenir.</small>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card card-glass h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Depo &amp; Paylaşım Politikaları</h2>
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label">İzin Verilen Dosya Uzantıları</label>
                            <textarea name="allowed_extensions" class="form-control" rows="5" placeholder="jpg&#10;png&#10;pdf"><?= sanitize($allowedExtensionText) ?></textarea>
                            <small class="text-white-50">Her satıra bir uzantı yazın, başında nokta kullanmanıza gerek yoktur.</small>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">Paylaşım Süresi (dakika)</label>
                            <input type="number" name="share_expiry_minutes" class="form-control" value="<?= sanitize($settings['share_expiry_minutes'] ?? 1440) ?>">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="publicSharingEnabled" name="public_sharing_enabled" <?= !empty($settings['public_sharing_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="publicSharingEnabled">Paylaşım bağlantıları aktif</label>
                            </div>
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="folderPasswordsEnabled" name="folder_passwords_enabled" <?= !empty($settings['folder_passwords_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="folderPasswordsEnabled">Klasör şifreleme aktif</label>
                            </div>
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="sharePasswordRequired" name="share_password_required" <?= !empty($settings['share_password_required']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="sharePasswordRequired">Paylaşım bağlantıları için şifre zorunlu</label>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">Paylaşım İndirme Gecikmesi (saniye)</label>
                            <input type="number" min="0" name="share_download_delay" class="form-control mb-3" value="<?= sanitize($settings['share_download_delay'] ?? 0) ?>">
                            <label class="form-label">Saklama Politikası</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="autoArchiveEnabled" name="auto_archive_enabled" <?= !empty($settings['auto_archive_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="autoArchiveEnabled">Dosyaları belirtilen gün sonunda arşivle</label>
                            </div>
                            <input type="number" min="0" name="archive_after_days" class="form-control mt-2" placeholder="Arşivle (gün)" value="<?= sanitize($settings['archive_after_days'] ?? '') ?>">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="autoDeleteEnabled" name="auto_delete_enabled" <?= !empty($settings['auto_delete_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="autoDeleteEnabled">Dosyaları belirtilen gün sonunda sil</label>
                            </div>
                            <input type="number" min="0" name="delete_after_days" class="form-control mt-2" placeholder="Sil (gün)" value="<?= sanitize($settings['delete_after_days'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card card-glass h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Mail Ayarları</h2>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="mailEnabled" name="mail_enabled" <?= !empty($settings['mail_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="mailEnabled">E-posta gönderimini aktif et</label>
                    </div>
                    <label class="form-label">Gönderim Yöntemi</label>
                    <select name="mail_method" class="form-select mb-3">
                        <option value="smtp" <?= ($settings['mail_method'] ?? 'smtp') === 'smtp' ? 'selected' : '' ?>>SMTP</option>
                        <option value="phpmail" <?= ($settings['mail_method'] ?? '') === 'phpmail' ? 'selected' : '' ?>>PHP mail()</option>
                    </select>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Sunucu</label>
                            <input type="text" name="mail_host" class="form-control" value="<?= sanitize($settings['mail_host'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Port</label>
                            <input type="number" name="mail_port" class="form-control" value="<?= sanitize($settings['mail_port'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kullanıcı</label>
                            <input type="text" name="mail_username" class="form-control" value="<?= sanitize($settings['mail_username'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Şifre</label>
                            <input type="password" name="mail_password" class="form-control" value="<?= sanitize($settings['mail_password'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Şifreleme</label>
                            <input type="text" name="mail_encryption" class="form-control" placeholder="tls / ssl" value="<?= sanitize($settings['mail_encryption'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gönderen Adı</label>
                            <input type="text" name="mail_from_name" class="form-control" value="<?= sanitize($settings['mail_from_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gönderen E-posta</label>
                            <input type="email" name="mail_from_address" class="form-control" value="<?= sanitize($settings['mail_from_address'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card card-glass h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Reklam Alanları</h2>
                    <label class="form-label">Panel Reklam Alanı (HTML)</label>
                    <textarea name="ad_dashboard_html" class="form-control mb-3" rows="3"><?= sanitize($settings['ad_dashboard_html'] ?? '') ?></textarea>
                    <label class="form-label">Paylaşım Sayfası Üst Reklamı</label>
                    <textarea name="ad_share_top_html" class="form-control mb-3" rows="3"><?= sanitize($settings['ad_share_top_html'] ?? '') ?></textarea>
                    <label class="form-label">Paylaşım Sayfası Alt Reklamı</label>
                    <textarea name="ad_share_bottom_html" class="form-control" rows="3"><?= sanitize($settings['ad_share_bottom_html'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card card-glass h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Stripe Ayarları</h2>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="stripeEnabled" name="stripe_enabled" <?= !empty($settings['stripe_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="stripeEnabled">Stripe ile ödeme al</label>
                    </div>
                    <label class="form-label">Para Birimi</label>
                    <input type="text" name="payment_currency" class="form-control mb-3" value="<?= sanitize($settings['payment_currency'] ?? 'TRY') ?>">
                    <label class="form-label">Gizli Anahtar</label>
                    <input type="text" name="stripe_api_key" class="form-control mb-3" value="<?= sanitize($settings['stripe_api_key'] ?? '') ?>">
                    <label class="form-label">Yayınlanabilir Anahtar</label>
                    <input type="text" name="stripe_publishable_key" class="form-control mb-3" value="<?= sanitize($settings['stripe_publishable_key'] ?? '') ?>">
                    <label class="form-label">Webhook Secret</label>
                    <input type="text" name="stripe_webhook_secret" class="form-control" value="<?= sanitize($settings['stripe_webhook_secret'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card card-glass h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Iyzico Ayarları</h2>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="iyzicoEnabled" name="iyzico_enabled" <?= !empty($settings['iyzico_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="iyzicoEnabled">Iyzico ile ödeme al</label>
                    </div>
                    <label class="form-label">API Key</label>
                    <input type="text" name="iyzico_api_key" class="form-control mb-3" value="<?= sanitize($settings['iyzico_api_key'] ?? '') ?>">
                    <label class="form-label">Secret Key</label>
                    <input type="text" name="iyzico_secret_key" class="form-control mb-3" value="<?= sanitize($settings['iyzico_secret_key'] ?? '') ?>">
                    <label class="form-label">Base URL</label>
                    <input type="text" name="iyzico_base_url" class="form-control" placeholder="https://api.iyzipay.com" value="<?= sanitize($settings['iyzico_base_url'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card card-glass h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Havale / EFT Ayarları</h2>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="bankTransferEnabled" name="bank_transfer_enabled" <?= !empty($settings['bank_transfer_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="bankTransferEnabled">Havale / EFT seçeneğini aktif et</label>
                    </div>
                    <label class="form-label">Talimat Metni</label>
                    <textarea name="bank_transfer_instructions" class="form-control" rows="6" placeholder="IBAN, açıklama, onay süreci vb."><?= sanitize($settings['bank_transfer_instructions'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-12 text-end">
            <button type="button" class="btn btn-gradient px-4" onclick="saveSettings()">
                <i class="bi bi-save me-2"></i>Ayarları Kaydet
            </button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
