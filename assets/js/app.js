(function () {
    'use strict';

    const { baseUrl, csrfToken } = window.APP_CONFIG || {};

    function showAlert(type, message) {
        Swal.fire({
            icon: type,
            title: type === 'success' ? 'Başarılı' : 'Hata',
            text: message,
            confirmButtonColor: '#6941c6'
        });
    }

    document.querySelectorAll('[data-ajax-form]').forEach(form => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitBtn = form.querySelector('[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            const formData = new FormData(form);
            formData.append('csrf_token', csrfToken);
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (data.status === 'success') {
                    showAlert('success', data.message || 'İşlem tamamlandı.');
                    if (data.redirect) {
                        setTimeout(() => window.location.href = data.redirect, 1200);
                    }
                } else {
                    throw new Error(data.message || 'Bir hata oluştu.');
                }
            } catch (error) {
                showAlert('error', error.message);
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            }
        });
    });

    if (typeof Dropzone !== 'undefined') {
        Dropzone.autoDiscover = false;
    }

    const uploadZone = document.querySelector('#uploadZone');
    if (uploadZone && typeof Dropzone !== 'undefined') {
        const maxFilesizeMB = 50;
        new Dropzone('#uploadZone', {
            url: `${baseUrl}/api/upload.php`,
            paramName: 'file',
            maxFilesize: maxFilesizeMB,
            parallelUploads: 1,
            addRemoveLinks: false,
            timeout: 0,
            acceptedFiles: null,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            init: function () {
                this.on('sending', function (file, xhr, formData) {
                    formData.append('csrf_token', csrfToken);
                });
                this.on('success', function (file, response) {
                    if (response.status === 'success') {
                        showAlert('success', response.message || 'Dosya yüklendi.');
                        if (response.fileUrl) {
                            const link = document.createElement('a');
                            link.href = response.fileUrl;
                            link.target = '_blank';
                            link.textContent = 'Dosyayı görüntüle';
                            link.className = 'd-block mt-2 text-decoration-none link-light';
                            file.previewElement.appendChild(link);
                        }
                        document.dispatchEvent(new CustomEvent('upload:completed', { detail: response }));
                    } else {
                        showAlert('error', response.message || 'Dosya yüklenirken hata oluştu.');
                    }
                });
                this.on('error', function (_file, errorMessage) {
                    const message = typeof errorMessage === 'string' ? errorMessage : (errorMessage.message || 'Yükleme başarısız.');
                    showAlert('error', message);
                });
            }
        });
    }

    document.querySelectorAll('[data-delete-url]').forEach(button => {
        button.addEventListener('click', async () => {
            const url = button.getAttribute('data-delete-url');
            const confirmResult = await Swal.fire({
                icon: 'warning',
                title: 'Emin misiniz?',
                text: 'Bu işlem geri alınamaz.',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Evet, sil',
                cancelButtonText: 'İptal'
            });
            if (!confirmResult.isConfirmed) {
                return;
            }
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ csrf_token: csrfToken })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    showAlert('success', data.message || 'Silindi');
                    if (data.removeSelector) {
                        document.querySelectorAll(data.removeSelector).forEach(el => el.remove());
                    }
                    if (data.redirect) {
                        setTimeout(() => window.location.href = data.redirect, 900);
                    }
                } else {
                    throw new Error(data.message || 'Silme başarısız.');
                }
            } catch (error) {
                showAlert('error', error.message);
            }
        });
    });
})();
