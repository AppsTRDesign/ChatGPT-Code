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
        const formatBytes = (bytes) => {
            if (!Number.isFinite(bytes) || bytes <= 0) {
                return '0 B';
            }
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
            const value = bytes / Math.pow(1024, index);
            return `${value.toFixed(value >= 10 || index === 0 ? 0 : 2)} ${units[index]}`;
        };

        document.querySelectorAll('[data-dropzone]').forEach(form => {
            const uploadUrl = form.getAttribute('action') || form.dataset.uploadUrl || `${baseUrl}/api/upload.php`;
            const maxFilesizeMB = parseFloat(form.dataset.maxFilesize || '50');
            const parallelUploads = parseInt(form.dataset.parallelUploads || '1', 10) || 1;
            const accepted = form.dataset.accepted || null;
            const previewTemplateSelector = form.dataset.previewTemplate || '';
            const previewsContainerSelector = form.dataset.previewsContainer || '';
            const templateEl = previewTemplateSelector ? document.querySelector(previewTemplateSelector) : null;
            const previewTemplate = templateEl ? templateEl.innerHTML.trim() : undefined;
            const previewsContainer = previewsContainerSelector ? form.closest('section, .card, .modal, body').querySelector(previewsContainerSelector) : null;
            const parentScope = form.closest('section, .card, .modal, .card-body, .card-glass') || form.parentElement;
            const startButton = parentScope?.querySelector('[data-upload-start]');
            const clearButton = parentScope?.querySelector('[data-upload-clear]');

            let successfulUploads = 0;
            let failedUploads = 0;

            const dz = new Dropzone(form, {
                url: uploadUrl,
                paramName: 'file',
                maxFilesize: maxFilesizeMB,
                parallelUploads,
                addRemoveLinks: false,
                timeout: 0,
                acceptedFiles: accepted,
                autoProcessQueue: false,
                previewsContainer: previewsContainer || undefined,
                previewTemplate: previewTemplate,
                headers: { 'X-CSRF-TOKEN': csrfToken },
                init: function () {
                    const dropzoneInstance = this;

                    const resetCounters = () => {
                        successfulUploads = 0;
                        failedUploads = 0;
                    };

                    const updateButtons = () => {
                        const hasQueued = dropzoneInstance.getFilesWithStatus(Dropzone.ADDED).length > 0;
                        const isUploading = dropzoneInstance.getActiveFiles().length > 0;
                        if (startButton) {
                            startButton.disabled = !hasQueued || isUploading;
                        }
                        if (clearButton) {
                            clearButton.disabled = !hasQueued && dropzoneInstance.files.length === 0;
                        }
                    };

                    this.on('addedfile', function (file) {
                        const preview = file.previewElement;
                        if (preview) {
                            const nameEl = preview.querySelector('[data-upload-name]');
                            const metaEl = preview.querySelector('[data-upload-meta]');
                            const statusEl = preview.querySelector('[data-upload-status]');
                            if (nameEl) {
                                nameEl.textContent = file.name;
                            }
                            if (metaEl) {
                                const parts = [];
                                if (file.type) {
                                    parts.push(file.type);
                                }
                                parts.push(formatBytes(file.size));
                                metaEl.textContent = parts.join(' • ');
                            }
                            if (statusEl) {
                                statusEl.textContent = 'Bekliyor';
                            }
                            const removeBtn = preview.querySelector('[data-upload-remove]');
                            if (removeBtn) {
                                removeBtn.addEventListener('click', (event) => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    if (file.status === Dropzone.UPLOADING || file.status === Dropzone.QUEUED) {
                                        dropzoneInstance.cancelUpload(file);
                                    }
                                    dropzoneInstance.removeFile(file);
                                    updateButtons();
                                });
                            }
                        }
                        updateButtons();
                    });

                    this.on('removedfile', function () {
                        updateButtons();
                    });

                    this.on('sending', function (_file, _xhr, formData) {
                        formData.append('csrf_token', csrfToken);
                        if (form.dataset.folderId) {
                            formData.append('folder_id', form.dataset.folderId);
                        }
                    });

                    this.on('processing', function () {
                        if (startButton) {
                            startButton.disabled = true;
                        }
                    });

                    this.on('uploadprogress', function (file, progress, bytesSent) {
                        const preview = file.previewElement;
                        if (!preview) {
                            return;
                        }
                        const progressEl = preview.querySelector('[data-upload-progress]');
                        const statusEl = preview.querySelector('[data-upload-status]');
                        if (progressEl) {
                            progressEl.style.width = `${progress}%`;
                        }
                        if (statusEl) {
                            const totalBytes = file.upload?.total || file.size || 0;
                            statusEl.textContent = `${formatBytes(bytesSent)} / ${formatBytes(totalBytes)}`;
                        }
                    });

                    this.on('success', function (file, response) {
                        successfulUploads += 1;
                        const preview = file.previewElement;
                        if (preview) {
                            const statusEl = preview.querySelector('[data-upload-status]');
                            if (statusEl) {
                                statusEl.textContent = response.message || 'Yüklendi';
                            }
                        }
                        if (response.status === 'success') {
                            document.dispatchEvent(new CustomEvent('upload:completed', { detail: response }));
                        }
                        updateButtons();
                    });

                    this.on('error', function (file, errorMessage, xhr) {
                        failedUploads += 1;
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
                        const preview = file.previewElement;
                        if (preview) {
                            const statusEl = preview.querySelector('[data-upload-status]');
                            if (statusEl) {
                                statusEl.textContent = message;
                            }
                        }
                        showAlert('error', message);
                        updateButtons();
                    });

                    this.on('canceled', function (file) {
                        const preview = file.previewElement;
                        if (preview) {
                            const statusEl = preview.querySelector('[data-upload-status]');
                            if (statusEl) {
                                statusEl.textContent = 'İptal edildi';
                            }
                        }
                    });

                    this.on('queuecomplete', function () {
                        if (startButton) {
                            startButton.disabled = false;
                        }
                        if (clearButton) {
                            clearButton.disabled = false;
                        }
                        if (successfulUploads > 0) {
                            const detail = failedUploads > 0
                                ? `${successfulUploads} dosya yüklendi, ${failedUploads} dosya yüklenemedi.`
                                : `${successfulUploads} dosya başarıyla yüklendi.`;
                            showAlert('success', detail);
                        }
                        resetCounters();
                    });

                    if (startButton) {
                        startButton.addEventListener('click', () => {
                            resetCounters();
                            const queued = dropzoneInstance.getFilesWithStatus(Dropzone.ADDED);
                            if (!queued.length) {
                                return;
                            }
                            dropzoneInstance.processQueue();
                        });
                    }

                    if (clearButton) {
                        clearButton.addEventListener('click', () => {
                            dropzoneInstance.removeAllFiles(true);
                            resetCounters();
                            updateButtons();
                        });
                    }

                    updateButtons();
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
