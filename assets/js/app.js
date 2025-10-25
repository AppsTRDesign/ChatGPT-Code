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
                if (!Array.isArray(result[key])) {
                    result[key] = [result[key]];
                }
                result[key].push(value);
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
        const adminChartCanvas = document.getElementById('admin-activity-chart');
        if (adminChartCanvas && (force || !adminChartCanvas.dataset.initialized)) {
            const dataset = adminChartCanvas.dataset.series ? JSON.parse(adminChartCanvas.dataset.series) : [];
            const labels = dataset.map((item) => item.day);
            const totals = dataset.map((item) => Number(item.total || 0));

            new Chart(adminChartCanvas, {
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

            adminChartCanvas.dataset.initialized = 'true';
        }

        const clientChartCanvas = document.getElementById('client-performance-chart');
        if (clientChartCanvas && (force || !clientChartCanvas.dataset.initialized)) {
            fetch(clientChartCanvas.dataset.source || '/client/reports/engagement')
                .then((response) => response.json())
                .then((series) => {
                    const labels = series.map((item) => item.day);
                    const clicks = series.map((item) => Number(item.clicks || 0));
                    const opens = series.map((item) => Number(item.opens || 0));

                    new Chart(clientChartCanvas, {
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

                    clientChartCanvas.dataset.initialized = 'true';
                });
        }
    }
})();
