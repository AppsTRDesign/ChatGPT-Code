<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
$settings = fetch_settings($pdo);
$allowedMimeText = '';
if (!empty($settings['allowed_mime_types'])) {
    $decoded = json_decode($settings['allowed_mime_types'], true);
    if (is_array($decoded)) {
        $allowedMimeText = implode("\n", $decoded);
    } else {
        $allowedMimeText = (string) $settings['allowed_mime_types'];
    }
}
global $pageScripts;
$pageScripts[] = '<script src="' . BASE_URL . '/assets/js/admin-settings.js?v=1.0.0"></script>';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="card card-glass p-4">
        <h2 class="h5 mb-4">Genel Ayarlar</h2>
        <form id="settingsForm" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Meta Başlık</label>
                    <input type="text" name="meta_title" class="form-control" value="<?= sanitize($settings['meta_title'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Meta Açıklama</label>
                    <input type="text" name="meta_description" class="form-control" value="<?= sanitize($settings['meta_description'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Header HTML</label>
                    <textarea name="header_html" class="form-control" rows="3"><?= sanitize($settings['header_html'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Footer HTML</label>
                    <textarea name="footer_html" class="form-control" rows="3"><?= sanitize($settings['footer_html'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Logo</label>
                    <div class="dropzone" id="logoDropzone">
                        <div class="dz-message">Logo dosyanızı sürükleyin veya tıklayın.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Favicon</label>
                    <div class="dropzone" id="faviconDropzone">
                        <div class="dz-message">Favicon dosyanızı sürükleyin veya tıklayın.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mail Sunucu</label>
                    <input type="text" name="mail_host" class="form-control" value="<?= sanitize($settings['mail_host'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mail Port</label>
                    <input type="number" name="mail_port" class="form-control" value="<?= sanitize($settings['mail_port'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mail Kullanıcı</label>
                    <input type="text" name="mail_username" class="form-control" value="<?= sanitize($settings['mail_username'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mail Şifre</label>
                    <input type="password" name="mail_password" class="form-control" value="<?= sanitize($settings['mail_password'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Şifreleme</label>
                    <input type="text" name="mail_encryption" class="form-control" value="<?= sanitize($settings['mail_encryption'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Google Analytics Kodu</label>
                    <textarea name="analytics_code" class="form-control" rows="3"><?= sanitize($settings['analytics_code'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="analyticsEnabled" <?= !empty($settings['analytics_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="analyticsEnabled">Analytics aktif</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">İzin Verilen MIME Türleri</label>
                    <textarea name="allowed_mime_types" class="form-control" rows="4" placeholder="image/jpeg
application/pdf"><?= sanitize($allowedMimeText) ?></textarea>
                    <small class="text-white-50">Virgül veya satır sonu ile ayırın.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Paylaşım Süresi (dakika)</label>
                    <input type="number" name="share_expiry_minutes" class="form-control" value="<?= sanitize($settings['share_expiry_minutes'] ?? 1440) ?>">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="publicSharingEnabled" <?= !empty($settings['public_sharing_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="publicSharingEnabled">Paylaşım bağlantıları aktif</label>
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="folderPasswordsEnabled" <?= !empty($settings['folder_passwords_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="folderPasswordsEnabled">Klasör şifreleme aktif</label>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Paylaşım İndirme Gecikmesi (saniye)</label>
                        <input type="number" min="0" name="share_download_delay" class="form-control" value="<?= sanitize($settings['share_download_delay'] ?? 0) ?>">
                        <small class="text-white-50">Paylaşım sayfasındaki indirme butonu bu süre dolana kadar pasif kalır.</small>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Panel Reklam Alanı (HTML)</label>
                    <textarea name="ad_dashboard_html" class="form-control" rows="3"><?= sanitize($settings['ad_dashboard_html'] ?? '') ?></textarea>
                    <small class="text-white-50">Admin ve kullanıcı panellerinde kullanılabilecek özel HTML blokları.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Paylaşım Sayfası Üst Reklamı</label>
                    <textarea name="ad_share_top_html" class="form-control" rows="3"><?= sanitize($settings['ad_share_top_html'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Paylaşım Sayfası Alt Reklamı</label>
                    <textarea name="ad_share_bottom_html" class="form-control" rows="3"><?= sanitize($settings['ad_share_bottom_html'] ?? '') ?></textarea>
                </div>
            </div>
            <button type="button" class="btn btn-gradient mt-4" onclick="saveSettings()">Kaydet</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
