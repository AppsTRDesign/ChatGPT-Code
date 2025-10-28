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
        formData.append('analytics_enabled', document.getElementById('analyticsEnabled')?.checked ? 1 : 0);
        formData.append('public_sharing_enabled', document.getElementById('publicSharingEnabled')?.checked ? 1 : 0);
        formData.append('folder_passwords_enabled', document.getElementById('folderPasswordsEnabled')?.checked ? 1 : 0);
        formData.append('share_download_delay', form.share_download_delay?.value || 0);

        const logoFile = zones.logo?.getAcceptedFiles()?.[0];
        if (logoFile) {
            formData.append('logo', logoFile, logoFile.name);
        }
        const faviconFile = zones.favicon?.getAcceptedFiles()?.[0];
        if (faviconFile) {
            formData.append('favicon', faviconFile, faviconFile.name);
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
            favicon: initDropzone('#faviconDropzone')
        };
        window.saveSettings = () => saveSettings(zones);
    });
})();
