<?php
/** @var array $settings */
/** @var array $allowedExtensions */
$logoFile = $settings['branding_logo'] ?? '';
$faviconFile = $settings['branding_favicon'] ?? '';
$logoUrl = $logoFile ? asset('storage/uploads/' . $logoFile) : null;
$faviconUrl = $faviconFile ? asset('storage/uploads/' . $faviconFile) : null;
$allowedExtensionsValue = $settings['uploads_allowed_extensions'] ?? implode(',', $allowedExtensions);
$allowedExtensionsLabel = $allowedExtensions ? implode(', ', array_map(static fn($ext) => strtoupper($ext), $allowedExtensions)) : 'Belirtilmedi';
$csrf = csrf_token();
?>
<?php ob_start(); ?>
<div class="card glass border-0">
    <div class="card-body">
        <form data-ajax="true" action="/admin/settings" method="post" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?= $csrf ?>">
            <div class="row g-4">
                <div class="col-md-6">
                    <h5 class="text-secondary">Telegram API</h5>
                    <div class="mb-3">
                        <label class="form-label">API ID</label>
                        <input type="text" name="telegram_api_id" value="<?= htmlspecialchars($settings['telegram_api_id'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API Hash</label>
                        <input type="text" name="telegram_api_hash" value="<?= htmlspecialchars($settings['telegram_api_hash'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Global Rate Limit</label>
                        <input type="number" name="rate_limit_global" value="<?= htmlspecialchars($settings['rate_limit_global'] ?? '60') ?>" class="form-control">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Telefon Başına</label>
                            <input type="number" name="rate_limit_per_phone" value="<?= htmlspecialchars($settings['rate_limit_per_phone'] ?? '30') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kanal Başına</label>
                            <input type="number" name="rate_limit_per_channel" value="<?= htmlspecialchars($settings['rate_limit_per_channel'] ?? '15') ?>" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 class="text-secondary">Mail Ayarları</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Sunucu</label>
                            <input type="text" name="mail_host" value="<?= htmlspecialchars($settings['mail_host'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Port</label>
                            <input type="text" name="mail_port" value="<?= htmlspecialchars($settings['mail_port'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" name="mail_username" value="<?= htmlspecialchars($settings['mail_username'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Parola</label>
                            <input type="password" name="mail_password" value="<?= htmlspecialchars($settings['mail_password'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Şifreleme</label>
                            <input type="text" name="mail_encryption" value="<?= htmlspecialchars($settings['mail_encryption'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gönderici Adresi</label>
                            <input type="email" name="mail_from_address" value="<?= htmlspecialchars($settings['mail_from_address'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gönderici Adı</label>
                            <input type="text" name="mail_from_name" value="<?= htmlspecialchars($settings['mail_from_name'] ?? '') ?>" class="form-control">
                        </div>
                    </div>
                    <div class="mt-4">
                        <h5 class="text-secondary">Marka Ayarları</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="brand-card card border-0 text-center p-3" id="logoPreview">
                                    <?php if ($logoUrl): ?>
                                        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" class="img-fluid">
                                        <p class="small text-muted mt-2">Aktif logo</p>
                                    <?php else: ?>
                                        <div class="text-muted small">Logo henüz yüklenmedi.</div>
                                    <?php endif; ?>
                                </div>
                                <form action="/admin/settings/branding/logo/upload" method="post" class="dropzone brand-dropzone mt-2" id="logoDropzone" data-remove-url="/admin/settings/branding/logo/remove" data-preview-target="#logoPreview" data-token="<?= $csrf ?>" data-existing-name="<?= htmlspecialchars($logoFile) ?>" data-existing-url="<?= htmlspecialchars($logoUrl ?? '') ?>" data-remove-label="Logoyu Kaldır">
                                    <input type="hidden" name="_token" value="<?= $csrf ?>">
                                    <div class="dz-message small text-muted">Logo yüklemek için tıklayın veya sürükleyin.</div>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <div class="brand-card card border-0 text-center p-3" id="faviconPreview">
                                    <?php if ($faviconUrl): ?>
                                        <img src="<?= htmlspecialchars($faviconUrl) ?>" alt="Favicon" class="img-fluid">
                                        <p class="small text-muted mt-2">Aktif favicon</p>
                                    <?php else: ?>
                                        <div class="text-muted small">Favicon henüz yüklenmedi.</div>
                                    <?php endif; ?>
                                </div>
                                <form action="/admin/settings/branding/favicon/upload" method="post" class="dropzone brand-dropzone mt-2" id="faviconDropzone" data-remove-url="/admin/settings/branding/favicon/remove" data-preview-target="#faviconPreview" data-token="<?= $csrf ?>" data-existing-name="<?= htmlspecialchars($faviconFile) ?>" data-existing-url="<?= htmlspecialchars($faviconUrl ?? '') ?>" data-remove-label="Faviconu Kaldır">
                                    <input type="hidden" name="_token" value="<?= $csrf ?>">
                                    <div class="dz-message small text-muted">Favicon yüklemek için tıklayın veya sürükleyin.</div>
                                </form>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">Desteklenen uzantılar: <?= htmlspecialchars($allowedExtensionsLabel) ?></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 class="text-secondary">Sunucu Kontrolü</h5>
                    <div class="mb-3">
                        <label class="form-label">Sunucu Adresi / IP</label>
                        <input type="text" name="remote_host" value="<?= htmlspecialchars($settings['remote_host'] ?? '') ?>" class="form-control" placeholder="123.45.67.89">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SSH Portu</label>
                        <input type="number" name="remote_port" value="<?= htmlspecialchars($settings['remote_port'] ?? '22') ?>" class="form-control" placeholder="22">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" name="remote_username" value="<?= htmlspecialchars($settings['remote_username'] ?? '') ?>" class="form-control" placeholder="root">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parola</label>
                        <input type="password" name="remote_password" value="<?= htmlspecialchars($settings['remote_password'] ?? '') ?>" class="form-control" placeholder="********">
                    </div>
                    <p class="small text-muted">Bu bilgiler, servisleri phpseclib üzerinden uzaktan başlatıp durdurmak için kullanılır.</p>
                </div>
                <div class="col-md-6">
                    <h5 class="text-secondary">Dosya Yükleme Ayarları</h5>
                    <div class="mb-3">
                        <label class="form-label">İzin Verilen Dosya Uzantıları</label>
                        <textarea name="uploads_allowed_extensions" class="form-control" rows="3" placeholder="zip,rar,pdf,doc,docx"><?= htmlspecialchars($allowedExtensionsValue) ?></textarea>
                        <small class="text-muted">Virgül veya satır ile ayırın. Bu uzantılar mesaj şablonları ve diğer yüklemelerde kullanılacaktır.</small>
                    </div>
                </div>
            </div>
            <div class="mt-4 text-end">
                <button class="btn btn-primary" type="submit">Ayarları Kaydet</button>
            </div>
        </form>
    </div>
