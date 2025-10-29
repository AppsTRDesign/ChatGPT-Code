(function () {
    'use strict';

    const DEFAULTS = {
        url: '',
        method: 'POST',
        paramName: 'file',
        parallelUploads: 2,
        autoProcessQueue: true,
        acceptedFiles: null,
        previewsContainer: null,
        previewTemplate: null,
        headers: {},
        timeout: 0
    };

    const STATUS = {
        ADDED: 'added',
        QUEUED: 'queued',
        UPLOADING: 'uploading',
        SUCCESS: 'success',
        ERROR: 'error',
        CANCELED: 'canceled'
    };

    function createElementFromHTML(html) {
        if (!html) {
            const div = document.createElement('div');
            div.className = 'dz-preview dz-file-preview';
            return div;
        }
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        return template.content.firstElementChild;
    }

    function matchesAccepted(file, accepted) {
        if (!accepted) {
            return true;
        }
        const list = accepted.split(',').map(item => item.trim()).filter(Boolean);
        if (list.length === 0) {
            return true;
        }
        const mime = (file.type || '').toLowerCase();
        const extension = file.name ? file.name.split('.').pop().toLowerCase() : '';
        return list.some(entry => {
            if (entry.startsWith('.')) {
                return entry.slice(1).toLowerCase() === extension;
            }
            if (entry.endsWith('/*')) {
                const prefix = entry.slice(0, -1).toLowerCase();
                return mime.startsWith(prefix);
            }
            return entry.toLowerCase() === mime;
        });
    }

    class Dropzone {
        constructor(element, options = {}) {
            if (!(element instanceof Element)) {
                throw new Error('Dropzone element geçersiz.');
            }
            this.element = element;
            this.options = Object.assign({}, DEFAULTS, options);
            if (!this.options.url) {
                this.options.url = element.getAttribute('action') || window.location.href;
            }
            this.options.parallelUploads = Math.max(1, parseInt(this.options.parallelUploads, 10) || 1);
            this.files = [];
            this.events = {};
            this.activeUploads = 0;
            this.previewsContainer = this.resolveContainer(this.options.previewsContainer) || element;
            this.previewTemplate = this.options.previewTemplate;
            this.clickable = element.querySelector('.dz-message') || element;
            this.autoProcessQueue = !!this.options.autoProcessQueue;
            this.element.classList.add('dz-clickable');

            this.initInput();
            this.bindEvents();

            if (typeof this.options.init === 'function') {
                this.options.init.call(this);
            }

            Dropzone.instances.push(this);
        }

        static create(element, options) {
            return new Dropzone(element, options);
        }

        resolveContainer(containerOption) {
            if (!containerOption) {
                return null;
            }
            if (containerOption instanceof Element) {
                return containerOption;
            }
            if (typeof containerOption === 'string') {
                return this.element.closest('section, .card, .modal, body').querySelector(containerOption);
            }
            return null;
        }

        initInput() {
            const input = document.createElement('input');
            input.type = 'file';
            input.multiple = true;
            input.style.display = 'none';
            this.element.appendChild(input);
            this.fileInput = input;
            input.addEventListener('change', (event) => {
                const files = Array.from(event.target.files || []);
                if (files.length) {
                    this.addFiles(files);
                }
                event.target.value = '';
            });
        }

        bindEvents() {
            this.element.addEventListener('dragover', (event) => {
                event.preventDefault();
                this.element.classList.add('dz-drag-hover');
            });
            this.element.addEventListener('dragleave', (event) => {
                if (event.target === this.element) {
                    this.element.classList.remove('dz-drag-hover');
                }
            });
            this.element.addEventListener('drop', (event) => {
                event.preventDefault();
                this.element.classList.remove('dz-drag-hover');
                const files = Array.from(event.dataTransfer?.files || []);
                if (files.length) {
                    this.addFiles(files);
                }
            });
            const openPicker = (event) => {
                if (event.defaultPrevented) {
                    return;
                }
                const interactive = event.target.closest('button, a, input, textarea, select, label, [data-dz-remove]');
                if (interactive) {
                    return;
                }
                this.fileInput.click();
            };
            this.element.addEventListener('click', openPicker);
            if (this.clickable && this.clickable !== this.element) {
                this.clickable.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    openPicker(event);
                });
            }

            this.element.querySelectorAll('.dz-message').forEach((message) => {
                message.style.cursor = 'pointer';
                message.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    openPicker(event);
                });
            });
        }

        addFiles(files) {
            files.forEach(file => this.addFile(file));
        }

        addFile(file) {
            if (!matchesAccepted(file, this.options.acceptedFiles)) {
                this.emit('error', file, 'Bu dosya türüne izin verilmiyor.', null);
                return;
            }
            const previewElement = createElementFromHTML(this.previewTemplate);
            if (previewElement) {
                previewElement.classList.add('dz-preview');
                (this.previewsContainer || this.element).appendChild(previewElement);
            }
            const dzFile = Object.assign(file, {
                status: Dropzone.ADDED,
                previewElement,
                upload: {
                    progress: 0,
                    total: file.size,
                    bytesSent: 0
                },
                xhr: null
            });
            this.files.push(dzFile);
            this.emit('addedfile', dzFile);
            if (this.autoProcessQueue) {
                this.enqueueFile(dzFile);
            }
        }

        enqueueFile(file) {
            if (!file) {
                return;
            }
            file.status = Dropzone.QUEUED;
            this.processQueue();
        }

        enqueueFiles(files) {
            files.forEach(file => this.enqueueFile(file));
        }

        processQueue() {
            const parallel = this.options.parallelUploads;
            while (this.activeUploads < parallel) {
                const next = this.files.find(f => f.status === Dropzone.QUEUED || f.status === Dropzone.ADDED);
                if (!next) {
                    break;
                }
                if (next.status === Dropzone.ADDED) {
                    next.status = Dropzone.QUEUED;
                }
                this.uploadFile(next);
            }
            if (this.activeUploads === 0 && !this.files.some(f => f.status === Dropzone.QUEUED || f.status === Dropzone.UPLOADING)) {
                this.emit('queuecomplete');
            }
        }

        uploadFile(file) {
            file.status = Dropzone.UPLOADING;
            this.activeUploads += 1;
            const formData = this.element instanceof HTMLFormElement
                ? new FormData(this.element)
                : new FormData();
            formData.delete(this.options.paramName);
            formData.append(this.options.paramName, file, file.name);
            const xhr = new XMLHttpRequest();
            file.xhr = xhr;
            xhr.open(this.options.method, this.options.url, true);
            xhr.withCredentials = !!this.options.withCredentials;
            if (this.options.timeout > 0) {
                xhr.timeout = this.options.timeout;
            }
            const headers = this.options.headers || {};
            Object.entries(headers).forEach(([key, value]) => {
                if (key && value !== undefined) {
                    xhr.setRequestHeader(key, value);
                }
            });
            this.emit('sending', file, xhr, formData);

            xhr.upload.addEventListener('progress', (event) => {
                if (!event.lengthComputable) {
                    return;
                }
                file.upload.bytesSent = event.loaded;
                file.upload.total = event.total;
                file.upload.progress = Math.round((event.loaded / event.total) * 100);
                this.emit('uploadprogress', file, file.upload.progress, event.loaded);
            });

            xhr.addEventListener('load', () => {
                this.activeUploads -= 1;
                let response = xhr.responseText;
                try {
                    response = response ? JSON.parse(response) : {};
                } catch (error) {
                    // keep raw response
                }
                if (xhr.status >= 200 && xhr.status < 300) {
                    file.status = Dropzone.SUCCESS;
                    this.emit('success', file, response, xhr);
                } else {
                    file.status = Dropzone.ERROR;
                    this.emit('error', file, response, xhr);
                }
                this.emit('complete', file);
                this.maybeProcessNext();
            });

            xhr.addEventListener('error', () => {
                this.activeUploads -= 1;
                file.status = Dropzone.ERROR;
                this.emit('error', file, xhr.responseText || 'Yükleme hatası', xhr);
                this.emit('complete', file);
                this.maybeProcessNext();
            });

            xhr.addEventListener('abort', () => {
                this.activeUploads = Math.max(0, this.activeUploads - 1);
                file.status = Dropzone.CANCELED;
                this.emit('canceled', file);
                this.emit('complete', file);
                this.maybeProcessNext();
            });

            xhr.addEventListener('timeout', () => {
                this.activeUploads = Math.max(0, this.activeUploads - 1);
                file.status = Dropzone.ERROR;
                this.emit('error', file, 'Zaman aşımı', xhr);
                this.emit('complete', file);
                this.maybeProcessNext();
            });

            xhr.send(formData);
        }

        maybeProcessNext() {
            if (this.files.some(file => file.status === Dropzone.QUEUED || file.status === Dropzone.ADDED)) {
                this.processQueue();
            } else if (this.activeUploads === 0) {
                this.emit('queuecomplete');
            }
        }

        getQueuedFiles() {
            return this.files.filter(file => file.status === Dropzone.QUEUED);
        }

        getActiveFiles() {
            return this.files.filter(file => file.status === Dropzone.UPLOADING);
        }

        getAcceptedFiles() {
            return this.files.filter(file => file.status === Dropzone.SUCCESS);
        }

        getFilesWithStatus(status) {
            return this.files.filter(file => file.status === status);
        }

        cancelUpload(file) {
            if (!file) {
                return;
            }
            if (file.xhr && file.status === Dropzone.UPLOADING) {
                file.xhr.abort();
            } else if (file.status === Dropzone.QUEUED || file.status === Dropzone.ADDED) {
                file.status = Dropzone.CANCELED;
                this.removeFile(file);
                this.emit('canceled', file);
            }
        }

        removeFile(file) {
            const index = this.files.indexOf(file);
            if (index !== -1) {
                this.files.splice(index, 1);
            }
            if (file.previewElement && file.previewElement.parentNode) {
                file.previewElement.parentNode.removeChild(file.previewElement);
            }
            this.emit('removedfile', file);
        }

        removeAllFiles(cancel = false) {
            [...this.files].forEach(file => {
                if (cancel) {
                    this.cancelUpload(file);
                }
                this.removeFile(file);
            });
        }

        on(eventName, handler) {
            if (!this.events[eventName]) {
                this.events[eventName] = [];
            }
            this.events[eventName].push(handler);
            return this;
        }

        emit(eventName, ...args) {
            const handlers = this.events[eventName];
            if (!handlers) {
                return;
            }
            handlers.forEach(handler => {
                try {
                    handler.apply(this, args);
                } catch (error) {
                    console.error(error);
                }
            });
        }
    }

    Object.assign(Dropzone, STATUS);
    Dropzone.prototype.options = DEFAULTS;
    Dropzone.instances = [];
    Dropzone.autoDiscover = false;

    window.Dropzone = Dropzone;
})();
