(function () {
    'use strict';

    const dataTables = new Map();

    initializeLogout();
    initializeLoginForm();
    initializeAjaxForms();
    initializeTables();
    initializeRowFillers();
    initializeRefreshButtons();
    initializeCharts();
    initializeDropzones();
    initializeHtmlEditors();
    initializeReportFilters();

    function initializeLogout() {
        document.querySelectorAll('[data-action="logout"]').forEach((button) => {
            button.addEventListener('click', () => {
                fetch('/logout', { method: 'POST' })
                    .then((response) => response.json())
                    .then((payload) => {
                        if (payload.redirect) {
                            window.location.href = payload.redirect;
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata',
                            text: 'Çıkış işlemi başarısız oldu.'
                        });
                    });
            });
        });
    }

    function initializeLoginForm() {
        const loginForm = document.getElementById('login-form');
        if (!loginForm) {
            return;
        }

        loginForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!loginForm.checkValidity()) {
                loginForm.classList.add('was-validated');
                return;
            }

            const formData = formDataToObject(new FormData(loginForm));
            fetch('/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(formData)
            })
                .then((response) => response.json())
                .then((payload) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı',
                        text: 'Yönlendiriliyorsunuz...'
                    }).then(() => {
                        window.location.href = payload.redirect;
                    });
                })
                .catch((error) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata',
                        text: error?.response?.data?.message || 'Giriş başarısız'
                    });
                });
        });
    }

    function initializeAjaxForms() {
        document.querySelectorAll('form[data-ajax="true"]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();

                const endpoint = form.getAttribute('data-endpoint') || form.getAttribute('action') || window.location.pathname;
                const method = (form.getAttribute('method') || 'POST').toUpperCase();
                const payload = formDataToObject(new FormData(form));
                const asJson = form.getAttribute('data-json') === 'true';

                const options = { method };
                if (asJson) {
                    options.headers = { 'Content-Type': 'application/json' };
                    options.body = JSON.stringify(payload);
                } else {
                    options.headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
                    options.body = new URLSearchParams(payload);
                }

                fetch(endpoint, options)
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.status === 'error') {
                            throw new Error(data.message || 'İşlem başarısız');
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Başarılı',
                            text: data.message || 'İşlem tamamlandı'
                        });

                        if (form.getAttribute('data-reset') !== 'false') {
                            form.reset();
                            form.querySelectorAll('textarea[data-html-editor="true"]').forEach((field) => {
                                field.dispatchEvent(new CustomEvent('html-editor:update', { detail: '' }));
                            });
                        }

                        const refreshTargets = form.getAttribute('data-refresh');
                        if (refreshTargets) {
                            refreshTargets.split(',').forEach((selector) => {
                                const table = document.querySelector(selector.trim());
                                if (table) {
                                    loadTable(table, true);
                                }
                            });
                        }
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata',
                            text: error.message || 'İşlem sırasında hata oluştu'
                        });
                    });
            });
        });
    }

    function initializeTables() {
        document.querySelectorAll('table[data-source]').forEach((table) => {
            table.addEventListener('datatable.refresh', () => loadTable(table, true));
            loadTable(table, false);
        });
    }

    function initializeRowFillers() {
        document.querySelectorAll('table[data-fill-form]').forEach((table) => {
            const formSelector = table.getAttribute('data-fill-form');
            const form = document.querySelector(formSelector);
            if (!form) {
                return;
            }

            table.addEventListener('click', (event) => {
                const row = event.target.closest('tr[data-row]');
                if (!row) {
                    return;
                }

                try {
                    const record = JSON.parse(row.getAttribute('data-row'));
                    populateForm(form, record);
                } catch (error) {
                    console.error('Satır verisi işlenemedi', error);
                }
            });
        });
    }

    function initializeRefreshButtons() {
        document.querySelectorAll('[data-action="refresh"]').forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.getAttribute('data-target');
                if (!target) {
                    return;
                }

                const table = document.querySelector(target.startsWith('#') ? target : `#${target}`);
                if (table && table.matches('table[data-source]')) {
                    loadTable(table, true);
                }

                if (target.includes('chart')) {
                    const canvas = document.querySelector(target);
                    if (canvas) {
                        canvas.dataset.dirty = 'true';
                    }
                    initializeCharts(true);
                }
            });
        });
    }

    function loadTable(table, forceReload) {
        const source = table.getAttribute('data-source');
        if (!source) {
            return;
        }

        fetch(source, { cache: 'no-store' })
            .then((response) => response.json())
            .then((data) => {
                const rows = Array.isArray(data)
                    ? data
                    : Array.isArray(data.data)
                        ? data.data
                        : [];

                populateTable(table, rows);

                table.dispatchEvent(new CustomEvent('datatable.load', { detail: rows }));

                if (dataTables.has(table)) {
                    const existing = dataTables.get(table);
                    existing.destroy();
                    dataTables.delete(table);
                }

                const instance = new DataTable(table, {
                    responsive: true,
                    destroy: true
                });
                dataTables.set(table, instance);
            })
            .catch((error) => {
                console.error('Tablo yüklenemedi', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'Veriler yüklenemedi.'
                });
            });
    }

    function populateTable(table, data) {
        const tbody = table.querySelector('tbody');
        if (!tbody) {
            return;
        }

        tbody.innerHTML = '';
        const columnDefinition = table.getAttribute('data-columns');
        let columns = [];
        if (columnDefinition) {
            try {
                columns = JSON.parse(columnDefinition);
            } catch (error) {
                console.warn('Tablo kolonları çözümlenemedi', error);
            }
        }

        data.forEach((row) => {
            const tr = document.createElement('tr');
            tr.setAttribute('data-row', JSON.stringify(row));

            const keys = columns.length ? columns : Object.keys(row);
            keys.forEach((key) => {
                const td = document.createElement('td');
                const value = row[key];
                td.innerHTML = formatValue(value);
                tr.appendChild(td);
            });

            tbody.appendChild(tr);
        });
    }

    function populateForm(form, data) {
        Object.entries(data).forEach(([key, value]) => {
            const element = form.querySelector(`[name="${key}"]`);
            if (!element) {
                return;
            }

            if (element.type === 'checkbox') {
                if (Array.isArray(value)) {
                    element.checked = value.includes(element.value);
                } else {
                    element.checked = Boolean(value);
                }
            } else if (element.type === 'datetime-local') {
                if (value) {
                    const normalized = String(value).replace(' ', 'T').slice(0, 16);
                    element.value = normalized;
                } else {
                    element.value = '';
                }
            } else if (element.type === 'select-multiple' && Array.isArray(value)) {
                Array.from(element.options).forEach((option) => {
                    option.selected = value.includes(option.value);
                });
            } else {
                element.value = Array.isArray(value) ? value.join(', ') : (value ?? '');
            }
        });
    }

    function formDataToObject(formData) {
        const result = {};
        formData.forEach((value, key) => {
            if (key.endsWith('[]')) {
                const normalized = key.slice(0, -2);
                if (!Array.isArray(result[normalized])) {
                    result[normalized] = [];
                }
                result[normalized].push(value);
            } else if (/^\w+\[\w+\]$/.test(key)) {
                const [, parent, child] = key.match(/^(\w+)\[(\w+)\]$/);
                if (!result[parent]) {
                    result[parent] = {};
                }
                result[parent][child] = value;
            } else if (result[key] !== undefined) {
                if (Array.isArray(result[key])) {
                    result[key].push(value);
                } else {
                    result[key] = value;
                }
            } else {
                result[key] = value;
            }
        });

        return result;
    }

    function formatValue(value) {
        if (value === null || value === undefined || value === '') {
            return '<span class="text-muted">-</span>';
        }

        if (Array.isArray(value)) {
            return value.map((item) => escapeHtml(item)).join(', ');
        }

        if (typeof value === 'object') {
            return Object.entries(value)
                .map(([key, val]) => `<span class="badge bg-accent text-dark me-1">${escapeHtml(key)}: ${escapeHtml(val)}</span>`)
                .join('');
        }

        return escapeHtml(value);
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    function initializeCharts(force) {
        const registerChart = (canvas, config) => {
            if (canvas.chartInstance) {
                canvas.chartInstance.destroy();
            }

            canvas.chartInstance = new Chart(canvas, config);
            canvas.dataset.initialized = 'true';
            canvas.dataset.dirty = 'false';
        };

        const adminChartCanvas = document.getElementById('admin-activity-chart');
        if (adminChartCanvas && (force || !adminChartCanvas.dataset.initialized)) {
            const dataset = adminChartCanvas.dataset.series ? JSON.parse(adminChartCanvas.dataset.series) : [];
            const labels = dataset.map((item) => item.day);
            const totals = dataset.map((item) => Number(item.total || 0));

            registerChart(adminChartCanvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Gönderilen Bildirim',
                        data: totals,
                        fill: true,
                        borderColor: '#00b8a9',
                        backgroundColor: 'rgba(0, 184, 169, 0.2)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        const clientChartCanvas = document.getElementById('client-performance-chart');
        if (clientChartCanvas && (force || !clientChartCanvas.dataset.initialized)) {
            fetch(clientChartCanvas.dataset.source || '/client/reports/engagement')
                .then((response) => response.json())
                .then((series) => {
                    const labels = series.map((item) => item.day);
                    const clicks = series.map((item) => Number(item.clicks || 0));
                    const opens = series.map((item) => Number(item.opens || 0));

                    registerChart(clientChartCanvas, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [
                                {
                                    label: 'Tıklamalar',
                                    data: clicks,
                                    backgroundColor: '#0a4d68'
                                },
                                {
                                    label: 'Açılma',
                                    data: opens,
                                    backgroundColor: '#00b8a9'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                });
        }

        const revenueChartCanvas = document.getElementById('admin-revenue-chart');
        if (revenueChartCanvas && (force || !revenueChartCanvas.dataset.initialized || revenueChartCanvas.dataset.dirty === 'true')) {
            const range = revenueChartCanvas.dataset.range || 'monthly';
            const source = revenueChartCanvas.dataset.source || '/admin/reports/revenue';
            fetch(`${source}?range=${range}`)
                .then((response) => response.json())
                .then((series) => {
                    const labels = series.map((item) => item.bucket);
                    const totals = series.map((item) => Number(item.total || 0));

                    registerChart(revenueChartCanvas, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Onaylı Gelir',
                                data: totals,
                                backgroundColor: '#0a4d68'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                });
        }

        const apiUsageCanvas = document.getElementById('admin-api-usage-chart');
        if (apiUsageCanvas && (force || !apiUsageCanvas.dataset.initialized || apiUsageCanvas.dataset.dirty === 'true')) {
            fetch(apiUsageCanvas.dataset.source || '/admin/reports/api/usage')
                .then((response) => response.json())
                .then((series) => {
                    const labels = series.map((item) => item.day);
                    const totals = series.map((item) => Number(item.total || 0));
                    const errors = series.map((item) => Number(item.errors || 0));

                    registerChart(apiUsageCanvas, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [
                                {
                                    label: 'Toplam Çağrı',
                                    data: totals,
                                    borderColor: '#00b8a9',
                                    backgroundColor: 'rgba(0, 184, 169, 0.15)',
                                    fill: true,
                                    tension: 0.4
                                },
                                {
                                    label: 'Hatalar',
                                    data: errors,
                                    borderColor: '#dc3545',
                                    backgroundColor: 'rgba(220, 53, 69, 0.15)',
                                    fill: true,
                                    tension: 0.4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                });
        }

        document.querySelectorAll('canvas[data-report-id="traffic"]').forEach((canvas) => {
            if (!canvas.dataset.source) {
                return;
            }

            if (!force && canvas.dataset.initialized && canvas.dataset.dirty !== 'true') {
                return;
            }

            const range = canvas.dataset.range || 'daily';
            fetch(`${canvas.dataset.source}?range=${range}`)
                .then((response) => response.json())
                .then((series) => {
                    const labels = series.map((item) => item.bucket);
                    const totals = series.map((item) => Number(item.total || 0));
                    const sent = series.map((item) => Number(item.sent || 0));
                    const failed = series.map((item) => Number(item.failed || 0));

                    registerChart(canvas, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [
                                {
                                    label: 'Toplam',
                                    data: totals,
                                    borderColor: '#0a4d68',
                                    backgroundColor: 'rgba(10, 77, 104, 0.15)',
                                    fill: true,
                                    tension: 0.35
                                },
                                {
                                    label: 'Başarılı',
                                    data: sent,
                                    borderColor: '#00b8a9',
                                    backgroundColor: 'rgba(0, 184, 169, 0.15)',
                                    fill: true,
                                    tension: 0.35
                                },
                                {
                                    label: 'Hatalı',
                                    data: failed,
                                    borderColor: '#dc3545',
                                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                    fill: true,
                                    tension: 0.35
                                }
                            ]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                });
        });

        document.querySelectorAll('canvas[data-report-id="memberships"]').forEach((canvas) => {
            if (!canvas.dataset.source) {
                return;
            }

            if (!force && canvas.dataset.initialized && canvas.dataset.dirty !== 'true') {
                return;
            }

            const range = canvas.dataset.range || 'daily';
            fetch(`${canvas.dataset.source}?range=${range}`)
                .then((response) => response.json())
                .then((series) => {
                    const labels = series.map((item) => item.bucket);
                    const members = series.map((item) => Number(item.new_members || 0));
                    const packages = series.map((item) => Number(item.activated_packages || 0));

                    registerChart(canvas, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [
                                {
                                    type: 'bar',
                                    label: 'Yeni Üyeler',
                                    data: members,
                                    backgroundColor: '#4f9da6'
                                },
                                {
                                    type: 'line',
                                    label: 'Aktif Paket',
                                    data: packages,
                                    borderColor: '#f9a620',
                                    backgroundColor: 'rgba(249, 166, 32, 0.15)',
                                    fill: true,
                                    tension: 0.3
                                }
                            ]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                });
        });

        document.querySelectorAll('canvas[data-report-id="api"]').forEach((canvas) => {
            if (!canvas.dataset.source) {
                return;
            }

            if (!force && canvas.dataset.initialized && canvas.dataset.dirty !== 'true') {
                return;
            }

            fetch(canvas.dataset.source)
                .then((response) => response.json())
                .then((series) => {
                    const labels = series.map((item) => item.day || item.bucket);
                    const totals = series.map((item) => Number(item.total || 0));
                    const errors = series.map((item) => Number(item.errors || 0));

                    registerChart(canvas, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [
                                {
                                    label: 'Toplam Çağrı',
                                    data: totals,
                                    borderColor: '#0a4d68',
                                    backgroundColor: 'rgba(10, 77, 104, 0.15)',
                                    fill: true,
                                    tension: 0.35
                                },
                                {
                                    label: 'Hatalar',
                                    data: errors,
                                    borderColor: '#dc3545',
                                    backgroundColor: 'rgba(220, 53, 69, 0.15)',
                                    fill: true,
                                    tension: 0.35
                                }
                            ]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                });
        });
    }

    function initializeDropzones() {
        if (typeof Dropzone === 'undefined') {
            return;
        }

        Dropzone.autoDiscover = false;

        document.querySelectorAll('[data-dropzone="true"]').forEach((element) => {
            if (element.dropzone) {
                return;
            }

            const previewSelector = element.getAttribute('data-preview');
            const type = element.getAttribute('data-type') || 'file';

            const dz = new Dropzone(element, {
                paramName: 'file',
                maxFiles: 1,
                acceptedFiles: 'image/*',
                dictDefaultMessage: 'Dosyayı buraya sürükleyin veya tıklayın',
                init() {
                    this.on('sending', (file, xhr, formData) => {
                        formData.append('type', type);
                    });

                    this.on('success', (file, response) => {
                        if (previewSelector && response?.path) {
                            const preview = document.querySelector(previewSelector);
                            if (preview) {
                                if (preview.tagName === 'IMG') {
                                    preview.src = response.path;
                                } else {
                                    preview.textContent = response.path;
                                }
                            }
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Yüklendi',
                            text: 'Dosya başarıyla yüklendi'
                        });
                    });

                    this.on('error', (file, message) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Yükleme başarısız',
                            text: typeof message === 'string' ? message : 'Dosya yüklenemedi'
                        });
                    });

                    this.on('complete', () => {
                        this.removeAllFiles(true);
                    });
                }
            });

            element.dropzone = dz;
        });
    }

    function initializeHtmlEditors() {
        document.querySelectorAll('textarea[data-html-editor="true"]').forEach((textarea) => {
            if (textarea.dataset.editorInitialized === 'true') {
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'html-editor';

            const toolbar = document.createElement('div');
            toolbar.className = 'html-editor__toolbar btn-toolbar mb-2';

            const buttons = [
                { icon: 'format_bold', command: 'bold', title: 'Kalın' },
                { icon: 'format_italic', command: 'italic', title: 'İtalik' },
                { icon: 'format_underlined', command: 'underline', title: 'Altı Çizili' },
                { icon: 'link', command: 'createLink', title: 'Bağlantı', prompt: 'Bağlantı adresi giriniz' },
                { icon: 'format_list_bulleted', command: 'insertUnorderedList', title: 'Madde İşaretli Liste' },
                { icon: 'format_list_numbered', command: 'insertOrderedList', title: 'Numaralı Liste' },
                { icon: 'format_clear', command: 'removeFormat', title: 'Biçimlendirmeyi Temizle' }
            ];

            buttons.forEach((config) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-light btn-sm';
                button.title = config.title;
                button.innerHTML = `<span class="material-symbols-outlined">${config.icon}</span>`;
                button.addEventListener('click', () => {
                    content.focus();
                    if (config.command === 'createLink') {
                        const url = prompt(config.prompt || 'Bağlantı giriniz');
                        if (url) {
                            document.execCommand(config.command, false, url);
                        }
                        return;
                    }
                    document.execCommand(config.command, false, null);
                });
                toolbar.appendChild(button);
            });

            const content = document.createElement('div');
            content.className = 'html-editor__content form-control';
            content.contentEditable = 'true';
            content.innerHTML = textarea.value;
            const editorId = `html-editor-${Math.random().toString(16).slice(2)}`;
            content.dataset.editorContent = editorId;
            textarea.dataset.editorTarget = editorId;

            textarea.classList.add('d-none');

            textarea.parentNode.insertBefore(wrapper, textarea);
            wrapper.appendChild(toolbar);
            wrapper.appendChild(content);
            wrapper.appendChild(textarea);

            const syncValue = () => {
                textarea.value = content.innerHTML.trim();
            };

            content.addEventListener('input', syncValue);
            content.addEventListener('blur', syncValue);

            textarea.addEventListener('html-editor:update', (event) => {
                const value = event.detail || '';
                content.innerHTML = value;
                textarea.value = value;
            });

            const form = textarea.closest('form');
            if (form && !form.dataset.editorSyncAttached) {
                form.addEventListener('submit', () => {
                    form.querySelectorAll('textarea[data-html-editor="true"]').forEach((field) => {
                        const target = field.dataset.editorTarget;
                        if (!target) {
                            return;
                        }
                        const editor = form.querySelector(`[data-editor-content="${target}"]`);
                        if (editor) {
                            field.value = editor.innerHTML.trim();
                        }
                    });
                });
                form.dataset.editorSyncAttached = 'true';
            }

            textarea.dataset.editorInitialized = 'true';
        });
    }

    function initializeReportFilters() {
        document.querySelectorAll('[data-report-filter]').forEach((select) => {
            select.addEventListener('change', () => {
                const target = select.getAttribute('data-report-filter');
                const scope = select.getAttribute('data-report-scope') || '';
                const canvas = document.querySelector(`canvas[data-report-id="${target}"][data-report-scope="${scope}"]`);
                if (canvas) {
                    canvas.dataset.range = select.value;
                    canvas.dataset.dirty = 'true';
                    initializeCharts(true);
                }
            });
        });

        document.querySelectorAll('[data-action="export-report"]').forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.getAttribute('data-target');
                const scope = button.getAttribute('data-scope') || '';
                const format = button.getAttribute('data-format') || 'excel';
                const select = document.querySelector(`[data-report-filter="${target}"][data-report-scope="${scope}"]`);
                const canvas = document.querySelector(`canvas[data-report-id="${target}"][data-report-scope="${scope}"]`);
                const range = select ? select.value : (canvas ? canvas.dataset.range : 'daily');

                const url = new URL(`/admin/reports/export/${target}`, window.location.origin);
                url.searchParams.set('format', format);
                if (range) {
                    url.searchParams.set('range', range);
                }

                window.open(url.toString(), '_blank', 'noopener');
            });
        });
    }
})();