</div>
<style>
    .brand-card {
        background: rgba(15, 23, 42, 0.35);
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
    }
    .brand-card img {
        max-width: 100%;
        max-height: 120px;
        object-fit: contain;
    }
    .brand-dropzone {
        border: 2px dashed rgba(148, 163, 184, 0.35);
        background: rgba(15, 23, 42, 0.45);
        border-radius: 14px;
        padding: 1.25rem;
        text-align: center;
        color: rgba(148, 163, 184, 0.8);
    }
    .brand-dropzone.dz-drag-hover { border-color: #38bdf8; color: #38bdf8; }
    .brand-dropzone .dz-message { margin: 0; }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (!window.Dropzone) {
        return;
    }

    const initBrandDropzone = (selector) => {
        const form = document.querySelector(selector);
        if (!form) {
            return;
        }

        const previewTarget = form.dataset.previewTarget;
        const removeUrl = form.dataset.removeUrl;
        const token = form.dataset.token;
        const existingName = form.dataset.existingName;
        const existingUrl = form.dataset.existingUrl;

        const removeLabel = form.dataset.removeLabel || 'Kaldır';
        const dz = new Dropzone(form, {
            paramName: 'file',
            maxFiles: 1,
            acceptedFiles: '.svg,.png,.jpg,.jpeg,.webp,.ico',
            addRemoveLinks: true,
            dictRemoveFile: 'Kaldır',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        const updatePreview = (url, emptyMessage) => {
            const preview = document.querySelector(previewTarget);
            if (!preview) {
                return;
            }
            preview.innerHTML = '';
            if (url) {
                const img = document.createElement('img');
                img.src = url;
                img.alt = 'Branding';
                preview.appendChild(img);
                const meta = document.createElement('p');
                meta.className = 'small text-muted mt-2';
                meta.textContent = 'Güncel dosya';
                preview.appendChild(meta);
            } else {
                const span = document.createElement('div');
                span.className = 'text-muted small';
                span.textContent = emptyMessage;
                preview.appendChild(span);
            }
        };

        const ensureRemoveButton = (hasFile) => {
            if (!removeUrl) {
                return;
            }
            const container = form.parentElement;
            if (!container) {
                return;
            }
            let buttonForm = container.querySelector(`form[data-remove-button="${form.id}"]`);
            if (hasFile) {
                if (!buttonForm) {
                    buttonForm = document.createElement('form');
                    buttonForm.dataset.ajax = 'true';
                    buttonForm.dataset.removeButton = form.id;
                    buttonForm.method = 'post';
                    buttonForm.action = removeUrl;
                    buttonForm.className = 'mt-2';

                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = '_token';
                    tokenInput.value = token;
                    buttonForm.appendChild(tokenInput);

                    const button = document.createElement('button');
                    button.type = 'submit';
                    button.className = 'btn btn-outline-danger btn-sm w-100';
                    button.textContent = removeLabel;
                    buttonForm.appendChild(button);

                    container.appendChild(buttonForm);
                }
            } else if (buttonForm) {
                buttonForm.remove();
            }
        };

        dz.on('success', (file, response) => {
            if (response?.preview) {
                updatePreview(response.preview, 'Dosya henüz yüklenmedi.');
            }
            if (response?.message) {
                showToast('success', response.message);
            }
            file.uploadedFilename = response?.filename || null;
            ensureRemoveButton(true);
        });

        dz.on('error', (file, errorMessage) => {
            const message = typeof errorMessage === 'string' ? errorMessage : (errorMessage?.message || 'Yükleme başarısız.');
            showToast('danger', message);
        });

        dz.on('removedfile', (file) => {
            if (file.skipRemoveRequest) {
                return;
            }
            if (!removeUrl || !(file.uploadedFilename || file.existing)) {
                return;
            }
            const formData = new FormData();
            formData.append('_token', token);
            fetch(removeUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(res => res.json().catch(() => null))
            .then(payload => {
                if (payload?.status === 'success') {
                    updatePreview(null, 'Dosya henüz yüklenmedi.');
                    showToast('success', payload.message || 'Dosya kaldırıldı.');
                    ensureRemoveButton(false);
                } else if (payload?.message) {
                    showToast('danger', payload.message);
                }
            })
            .catch(() => showToast('danger', 'Dosya kaldırılamadı.'));
        });

        if (existingUrl) {
            const mockFile = { name: existingName || 'dosya', size: 0, existing: true, status: Dropzone.SUCCESS };
            dz.emit('addedfile', mockFile);
            dz.emit('thumbnail', mockFile, existingUrl);
            dz.emit('complete', mockFile);
            if (mockFile.previewElement) {
                mockFile.previewElement.classList.add('dz-success', 'dz-complete');
            }
            ensureRemoveButton(true);
        }

        dz.on('addedfile', () => {
            while (dz.files.length > 1) {
                const removed = dz.files[0];
                removed.skipRemoveRequest = true;
                dz.removeFile(removed);
            }
        });
    };

    initBrandDropzone('#logoDropzone');
    initBrandDropzone('#faviconDropzone');
});
</script>
<?php $content = ob_get_clean(); include resource_path('views/layouts/app.php');
