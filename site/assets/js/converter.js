Dropzone.autoDiscover = false;

(function () {
    const activeJobIds = new Set();
    let unloadHooked = false;

    const CATEGORY_RULES = {
        audio: {
            acceptedFiles: 'audio/*',
            mimePrefixes: ['audio/'],
            extensions: ['.mp3', '.wav', '.aac', '.m4a', '.flac', '.ogg', '.opus', '.wma'],
            errorMessage: 'Lütfen sadece ses dosyaları yükleyin.'
        },
        video: {
            acceptedFiles: 'video/*',
            mimePrefixes: ['video/'],
            extensions: ['.mp4', '.mov', '.mkv', '.avi', '.wmv', '.webm', '.mpeg', '.mpg', '.m4v', '.flv', '.3gp'],
            errorMessage: 'Lütfen sadece video dosyaları yükleyin.'
        },
        image: {
            acceptedFiles: 'image/*',
            mimePrefixes: ['image/'],
            extensions: ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.tif', '.tiff', '.svg', '.heic', '.heif'],
            errorMessage: 'Lütfen sadece görsel dosyaları yükleyin.'
        }
    };

    function normalizeList(value) {
        if (!value) {
            return null;
        }
        if (Array.isArray(value)) {
            return value.map((item) => String(item).toLowerCase().trim()).filter(Boolean);
        }
        if (typeof value === 'string') {
            return value
                .split(',')
                .map((item) => item.toLowerCase().trim())
                .filter(Boolean);
        }
        return null;
    }

    function normalizeExtensions(value) {
        const list = normalizeList(value);
        if (!list) {
            return null;
        }
        return list.map((ext) => (ext.startsWith('.') ? ext : `.${ext}`));
    }

    function normalizeMimePrefixes(value) {
        const list = normalizeList(value);
        if (!list) {
            return null;
        }
        return list.map((prefix) => {
            let normalized = prefix;
            if (normalized.endsWith('/*')) {
                normalized = normalized.slice(0, -1);
            }
            if (!normalized.endsWith('/')) {
                normalized = `${normalized}/`;
            }
            return normalized;
        });
    }

    function handleUnload() {
        if (!activeJobIds.size) {
            return;
        }
        activeJobIds.forEach((jobId) => {
            if (!jobId) {
                return;
            }
            const body = new URLSearchParams();
            body.append('job_id', jobId);
            try {
                if (navigator.sendBeacon) {
                    navigator.sendBeacon('/ajax/cancel.php', body);
                } else {
                    fetch('/ajax/cancel.php', {
                        method: 'POST',
                        body
                    });
                }
            } catch (error) {
                console.warn('İş iptali gönderilemedi', error);
            }
        });
        activeJobIds.clear();
    }

    function attachUnloadHook() {
        if (unloadHooked) {
            return;
        }
        window.addEventListener('pagehide', handleUnload);
        window.addEventListener('beforeunload', handleUnload);
        unloadHooked = true;
    }

    function registerActiveJob(jobId) {
        if (!jobId) {
            return;
        }
        activeJobIds.add(jobId);
        attachUnloadHook();
    }

    function unregisterActiveJob(jobId) {
        if (!jobId) {
            return;
        }
        activeJobIds.delete(jobId);
    }

    async function cancelJobOnServer(jobId) {
        if (!jobId) {
            return;
        }
        const body = new URLSearchParams();
        body.append('job_id', jobId);
        try {
            await fetch('/ajax/cancel.php', {
                method: 'POST',
                body
            });
        } catch (error) {
            console.warn('İş iptali başarısız', error);
        }
    }

    function bytesToMB(bytes) {
        return (bytes / (1024 * 1024)).toFixed(2);
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + sizes[i];
    }

    function hasProcessableEntry(resultMap) {
        for (const entry of resultMap.values()) {
            if (!['ready', 'downloading', 'downloaded', 'cancelled'].includes(entry.state)) {
                return true;
            }
        }
        return false;
    }

    function initializeConverter(configInput) {
        const config = configInput || {};
        if (!config.dropzoneId || !config.formId || !config.resultListId || !config.endpoint) {
            console.warn('Eksik dönüştürücü yapılandırması.');
            return;
        }

        const maxFiles = config.maxFiles || 5;
        const maxFileSize = config.maxFileSize || 2048;
        const dropzoneElement = document.getElementById(config.dropzoneId);
        const convertButton = document.getElementById(config.convertButtonId || 'convertButton');
        const resultList = document.getElementById(config.resultListId);
        const formEl = document.getElementById(config.formId);
        const summaryEl = config.fileSummaryId ? document.getElementById(config.fileSummaryId) : null;
        const instructionText = config.instructionText || `Sürükle veya tıklayarak dosya seçin - Maks ${maxFiles} dosya`;

        if (!dropzoneElement || !convertButton || !formEl || !resultList) {
            console.warn('Dönüştürücü için gerekli DOM elemanları bulunamadı.');
            return;
        }

        const categoryKey = (config.fileCategory || config.type || '').toLowerCase();
        const categoryRule = CATEGORY_RULES[categoryKey] || null;
        const allowedExtensions = normalizeExtensions(config.allowedExtensions || (categoryRule ? categoryRule.extensions : null));
        const allowedMimePrefixes = normalizeMimePrefixes(config.allowedMimePrefixes || (categoryRule ? categoryRule.mimePrefixes : null));
        const acceptedFilesSetting = config.acceptedFiles || (categoryRule ? categoryRule.acceptedFiles : null);
        const typeErrorTitle = config.typeErrorTitle || 'Geçersiz dosya';
        const typeErrorMessage = config.typeErrorMessage || (categoryRule ? categoryRule.errorMessage : 'Bu dosya türü desteklenmiyor.');
        const invalidTypePatterns = [/You can't upload files of this type/i, /File type not allowed/i];

        function isFileTypeAllowed(file) {
            if (!allowedExtensions && !allowedMimePrefixes) {
                return true;
            }
            const mime = (file.type || '').toLowerCase();
            if (allowedMimePrefixes && mime) {
                if (allowedMimePrefixes.some((prefix) => mime.startsWith(prefix))) {
                    return true;
                }
            }
            const name = (file.name || '').toLowerCase();
            if (allowedExtensions && name) {
                if (allowedExtensions.some((ext) => name.endsWith(ext))) {
                    return true;
                }
            }
            return false;
        }

        const resultMap = new Map();
        const defaultConvertLabel = (config.convertButtonLabel || convertButton.textContent || 'Dönüştür').trim();
        convertButton.textContent = config.convertButtonLabel || defaultConvertLabel;
        convertButton.disabled = true;
        convertButton.classList.add('disabled');

        let messageEl = dropzoneElement.querySelector('.dz-message');
        if (!messageEl) {
            messageEl = document.createElement('div');
            messageEl.className = 'dz-message';
            dropzoneElement.appendChild(messageEl);
        }

        function setDropzoneMessage(text) {
            if (!messageEl) {
                return;
            }
            messageEl.innerHTML = '';
            const span = document.createElement('span');
            span.textContent = text != null ? String(text) : '';
            messageEl.appendChild(span);
        }

        setDropzoneMessage(instructionText);

        function updateFileIndicators() {
            const count = dropzone.files.length;
            const hasFiles = count > 0;
            const summaryText = hasFiles
                ? `${count} dosya seçildi - Maks ${maxFiles} dosya`
                : instructionText;
            if (messageEl) {
                setDropzoneMessage(hasFiles ? summaryText : instructionText);
            }
            dropzoneElement.classList.toggle('dz-has-files', hasFiles);
            dropzoneElement.classList.remove('dz-started');
            if (summaryEl) {
                if (hasFiles) {
                    summaryEl.textContent = summaryText;
                    summaryEl.classList.remove('hidden');
                } else {
                    summaryEl.textContent = '';
                    summaryEl.classList.add('hidden');
                }
            }
        }

        function toggleConvertButton() {
            if (hasProcessableEntry(resultMap)) {
                convertButton.disabled = false;
                convertButton.classList.remove('disabled');
            } else {
                convertButton.disabled = true;
                convertButton.classList.add('disabled');
            }
        }

        function collectOptions() {
            const disabled = [];
            formEl.querySelectorAll(':disabled').forEach((element) => {
                disabled.push(element);
                element.disabled = false;
            });

            const data = new FormData(formEl);

            disabled.forEach((element) => {
                element.disabled = true;
            });

            const options = {};
            for (const [key, value] of data.entries()) {
                options[key] = value;
            }
            return options;
        }

        function createResultItem(file) {
            const uuid = file.upload.uuid;
            const entry = {
                uuid,
                file,
                jobId: null,
                poller: null,
                xhr: null,
                state: 'queued',
                downloadUrl: null,
                downloadName: file.name,
                ignoreRemoval: false
            };

            const wrapper = document.createElement('div');
            wrapper.className = 'ns-result';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'ns-remove';
            removeBtn.setAttribute('aria-label', 'Dosyayı kaldır');
            removeBtn.innerHTML = '&times;';
            removeBtn.addEventListener('click', () => {
                dropzone.removeFile(file);
            });

            const fileName = document.createElement('div');
            fileName.className = 'ns-file';
            fileName.textContent = file.name;
            fileName.title = file.name;

            const status = document.createElement('div');
            status.className = 'ns-status';
            status.textContent = `Boyut: ${formatBytes(file.size)}`;

            const uploadSection = document.createElement('div');
            uploadSection.className = 'ns-progress-area hidden';
            const uploadProgress = document.createElement('div');
            uploadProgress.className = 'ns-progress';
            const uploadBar = document.createElement('div');
            uploadBar.className = 'ns-progress-bar';
            uploadProgress.appendChild(uploadBar);
            const uploadInfo = document.createElement('div');
            uploadInfo.className = 'ns-progress-info';
            uploadInfo.textContent = 'Yükleme bekleniyor...';
            uploadSection.append(uploadProgress, uploadInfo);

            const convertSection = document.createElement('div');
            convertSection.className = 'ns-progress-area hidden';
            const convertProgress = document.createElement('div');
            convertProgress.className = 'ns-progress';
            const convertBar = document.createElement('div');
            convertBar.className = 'ns-progress-bar';
            convertProgress.appendChild(convertBar);
            const convertInfo = document.createElement('div');
            convertInfo.className = 'ns-progress-info';
            convertInfo.textContent = 'Dönüştürme bekleniyor...';
            convertSection.append(convertProgress, convertInfo);

            const downloadSection = document.createElement('div');
            downloadSection.className = 'ns-progress-area hidden';
            const downloadProgress = document.createElement('div');
            downloadProgress.className = 'ns-progress';
            const downloadBar = document.createElement('div');
            downloadBar.className = 'ns-progress-bar';
            downloadProgress.appendChild(downloadBar);
            const downloadInfo = document.createElement('div');
            downloadInfo.className = 'ns-progress-info';
            downloadInfo.textContent = 'İndirme bekleniyor...';
            downloadSection.append(downloadProgress, downloadInfo);

            const actionWrap = document.createElement('div');
            actionWrap.className = 'ns-actions';
            const downloadBtn = document.createElement('a');
            downloadBtn.className = 'ns-btn ns-btn-primary ns-download disabled';
            downloadBtn.textContent = 'İndir';
            downloadBtn.href = '#';
            downloadBtn.setAttribute('role', 'button');
            downloadBtn.addEventListener('click', (event) => {
                event.preventDefault();
                startDownload(entry);
            });
            actionWrap.appendChild(downloadBtn);

            wrapper.append(removeBtn, fileName, status, uploadSection, convertSection, downloadSection, actionWrap);
            resultList.appendChild(wrapper);

            Object.assign(entry, {
                element: wrapper,
                status,
                uploadSection,
                uploadBar,
                uploadInfo,
                convertSection,
                convertBar,
                convertInfo,
                downloadSection,
                downloadBar,
                downloadInfo,
                downloadBtn
            });

            resultMap.set(uuid, entry);
            return entry;
        }

        function removeResultEntry(entry) {
            if (!entry) return;
            if (entry.poller && typeof entry.poller.stop === 'function') {
                entry.poller.stop();
            }
            unregisterActiveJob(entry.jobId);
            resultMap.delete(entry.uuid);
            if (entry.element && entry.element.parentNode) {
                entry.element.parentNode.removeChild(entry.element);
            }
        }

        function stopPolling(entry) {
            if (entry && entry.poller && typeof entry.poller.stop === 'function') {
                entry.poller.stop();
            }
            entry.poller = null;
        }

        function updateStatus(entry, payload) {
            if (!entry) {
                return;
            }
            if (payload.uploadProgress != null) {
                entry.uploadSection.classList.remove('hidden');
                entry.uploadBar.style.width = `${payload.uploadProgress}%`;
            }
            if (payload.uploadInfo) {
                entry.uploadSection.classList.remove('hidden');
                entry.uploadInfo.textContent = payload.uploadInfo;
            }
            if (payload.convertProgress != null) {
                entry.convertSection.classList.remove('hidden');
                entry.convertBar.style.width = `${payload.convertProgress}%`;
            }
            if (payload.convertInfo) {
                entry.convertSection.classList.remove('hidden');
                entry.convertInfo.textContent = payload.convertInfo;
            }
            if (payload.downloadProgress != null) {
                entry.downloadSection.classList.remove('hidden');
                entry.downloadBar.style.width = `${payload.downloadProgress}%`;
            }
            if (payload.downloadInfo) {
                entry.downloadSection.classList.remove('hidden');
                entry.downloadInfo.textContent = payload.downloadInfo;
            }
            if (payload.statusText) {
                entry.status.textContent = payload.statusText;
            }
            if (payload.downloadUrl) {
                entry.downloadUrl = payload.downloadUrl;
                entry.downloadBtn.classList.remove('disabled');
                entry.downloadBtn.style.display = 'inline-flex';
                entry.element.classList.add('ready');
            }
            if (payload.downloadName) {
                entry.downloadName = payload.downloadName;
            }
            if (payload.state) {
                entry.state = payload.state;
            }
        }

        function createPoller(entry) {
            let pollTimer = null;
            const jobId = entry.jobId;
            return {
                start() {
                    if (pollTimer || !jobId) {
                        return;
                    }
                    pollTimer = setInterval(async () => {
                        try {
                            const response = await fetch(`/ajax/status.php?job_id=${encodeURIComponent(jobId)}`);
                            if (!response.ok) {
                                return;
                            }
                            const data = await response.json();
                            if (!data) {
                                return;
                            }
                            if (typeof data.upload_percent === 'number') {
                                updateStatus(entry, {
                                    uploadProgress: data.upload_percent,
                                    uploadInfo: data.upload_text || '',
                                    statusText: data.status || ''
                                });
                            }
                            if (typeof data.convert_percent === 'number') {
                                updateStatus(entry, {
                                    convertProgress: data.convert_percent,
                                    convertInfo: data.convert_text || '',
                                    statusText: data.status || ''
                                });
                            }
                            if (data.download_url && !entry.downloadUrl) {
                                updateStatus(entry, {
                                    downloadUrl: data.download_url,
                                    downloadName: data.download_file || entry.downloadName,
                                    statusText: data.status || 'Hazır',
                                    state: 'ready'
                                });
                                unregisterActiveJob(jobId);
                                stopPolling(entry);
                            }
                            if (data.status === 'completed') {
                                updateStatus(entry, {
                                    convertProgress: 100,
                                    convertInfo: data.convert_text || 'Dönüştürme tamamlandı.',
                                    statusText: 'Hazır',
                                    state: 'ready'
                                });
                                unregisterActiveJob(jobId);
                                stopPolling(entry);
                            }
                            if (data.status === 'cancelled') {
                                updateStatus(entry, {
                                    statusText: data.message || 'İş iptal edildi.',
                                    convertInfo: data.message || 'İş iptal edildi.',
                                    state: 'cancelled'
                                });
                                unregisterActiveJob(jobId);
                                stopPolling(entry);
                            }
                            if (data.status === 'finalized') {
                                unregisterActiveJob(jobId);
                                stopPolling(entry);
                            }
                            if (data.status === 'error') {
                                updateStatus(entry, {
                                    statusText: data.message || 'Hata oluştu',
                                    convertInfo: data.message || 'Hata oluştu',
                                    state: 'error'
                                });
                                unregisterActiveJob(jobId);
                                stopPolling(entry);
                                Swal.fire('Hata', data.message || 'İşlem sırasında bir hata oluştu.', 'error');
                            }
                        } catch (error) {
                            console.error(error);
                        }
                    }, 1000);
                },
                stop() {
                    if (pollTimer) {
                        clearInterval(pollTimer);
                        pollTimer = null;
                    }
                }
            };
        }

        async function startDownload(entry) {
            if (!entry || entry.state !== 'ready' || !entry.downloadUrl || entry.state === 'downloading') {
                if (!entry || !entry.downloadUrl) {
                    Swal.fire('Hazır değil', 'Dosya henüz indirilmeye hazır değil.', 'info');
                }
                return;
            }
            entry.state = 'downloading';
            entry.downloadBtn.classList.add('disabled');
            entry.downloadSection.classList.remove('hidden');
            entry.downloadBar.style.width = '0%';
            entry.downloadInfo.textContent = 'İndirme başlatılıyor...';
            try {
                const response = await fetch(entry.downloadUrl);
                if (!response.ok || !response.body) {
                    throw new Error('İndirme başlatılamadı.');
                }
                const contentLength = Number(response.headers.get('Content-Length')) || 0;
                const reader = response.body.getReader();
                const chunks = [];
                let received = 0;
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) {
                        break;
                    }
                    if (value) {
                        chunks.push(value);
                        received += value.length;
                        if (contentLength) {
                            const percent = Math.min(100, Math.round((received / contentLength) * 100));
                            updateStatus(entry, {
                                downloadProgress: percent,
                                downloadInfo: `${bytesToMB(received)} MB / ${bytesToMB(contentLength)} MB (${percent}%)`
                            });
                        } else {
                            updateStatus(entry, {
                                downloadInfo: `${formatBytes(received)} indirildi...`
                            });
                        }
                    }
                }
                updateStatus(entry, {
                    downloadProgress: 100,
                    downloadInfo: 'İndirme tamamlandı. Kaydediliyor...',
                    statusText: 'Dosya indiriliyor',
                    state: 'downloaded'
                });

                const blob = new Blob(chunks);
                const blobUrl = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = blobUrl;
                link.download = entry.downloadName || entry.file.name;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(blobUrl);

                Swal.fire('İndirme tamamlandı', `${entry.downloadName || entry.file.name} indirildi.`, 'success');

                entry.ignoreRemoval = true;
                dropzone.removeFile(entry.file);
                removeResultEntry(entry);
                formEl.reset();
                if (typeof config.onReset === 'function') {
                    try {
                        config.onReset(formEl);
                    } catch (resetError) {
                        console.error(resetError);
                    }
                }
                updateFileIndicators();
                toggleConvertButton();
            } catch (error) {
                console.error(error);
                entry.state = 'ready';
                entry.downloadSection.classList.add('hidden');
                entry.downloadBar.style.width = '0%';
                entry.downloadInfo.textContent = 'İndirme bekleniyor...';
                entry.downloadBtn.classList.remove('disabled');
                Swal.fire('İndirme hatası', error.message || 'Dosya indirilemedi.', 'error');
            }
        }

        async function cancelEntry(entry, { silent } = {}) {
            if (!entry) return;
            if (entry.state === 'cancelled') {
                return;
            }
            entry.state = 'cancelled';
            stopPolling(entry);
            if (entry.xhr && entry.xhr.readyState !== XMLHttpRequest.DONE) {
                entry.xhr.abort();
            }
            if (entry.jobId) {
                unregisterActiveJob(entry.jobId);
                await cancelJobOnServer(entry.jobId);
            }
            updateStatus(entry, {
                statusText: 'İş iptal edildi.',
                convertInfo: 'İş iptal edildi.'
            });
            if (!silent) {
                Swal.fire('İptal edildi', `${entry.file.name} işlemi iptal edildi.`, 'info');
            }
            entry.xhr = null;
            entry.jobId = null;
            convertButton.textContent = config.convertButtonLabel || defaultConvertLabel;
            toggleConvertButton();
        }

        function processFile(file, options) {
            return new Promise((resolve, reject) => {
                const entry = resultMap.get(file.upload.uuid);
                if (!entry) {
                    reject(new Error('Dosya kaydı bulunamadı.'));
                    return;
                }
                const jobId = `${config.type || 'job'}_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
                entry.jobId = jobId;
                entry.state = 'uploading';
                registerActiveJob(jobId);

                const poller = createPoller(entry);
                entry.poller = poller;

                const formData = new FormData();
                formData.append('job_id', jobId);
                formData.append('type', config.type || 'general');
                formData.append('file', file, file.name);
                formData.append('options', JSON.stringify(options));

                const xhr = new XMLHttpRequest();
                entry.xhr = xhr;
                xhr.open('POST', config.endpoint, true);

                xhr.upload.addEventListener('loadstart', () => {
                    updateStatus(entry, {
                        uploadProgress: 0,
                        uploadInfo: 'Yükleme başlatıldı...',
                        statusText: 'Yükleniyor',
                        state: 'uploading'
                    });
                });

                xhr.upload.addEventListener('progress', (event) => {
                    if (event.lengthComputable) {
                        const percent = Math.round((event.loaded / event.total) * 100);
                        const info = `${bytesToMB(event.loaded)} MB / ${bytesToMB(event.total)} MB (${percent}%)`;
                        updateStatus(entry, {
                            uploadProgress: percent,
                            uploadInfo: info,
                            statusText: 'Yükleniyor'
                        });
                    }
                });

                xhr.upload.addEventListener('load', () => {
                    updateStatus(entry, {
                        uploadProgress: 100,
                        uploadInfo: `${bytesToMB(file.size)} MB / ${bytesToMB(file.size)} MB (100%)`,
                        statusText: 'Dönüştürme hazırlanıyor',
                        state: 'processing'
                    });
                    poller.start();
                });

                xhr.addEventListener('abort', () => {
                    unregisterActiveJob(jobId);
                    stopPolling(entry);
                    entry.state = 'cancelled';
                    reject({ cancelled: true });
                });

                xhr.onreadystatechange = () => {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        stopPolling(entry);
                        unregisterActiveJob(jobId);
                        if (entry.state === 'cancelled' || xhr.status === 0) {
                            reject({ cancelled: true });
                            return;
                        }
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                const response = JSON.parse(xhr.responseText || '{}');
                                if (response.success) {
                                    updateStatus(entry, {
                                        convertProgress: 100,
                                        convertInfo: response.message || 'İşlem tamamlandı.',
                                        downloadUrl: response.download_url,
                                        downloadName: response.download_file || entry.downloadName,
                                        statusText: 'Hazır',
                                        state: 'ready'
                                    });
                                    resolve(response);
                                } else if (response.message === 'İş iptal edildi.') {
                                    entry.state = 'cancelled';
                                    reject({ cancelled: true });
                                } else {
                                    const message = response.message || 'İşlem sırasında hata oluştu.';
                                    updateStatus(entry, {
                                        convertInfo: message,
                                        statusText: message,
                                        state: 'error'
                                    });
                                    Swal.fire('Hata', message, 'error');
                                    reject(new Error(message));
                                }
                            } catch (error) {
                                Swal.fire('Hata', 'Sunucudan geçersiz yanıt alındı.', 'error');
                                reject(error);
                            }
                        } else {
                            Swal.fire('Hata', 'Sunucuya erişilirken hata oluştu.', 'error');
                            reject(new Error('HTTP ' + xhr.status));
                        }
                    }
                };

                xhr.onerror = () => {
                    stopPolling(entry);
                    unregisterActiveJob(jobId);
                    if (entry.state === 'cancelled') {
                        reject({ cancelled: true });
                        return;
                    }
                    Swal.fire('Hata', 'İstek sırasında hata oluştu.', 'error');
                    reject(new Error('İstek hatası'));
                };

                xhr.send(formData);
            });
        }

        async function handleConvert() {
            if (!dropzone.files.length) {
                Swal.fire('Dosya seçilmedi', 'Lütfen dönüştürmek için en az bir dosya ekleyin.', 'warning');
                return;
            }

            const options = collectOptions();
            const queue = dropzone.files.filter((file) => {
                const entry = resultMap.get(file.upload.uuid);
                return entry && !['ready', 'downloading', 'downloaded'].includes(entry.state);
            });

            if (!queue.length) {
                Swal.fire('İşlem yok', 'Dönüştürülecek uygun dosya bulunamadı.', 'info');
                return;
            }

            convertButton.disabled = true;
            convertButton.classList.add('disabled');
            convertButton.textContent = 'İşleniyor...';

            let successCount = 0;
            let failureCount = 0;
            let cancelledCount = 0;

            for (const file of queue) {
                try {
                    await processFile(file, options);
                    successCount += 1;
                } catch (error) {
                    if (error && error.cancelled) {
                        cancelledCount += 1;
                        continue;
                    }
                    failureCount += 1;
                    if (error) {
                        console.error(error);
                    }
                }
            }

            convertButton.textContent = config.convertButtonLabel || defaultConvertLabel;
            toggleConvertButton();

            if (failureCount === 0 && cancelledCount === 0 && successCount > 0) {
                Swal.fire('Başarılı', 'Tüm dönüştürme işlemleri tamamlandı.', 'success');
                formEl.reset();
                if (typeof config.onReset === 'function') {
                    try {
                        config.onReset(formEl);
                    } catch (resetError) {
                        console.error(resetError);
                    }
                }
            } else if (successCount > 0 && failureCount > 0) {
                Swal.fire('Kısmi başarı', 'Bazı dosyalar dönüştürülemedi.', 'warning');
            } else if (successCount === 0 && failureCount > 0) {
                Swal.fire('Hata', 'Dosyalar dönüştürülemedi.', 'error');
            }
        }

        const dropzone = new Dropzone(dropzoneElement, {
            url: config.endpoint,
            autoProcessQueue: false,
            uploadMultiple: false,
            parallelUploads: 1,
            maxFiles: maxFiles,
            maxFilesize: maxFileSize,
            addRemoveLinks: false,
            clickable: true,
            dictDefaultMessage: instructionText,
            dictInvalidFileType: typeErrorMessage,
            acceptedFiles: acceptedFilesSetting || undefined,
            previewTemplate: '<div></div>'
        });

        dropzone.on('init', () => {
            const latestMessage = dropzoneElement.querySelector('.dz-message');
            if (latestMessage) {
                messageEl = latestMessage;
            }
            setDropzoneMessage(instructionText);
            updateFileIndicators();
            toggleConvertButton();
        });

        dropzone.on('addedfile', (file) => {
            if (!isFileTypeAllowed(file)) {
                file._nsInvalidType = true;
                dropzone.removeFile(file);
                Swal.fire(typeErrorTitle, typeErrorMessage, 'error');
                return;
            }
            if (file.size > maxFileSize * 1024 * 1024) {
                dropzone.removeFile(file);
                Swal.fire('Dosya boyutu çok büyük', `Her dosya en fazla ${maxFileSize} MB olabilir.`, 'warning');
                return;
            }
            if (dropzone.files.length > maxFiles) {
                dropzone.removeFile(file);
                Swal.fire('Maksimum dosya', `En fazla ${maxFiles} dosya yükleyebilirsiniz.`, 'warning');
                return;
            }
            createResultItem(file);
            toggleConvertButton();
            updateFileIndicators();
        });

        dropzone.on('removedfile', (file) => {
            const uuid = file.upload && file.upload.uuid;
            const entry = uuid ? resultMap.get(uuid) : null;
            if (!entry) {
                updateFileIndicators();
                toggleConvertButton();
                return;
            }
            if (!entry.ignoreRemoval) {
                cancelEntry(entry, { silent: true });
            }
            removeResultEntry(entry);
            updateFileIndicators();
            toggleConvertButton();
        });

        dropzone.on('maxfilesexceeded', (file) => {
            dropzone.removeFile(file);
            Swal.fire('Limit aşıldı', `En fazla ${maxFiles} dosya yükleyebilirsiniz.`, 'warning');
        });

        dropzone.on('error', (file, errorMessage) => {
            if (file && file._nsInvalidType) {
                toggleConvertButton();
                updateFileIndicators();
                return;
            }
            const uuid = file.upload && file.upload.uuid;
            const entry = uuid ? resultMap.get(uuid) : null;
            if (entry) {
                updateStatus(entry, {
                    statusText: typeof errorMessage === 'string' ? errorMessage : 'Yükleme hatası',
                    state: 'error'
                });
            }
            let messageText = typeof errorMessage === 'string' ? errorMessage : 'Dosya yüklenirken hata oluştu.';
            const isTypeError = invalidTypePatterns.some((pattern) => pattern.test(messageText));
            if (isTypeError) {
                messageText = typeErrorMessage;
            }
            Swal.fire(isTypeError ? typeErrorTitle : 'Yükleme hatası', messageText, 'error');
            toggleConvertButton();
            updateFileIndicators();
        });

        convertButton.addEventListener('click', (event) => {
            event.preventDefault();
            handleConvert();
        });

        if (typeof config.setupPresets === 'function') {
            const helpers = config.setupPresets(formEl);
            if (!config.onReset && helpers && typeof helpers.reset === 'function') {
                config.onReset = () => helpers.reset();
            }
        }
    }

    window.nsInitializeConverter = function (config) {
        initializeConverter(config);
    };

    if (window.nsConverterConfig) {
        window.nsInitializeConverter(window.nsConverterConfig);
        delete window.nsConverterConfig;
    }
})();
