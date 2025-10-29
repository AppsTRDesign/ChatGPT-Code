(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const activeStatuses = new Set(['added', 'queued', 'uploading', 'success']);

    function findSelectedFile(zone) {
        if (!zone || !Array.isArray(zone.files)) {
            return null;
        }
        return zone.files.find((file) => {
            if (!(file instanceof File)) {
                return false;
            }
            const status = (file.status || '').toLowerCase();
            return activeStatuses.has(status);
        }) || null;
    }

    function renderImagePreview(target, file, options) {
        if (!target) {
            return;
        }
        target.innerHTML = '';

        const isImage = typeof file.type === 'string' && file.type.startsWith('image/');
        if (isImage) {
            const img = document.createElement('img');
            img.alt = file.name || 'Seçilen dosya';
            img.className = options.classes;
            if (typeof options.width === 'number') {
                img.width = options.width;
            }
            if (typeof options.height === 'number') {
                img.height = options.height;
            }
            const reader = new FileReader();
            reader.addEventListener('load', (event) => {
                img.src = event.target?.result || '';
            });
            reader.readAsDataURL(file);
            target.appendChild(img);
        }

        const caption = document.createElement('div');
        caption.className = 'text-white-50 small mt-2';
        caption.textContent = file.name || 'Seçilen dosya';
        target.appendChild(caption);
    }

    function restoreInitialPreview(target, initialHTML, emptyText) {
        if (!target) {
            return;
        }
        if (initialHTML && initialHTML.trim().length > 0) {
            target.innerHTML = initialHTML;
        } else {
            target.innerHTML = '';
            const placeholder = document.createElement('span');
            placeholder.className = 'text-white-50 small d-block';
            placeholder.textContent = emptyText || 'Henüz dosya seçilmedi.';
            target.appendChild(placeholder);
        }
    }

    function initDropzone(selector) {
        const element = document.querySelector(selector);
        if (!element || typeof Dropzone === 'undefined') {
            return null;
        }

        const existingUrl = element.dataset.existingUrl || '';
        const existingName = element.dataset.existingName || 'Mevcut dosya';
        const previewSelector = element.dataset.previewTarget || '';
        const previewTarget = previewSelector ? document.querySelector(previewSelector) : null;
        const emptyText = element.dataset.emptyText || 'Henüz dosya seçilmedi.';
        const initialPreviewHTML = previewTarget ? previewTarget.innerHTML : '';

        const isBannerZone = element.id === 'bannerDropzone';
        const isFaviconZone = element.id === 'faviconDropzone';
        const previewOptions = {
            classes: isBannerZone
                ? 'img-fluid rounded border border-light border-opacity-25'
                : 'img-thumbnail bg-white',
            width: isBannerZone ? null : (isFaviconZone ? 48 : 96),
            height: isBannerZone ? null : (isFaviconZone ? 48 : 96)
        };

        if (previewTarget && !initialPreviewHTML.trim() && existingUrl) {
            const existingImg = document.createElement('img');
            existingImg.src = existingUrl;
            existingImg.alt = existingName;
            existingImg.className = previewOptions.classes;
            if (typeof previewOptions.width === 'number') {
                existingImg.width = previewOptions.width;
            }
            if (typeof previewOptions.height === 'number') {
                existingImg.height = previewOptions.height;
            }
            previewTarget.appendChild(existingImg);
        }

        const zone = new Dropzone(element, {
            url: '#',
            autoProcessQueue: false,
            acceptedFiles: 'image/*',
            addRemoveLinks: false,
            dictRemoveFile: 'Kaldır',
            init() {
                this.on('addedfile', (file) => {
                    this.files
                        .filter((queued) => queued !== file && queued instanceof File)
                        .forEach((queued) => this.removeFile(queued));

                    if (file instanceof File) {
                        renderImagePreview(previewTarget, file, previewOptions);
                    }
                });

                this.on('removedfile', (file) => {
                    if (!(file instanceof File)) {
                        return;
                    }
                    const stillHasFile = this.files.some((queued) => queued instanceof File && queued !== file);
                    if (!stillHasFile) {
                        restoreInitialPreview(previewTarget, initialPreviewHTML, emptyText);
                    }
                });
            }
        });

        return zone;
    }

    function gatherFormData(form, zones) {
        const formData = new FormData(form);
        formData.append('action', 'update-settings');
        formData.append('csrf_token', appConfig.csrfToken);

        const toggles = {
            analytics_enabled: document.getElementById('analyticsEnabled'),
            share_stats_enabled: document.getElementById('shareStatsEnabled'),
            public_sharing_enabled: document.getElementById('publicSharingEnabled'),
            folder_passwords_enabled: document.getElementById('folderPasswordsEnabled'),
            share_password_required: document.getElementById('sharePasswordRequired'),
            auto_archive_enabled: document.getElementById('autoArchiveEnabled'),
            auto_delete_enabled: document.getElementById('autoDeleteEnabled'),
            mail_enabled: document.getElementById('mailEnabled'),
            stripe_enabled: document.getElementById('stripeEnabled'),
            iyzico_enabled: document.getElementById('iyzicoEnabled'),
            bank_transfer_enabled: document.getElementById('bankTransferEnabled'),
        };

        Object.entries(toggles).forEach(([key, checkbox]) => {
            if (checkbox) {
                formData.set(key, checkbox.checked ? 1 : 0);
            }
        });

        formData.set('share_download_delay', form.share_download_delay?.value || 0);
        formData.set('archive_after_days', form.archive_after_days?.value || '');
        formData.set('delete_after_days', form.delete_after_days?.value || '');
        formData.set('geoip_database_path', form.geoip_database_path?.value?.trim() || '');

        const logoFile = findSelectedFile(zones.logo);
        if (logoFile) {
            formData.append('logo', logoFile, logoFile.name);
        }

        const faviconFile = findSelectedFile(zones.favicon);
        if (faviconFile) {
            formData.append('favicon', faviconFile, faviconFile.name);
        }

        const bannerFile = findSelectedFile(zones.banner);
        if (bannerFile) {
            formData.append('banner', bannerFile, bannerFile.name);
        }

        return formData;
    }

    async function saveSettings(zones) {
        const form = document.getElementById('settingsForm');
        if (!form) {
            return;
        }

        const formData = gatherFormData(form, zones);

        try {
            const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
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

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof Dropzone !== 'undefined') {
            Dropzone.autoDiscover = false;
        }

        const zones = {
            logo: initDropzone('#logoDropzone'),
            favicon: initDropzone('#faviconDropzone'),
            banner: initDropzone('#bannerDropzone')
        };

        window.saveSettings = () => saveSettings(zones);
    });
})();
