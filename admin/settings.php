<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
$settings = fetch_settings($pdo);
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
            </div>
            <button type="button" class="btn btn-gradient mt-4" onclick="saveSettings()">Kaydet</button>
        </form>
    </div>
</div>
<script>
const logoDropzone = new Dropzone('#logoDropzone', {
    url: '#',
    autoProcessQueue: false,
    maxFiles: 1,
    acceptedFiles: 'image/*',
    addRemoveLinks: true,
    dictRemoveFile: 'Kaldır',
    init() {
        this.on('maxfilesexceeded', file => {
            this.removeAllFiles();
            this.addFile(file);
        });
    }
});

const faviconDropzone = new Dropzone('#faviconDropzone', {
    url: '#',
    autoProcessQueue: false,
    maxFiles: 1,
    acceptedFiles: 'image/*',
    addRemoveLinks: true,
    dictRemoveFile: 'Kaldır',
    init() {
        this.on('maxfilesexceeded', file => {
            this.removeAllFiles();
            this.addFile(file);
        });
    }
});

async function saveSettings() {
    const form = document.getElementById('settingsForm');
    const formData = new FormData(form);
    formData.append('action', 'update-settings');
    formData.append('csrf_token', window.APP_CONFIG.csrfToken);
    formData.append('analytics_enabled', document.getElementById('analyticsEnabled').checked ? 1 : 0);
    const logoFile = logoDropzone.getAcceptedFiles()[0];
    if (logoFile) {
        formData.append('logo', logoFile, logoFile.name);
    }
    const faviconFile = faviconDropzone.getAcceptedFiles()[0];
    if (faviconFile) {
        formData.append('favicon', faviconFile, faviconFile.name);
    }
    try {
        const response = await fetch('<?= BASE_URL ?>/api/admin.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Ayarlar kaydedilemedi');
        }
        Swal.fire({ icon: 'success', title: 'Kaydedildi', text: data.message });
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
    }
}
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
