(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const baseUrl = appConfig.baseUrl || '';
    const csrfToken = appConfig.csrfToken || '';

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
            const endpoint = form.getAttribute('action');
            try {
                if (!endpoint) {
                    throw new Error('Form eylem adresi tanımlı değil.');
                }
                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (response.ok && data.status === 'success') {
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

    if (typeof Dropzone !== 'undefined') {
        document.querySelectorAll('[data-dropzone]').forEach(form => {
            const uploadUrl = form.getAttribute('action') || form.dataset.uploadUrl || `${baseUrl}/api/upload.php`;
            const maxFilesizeMB = parseFloat(form.dataset.maxFilesize || '50');
            const parallelUploads = parseInt(form.dataset.parallelUploads || '1', 10) || 1;
            const accepted = form.dataset.accepted || null;
            const dz = new Dropzone(form, {
                url: uploadUrl,
                paramName: 'file',
                maxFilesize: maxFilesizeMB,
                parallelUploads,
                addRemoveLinks: false,
                timeout: 0,
                acceptedFiles: accepted,
                headers: { 'X-CSRF-TOKEN': csrfToken },
                init: function () {
                    this.on('sending', function (_file, _xhr, formData) {
                        formData.append('csrf_token', csrfToken);
                        if (form.dataset.folderId) {
                            formData.append('folder_id', form.dataset.folderId);
                        }
                    });
                    this.on('success', function (file, response) {
                        if (response.status === 'success') {
                            showAlert('success', response.message || 'Dosya yüklendi.');
                            document.dispatchEvent(new CustomEvent('upload:completed', { detail: response }));
                        } else {
                            showAlert('error', response.message || 'Dosya yüklenirken hata oluştu.');
                        }
                    });
                    this.on('error', function (_file, errorMessage, xhr) {
                        let message = 'Yükleme başarısız.';
                        if (xhr) {
                            if (xhr.status === 401) {
                                message = 'Dosya yüklemek için giriş yapmanız gerekiyor.';
                            } else if (xhr.responseText) {
                                try {
                                    const parsed = JSON.parse(xhr.responseText);
                                    if (parsed && parsed.message) {
                                        message = parsed.message;
                                    }
                                } catch (parseError) {
                                    console.warn(parseError);
                                }
                            }
                        }
                        if (typeof errorMessage === 'string') {
                            message = errorMessage;
                        } else if (errorMessage && errorMessage.message) {
                            message = errorMessage.message;
                        }
                        showAlert('error', message);
                    });
                }
            });

            form.dropzone = dz;

            form.addEventListener('set-folder', event => {
                const folderId = event.detail?.folderId ?? '';
                form.dataset.folderId = folderId;
                if (event.detail?.parallelUploads && form.dropzone) {
                    form.dropzone.options.parallelUploads = event.detail.parallelUploads;
                }
                if (event.detail?.acceptedFiles && form.dropzone) {
                    form.dropzone.options.acceptedFiles = event.detail.acceptedFiles.join(',');
                }
            });
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
