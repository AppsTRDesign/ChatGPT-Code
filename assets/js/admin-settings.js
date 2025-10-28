(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};

    function initDropzone(selector) {
        const element = document.querySelector(selector);
        if (!element || typeof Dropzone === 'undefined') {
            return null;
        }
        return new Dropzone(element, {
            url: '#',
            autoProcessQueue: false,
            maxFiles: 1,
            acceptedFiles: 'image/*',
            addRemoveLinks: true,
            dictRemoveFile: 'Kaldır',
            init() {
                this.on('maxfilesexceeded', function (file) {
                    this.removeAllFiles();
                    this.addFile(file);
                });
            }
        });
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

        const logoFile = zones.logo?.getAcceptedFiles()?.[0];
        if (logoFile) {
            formData.append('logo', logoFile, logoFile.name);
        }
        const faviconFile = zones.favicon?.getAcceptedFiles()?.[0];
        if (faviconFile) {
            formData.append('favicon', faviconFile, faviconFile.name);
        }
        const bannerFile = zones.banner?.getAcceptedFiles()?.[0];
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
