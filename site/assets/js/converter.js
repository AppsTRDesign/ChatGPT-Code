Dropzone.autoDiscover = false;

(function(){
    function initializeConverter(configInput){
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

        if (!dropzoneElement || !convertButton || !formEl || !resultList) {
            console.warn('Dönüştürücü için gerekli DOM elemanları bulunamadı.');
            return;
        }

        const resultMap = new Map();

        const dz = new Dropzone(dropzoneElement, {
            url: '/ajax/process.php',
            autoProcessQueue: false,
            uploadMultiple: false,
            parallelUploads: 1,
            maxFiles: maxFiles,
            maxFilesize: maxFileSize,
            addRemoveLinks: true,
            dictDefaultMessage: 'Dosyalarınızı sürükleyip bırakın veya tıklayarak seçin',
            previewTemplate: '<div></div>'
        });

        function bytesToMB(bytes){
            return (bytes / (1024 * 1024)).toFixed(2);
        }

        function formatBytes(bytes){
            if (bytes === 0) return '0 B';
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + sizes[i];
        }

        function createResultItem(file){
            const wrapper = document.createElement('div');
            wrapper.className = 'ns-result';
            const fileName = document.createElement('div');
            fileName.className = 'ns-file';
            fileName.textContent = file.name;

            const status = document.createElement('div');
            status.className = 'ns-status';
            status.textContent = `Boyut: ${formatBytes(file.size)}`;

            const uploadProgress = document.createElement('div');
            uploadProgress.className = 'ns-progress';
            const uploadBar = document.createElement('div');
            uploadBar.className = 'ns-progress-bar';
            uploadProgress.appendChild(uploadBar);

            const uploadInfo = document.createElement('div');
            uploadInfo.className = 'ns-progress-info';
            uploadInfo.textContent = 'Yükleme bekleniyor...';

            const convertProgress = document.createElement('div');
            convertProgress.className = 'ns-progress';
            const convertBar = document.createElement('div');
            convertBar.className = 'ns-progress-bar';
            convertProgress.appendChild(convertBar);

            const convertInfo = document.createElement('div');
            convertInfo.className = 'ns-progress-info';
            convertInfo.textContent = 'Dönüştürme bekleniyor...';

            const actionWrap = document.createElement('div');
            actionWrap.className = 'ns-actions';
            const downloadBtn = document.createElement('a');
            downloadBtn.className = 'ns-btn ns-btn-primary ns-download';
            downloadBtn.textContent = 'İndir';
            downloadBtn.href = '#';
            downloadBtn.setAttribute('download', file.name);
            downloadBtn.style.display = 'none';
            actionWrap.appendChild(downloadBtn);

            wrapper.append(fileName, status, uploadProgress, uploadInfo, convertProgress, convertInfo, actionWrap);

            resultList.appendChild(wrapper);

            resultMap.set(file.upload.uuid, {
                element: wrapper,
                status,
                uploadBar,
                uploadInfo,
                convertBar,
                convertInfo,
                downloadBtn,
                file
            });
        }

        function removeResultItem(file){
            const item = resultMap.get(file.upload.uuid);
            if (item) {
                item.element.remove();
                resultMap.delete(file.upload.uuid);
            }
        }

        dz.on('addedfile', (file) => {
            if (dz.files.length > maxFiles) {
                dz.removeFile(file);
                Swal.fire('Maksimum dosya', `En fazla ${maxFiles} dosya yükleyebilirsiniz.`, 'warning');
                return;
            }
            if (file.size > maxFileSize * 1024 * 1024) {
                dz.removeFile(file);
                Swal.fire('Dosya boyutu çok büyük', `Her dosya en fazla ${maxFileSize} MB olabilir.`, 'warning');
                return;
            }
            createResultItem(file);
            toggleConvertButton();
        });

        dz.on('removedfile', (file) => {
            removeResultItem(file);
            toggleConvertButton();
        });

        dz.on('maxfilesexceeded', (file) => {
            dz.removeFile(file);
            Swal.fire('Limit aşıldı', `En fazla ${maxFiles} dosya yükleyebilirsiniz.`, 'warning');
        });

        dz.on('error', (file, errorMessage) => {
            Swal.fire('Yükleme hatası', errorMessage, 'error');
            removeResultItem(file);
        });

        function toggleConvertButton(){
            if (dz.files.length === 0) {
                convertButton.disabled = true;
                convertButton.classList.add('disabled');
            } else {
                convertButton.disabled = false;
                convertButton.classList.remove('disabled');
            }
        }

        toggleConvertButton();

        function collectOptions(){
            const data = new FormData(formEl);
            const options = {};
            for (const [key, value] of data.entries()) {
                options[key] = value;
            }
            return options;
        }

        function updateStatus(uuid, payload){
            const item = resultMap.get(uuid);
            if (!item) return;
            if (payload.uploadProgress != null) {
                item.uploadBar.style.width = `${payload.uploadProgress}%`;
            }
            if (payload.uploadInfo) {
                item.uploadInfo.textContent = payload.uploadInfo;
            }
            if (payload.convertProgress != null) {
                item.convertBar.style.width = `${payload.convertProgress}%`;
            }
            if (payload.convertInfo) {
                item.convertInfo.textContent = payload.convertInfo;
            }
            if (payload.statusText) {
                item.status.textContent = payload.statusText;
            }
            if (payload.downloadUrl) {
                item.downloadBtn.href = payload.downloadUrl;
                item.downloadBtn.style.display = 'inline-flex';
                item.element.classList.add('ready');
            }
        }

        function pollJob(jobId, uuid){
            let pollTimer = null;
            const start = () => {
                pollTimer = setInterval(async () => {
                    try {
                        const response = await fetch(`/ajax/status.php?job_id=${encodeURIComponent(jobId)}`);
                        if (!response.ok) return;
                        const data = await response.json();
                        if (data.upload_percent !== undefined) {
                            updateStatus(uuid, {
                                uploadInfo: data.upload_text || '',
                                uploadProgress: data.upload_percent,
                                statusText: data.status || ''
                            });
                        }
                        if (data.convert_percent !== undefined) {
                            updateStatus(uuid, {
                                convertInfo: data.convert_text || '',
                                convertProgress: data.convert_percent,
                                statusText: data.status || ''
                            });
                        }
                        if (data.status === 'completed') {
                            updateStatus(uuid, {
                                convertProgress: 100,
                                convertInfo: data.convert_text || 'Dönüştürme tamamlandı.',
                                downloadUrl: data.download_url
                            });
                            clearInterval(pollTimer);
                        }
                        if (data.status === 'error') {
                            updateStatus(uuid, {
                                convertInfo: data.message || 'Hata oluştu',
                                statusText: data.message || 'Hata oluştu'
                            });
                            Swal.fire('Hata', data.message || 'İşlem sırasında bir hata oluştu.', 'error');
                            clearInterval(pollTimer);
                        }
                    } catch (error) {
                        console.error(error);
                    }
                }, 1000);
            };
            const stop = () => {
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            };
            return { start, stop };
        }

        async function processFile(file, options){
            return new Promise((resolve, reject) => {
                const uuid = file.upload.uuid;
                const jobId = `${config.type || 'job'}_${Date.now()}_${Math.random().toString(36).slice(2,8)}`;
                const poller = pollJob(jobId, uuid);

                const formData = new FormData();
                formData.append('job_id', jobId);
                formData.append('type', config.type || 'general');
                formData.append('file', file, file.name);
                formData.append('options', JSON.stringify(options));

                const xhr = new XMLHttpRequest();
                xhr.open('POST', config.endpoint, true);

                xhr.upload.addEventListener('loadstart', () => {
                    updateStatus(uuid, {
                        uploadProgress: 0,
                        uploadInfo: 'Yükleme başlatıldı...',
                        statusText: 'Yükleniyor'
                    });
                });

                xhr.upload.addEventListener('progress', (event) => {
                    if (event.lengthComputable) {
                        const percent = Math.round((event.loaded / event.total) * 100);
                        const info = `${bytesToMB(event.loaded)} MB / ${bytesToMB(event.total)} MB (${percent}%)`;
                        updateStatus(uuid, {
                            uploadProgress: percent,
                            uploadInfo: info,
                            statusText: 'Yükleniyor'
                        });
                    }
                });

                xhr.upload.addEventListener('load', () => {
                    poller.start();
                    updateStatus(uuid, {
                        uploadProgress: 100,
                        uploadInfo: `${bytesToMB(file.size)} MB / ${bytesToMB(file.size)} MB (100%)`,
                        statusText: 'Dönüştürme hazırlanıyor'
                    });
                });

                xhr.onreadystatechange = () => {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        poller.stop();
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    updateStatus(uuid, {
                                        convertProgress: 100,
                                        convertInfo: response.message || 'İşlem tamamlandı.',
                                        downloadUrl: response.download_url,
                                        statusText: 'Hazır'
                                    });
                                    resolve(response);
                                } else {
                                    const message = response.message || 'İşlem sırasında hata oluştu.';
                                    updateStatus(uuid, {
                                        convertInfo: message,
                                        statusText: message
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
                    poller.stop();
                    Swal.fire('Hata', 'İstek sırasında hata oluştu.', 'error');
                    reject(new Error('İstek hatası'));
                };

                xhr.send(formData);
            });
        }

        async function handleConvert(){
            if (dz.files.length === 0) {
                Swal.fire('Dosya seçilmedi', 'Lütfen dönüştürmek için en az bir dosya ekleyin.', 'warning');
                return;
            }

            const options = collectOptions();
            convertButton.disabled = true;
            convertButton.classList.add('disabled');
            convertButton.textContent = 'İşleniyor...';

            for (const file of dz.files) {
                try {
                    updateStatus(file.upload.uuid, {
                        convertProgress: 0,
                        convertInfo: 'Dönüştürme başlatılıyor...'
                    });
                    await processFile(file, options);
                } catch (error) {
                    console.error(error);
                }
            }

            convertButton.disabled = false;
            convertButton.classList.remove('disabled');
            convertButton.textContent = config.convertButtonLabel || 'Dönüştür';
        }

        convertButton.addEventListener('click', (event) => {
            event.preventDefault();
            handleConvert();
        });

        if (typeof config.setupPresets === 'function') {
            config.setupPresets(formEl);
        }
    }

    window.nsInitializeConverter = function(config){
        initializeConverter(config);
    };

    if (window.nsConverterConfig) {
        window.nsInitializeConverter(window.nsConverterConfig);
        delete window.nsConverterConfig;
    }
})();
