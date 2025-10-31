Dropzone.autoDiscover = false;

const AdminApp = (() => {
    const state = {
        summary: {},
        tables: [],
        orders: [],
        waiterCalls: [],
        reports: [],
        settings: window.APP_STATE?.settings || {},
        qrPreview: window.APP_STATE?.qrPreview || '',
        currencies: [],
        languages: [],
        chart: null,
        period: 'weekly',
        orderStatus: 'all',
    };

    const selectors = {
        navButtons: document.querySelectorAll('.dashboard__link'),
        sections: document.querySelectorAll('.section'),
        summaryCards: document.querySelectorAll('[data-summary]'),
        chartCanvas: document.querySelector('#ordersChart'),
        ordersTable: $('#ordersTable'),
        tablesTable: $('#tablesTable'),
        waiterTable: $('#waiterTable'),
        reportsTable: $('#reportsTable'),
        languagesTable: $('#languagesTable'),
        currenciesTable: $('#currenciesTable'),
        defaultLanguage: document.querySelector('#defaultLanguage'),
        logoutButton: document.querySelector('#logoutButton'),
        orderStatusFilters: document.querySelectorAll('#orderStatusFilters button'),
        reportButtons: document.querySelectorAll('[data-report]'),
        generalSettingsForm: document.querySelector('#generalSettingsForm'),
        brandingForm: document.querySelector('#brandingForm'),
        qrForm: document.querySelector('#qrForm'),
        languageModal: document.querySelector('#languageModal'),
        languageForm: document.querySelector('#languageForm'),
        saveLanguage: document.querySelector('#saveLanguage'),
        currencyModal: document.querySelector('#currencyModal'),
        currencyForm: document.querySelector('#currencyForm'),
        saveCurrency: document.querySelector('#saveCurrency'),
        qrPreview: document.querySelector('#qrPreviewImage'),
    };

    const toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4200,
    });

    const switchSection = (section) => {
        selectors.sections.forEach((element) => {
            element.classList.toggle('d-none', element.id !== `section-${section}`);
        });
    };

    const bindNavigation = () => {
        selectors.navButtons.forEach((button) => {
            button.addEventListener('click', () => {
                selectors.navButtons.forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                switchSection(button.dataset.section);
            });
        });
    };

    const fetchDashboard = async () => {
        const response = await fetch(`api/dashboard.php?period=${state.period}`);
        const data = await response.json();
        state.summary = data.summary || {};
        selectors.summaryCards.forEach((card) => {
            const key = card.dataset.summary;
            card.querySelector('strong').innerText = state.summary[key] ?? 0;
        });
        renderChart(data.charts);
    };

    const renderChart = (chartData) => {
        if (!selectors.chartCanvas || !chartData) {
            return;
        }
        if (state.chart) {
            state.chart.destroy();
        }
        const datasets = (chartData.datasets || []).map((dataset) => ({
            ...dataset,
            borderRadius: 12,
            stack: 'stack1',
        }));
        state.chart = new Chart(selectors.chartCanvas, {
            type: 'bar',
            data: {
                labels: chartData.labels || [],
                datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true },
                    y: { stacked: true },
                },
                plugins: {
                    legend: { position: 'bottom' },
                },
            },
        });
    };

    const fetchTables = async () => {
        const response = await fetch('api/tables.php');
        const data = await response.json();
        state.tables = data.tables || [];
        selectors.tablesTable.DataTable({
            data: state.tables,
            destroy: true,
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json' },
            columns: [
                { data: 'name', title: 'Masa' },
                { data: 'status_label', title: 'Durum' },
                {
                    data: 'qr_url',
                    title: 'Masa Bağlantısı',
                    render: (value) => `<a href="${value}" target="_blank" rel="noopener">${value}</a>`,
                },
            ],
        });
    };

    const fetchOrders = async () => {
        const response = await fetch(`api/orders.php?status=${state.orderStatus}`);
        const data = await response.json();
        state.orders = data.orders || [];
        selectors.ordersTable.DataTable({
            data: state.orders,
            destroy: true,
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json' },
            columns: [
                { data: 'id', title: 'Sipariş ID' },
                { data: 'table', title: 'Masa' },
                { data: 'status', title: 'Durum' },
                { data: 'total_formatted', title: 'Tutar' },
                { data: 'created_at', title: 'Tarih' },
            ],
        });
    };

    const fetchWaiterCalls = async () => {
        const response = await fetch('api/waiter-calls.php');
        const data = await response.json();
        state.waiterCalls = data.calls || [];
        selectors.waiterTable.DataTable({
            data: state.waiterCalls,
            destroy: true,
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json' },
            columns: [
                { data: 'table', title: 'Masa' },
                { data: 'status_label', title: 'Durum' },
                { data: 'created_at', title: 'Çağrı Zamanı' },
            ],
        });
    };

    const fetchReports = async () => {
        const query = new URLSearchParams({
            start: document.querySelector('#reportStart')?.value || '',
            end: document.querySelector('#reportEnd')?.value || '',
        });
        const response = await fetch(`api/reports.php?${query.toString()}`);
        const data = await response.json();
        state.reports = data.reports || [];
        selectors.reportsTable.DataTable({
            data: state.reports,
            destroy: true,
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json' },
            columns: [
                { data: 'period', title: 'Dönem' },
                { data: 'orders', title: 'Sipariş Adedi' },
                { data: 'completed_revenue', title: 'Tamamlanan Ciro' },
                { data: 'pending_revenue', title: 'Bekleyen Ciro' },
            ],
        });
    };

    const fetchSettings = async () => {
        const response = await fetch('api/settings.php');
        const data = await response.json();
        state.settings = data.settings || {};
        state.currencies = state.settings.currencies || [];
        state.languages = data.languages || [];
        state.qrPreview = data.qr_preview || state.qrPreview;
        populateLanguageSelect(data.language_files || []);
        renderCurrencies();
        renderLanguages();
        updateQrPreview(state.qrPreview);
    };

    const populateLanguageSelect = (languages) => {
        if (!selectors.defaultLanguage) return;
        selectors.defaultLanguage.innerHTML = '';
        languages.forEach((lang) => {
            const option = document.createElement('option');
            option.value = lang;
            option.innerText = lang.toUpperCase();
            if (state.settings.restaurant?.language === lang) {
                option.selected = true;
            }
            selectors.defaultLanguage.appendChild(option);
        });
    };

    const renderLanguages = () => {
        if (!selectors.languagesTable) return;
        selectors.languagesTable.DataTable({
            data: state.languages,
            destroy: true,
            responsive: true,
            searching: false,
            paging: false,
            info: false,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json' },
            columns: [
                { data: 'code', title: 'Kod', render: (data) => data.toUpperCase() },
                { data: 'label', title: 'Dil Adı' },
            ],
        });
    };

    const renderCurrencies = () => {
        if (!selectors.currenciesTable) return;
        selectors.currenciesTable.DataTable({
            data: state.currencies,
            destroy: true,
            responsive: true,
            searching: false,
            paging: false,
            info: false,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json' },
            columns: [
                { data: 'code', title: 'Kod' },
                { data: 'name', title: 'Para Birimi' },
                { data: 'symbol', title: 'Sembol' },
                {
                    data: 'is_default',
                    title: 'Varsayılan',
                    render: (value, type, row) => value ? '<span class="badge-soft-success">Aktif</span>' : `<button class="btn btn-sm btn-outline-primary" data-set-default="${row.code}">Seç</button>`,
                },
                {
                    data: 'id',
                    title: 'İşlem',
                    render: (id, type, row) => row.is_default ? '' : `<button class="btn btn-sm btn-outline-danger" data-delete-currency="${id}">Sil</button>`,
                },
            ],
        });
    };

    const handleSettingsSubmit = (form, payloadBuilder, onSuccess) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const payload = payloadBuilder(new FormData(form));
            const response = await fetch('api/settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const result = await response.json();
            if (result.error) {
                toast.fire({ icon: 'error', title: result.message || 'Ayarlar kaydedilemedi.' });
                return;
            }
            state.settings = result.settings;
            toast.fire({ icon: 'success', title: result.message });
            if (typeof onSuccess === 'function') {
                await onSuccess(result);
            }
        });
    };

    const updateQrPreview = (url) => {
        if (selectors.qrPreview) {
            selectors.qrPreview.src = url;
        }
    };

    const initDropzones = () => {
        const dropzones = document.querySelectorAll('[data-dropzone]');
        dropzones.forEach((element) => {
            const dz = new Dropzone(element, {
                url: 'api/upload.php',
                paramName: 'file',
                maxFiles: 1,
                acceptedFiles: 'image/*',
                addRemoveLinks: true,
                dictDefaultMessage: element.dataset.placeholder || 'Dosyayı buraya sürükleyin',
            });

            dz.on('success', (file, response) => {
                if (!response.success) {
                    toast.fire({ icon: 'error', title: response.message || 'Dosya yüklenemedi.' });
                    return;
                }
                const input = document.querySelector(element.dataset.target);
                if (input) {
                    input.value = response.path;
                }
                toast.fire({ icon: 'success', title: 'Dosya yüklendi.' });
            });

            dz.on('error', () => {
                toast.fire({ icon: 'error', title: 'Dosya yüklenirken hata oluştu.' });
            });
        });
    };

    const bindOrderFilters = () => {
        selectors.orderStatusFilters.forEach((button) => {
            button.addEventListener('click', async () => {
                selectors.orderStatusFilters.forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                state.orderStatus = button.dataset.status;
                await fetchOrders();
            });
        });
    };

    const bindReportButtons = () => {
        selectors.reportButtons.forEach((button) => {
            button.addEventListener('click', async () => {
                state.period = button.dataset.report;
                selectors.reportButtons.forEach((btn) => btn.classList.remove('active'));
                button.classList.add('active');
                await fetchDashboard();
            });
        });
        document.querySelector('#exportPdf')?.addEventListener('click', () => exportReport('pdf'));
        document.querySelector('#exportExcel')?.addEventListener('click', () => exportReport('excel'));
        document.querySelector('#reportStart')?.addEventListener('change', fetchReports);
        document.querySelector('#reportEnd')?.addEventListener('change', fetchReports);
    };

    const exportReport = async (format) => {
        const query = new URLSearchParams({
            start: document.querySelector('#reportStart')?.value || '',
            end: document.querySelector('#reportEnd')?.value || '',
            format,
        });
        const response = await fetch(`api/export.php?${query.toString()}`);
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `rapor.${format === 'pdf' ? 'pdf' : 'xlsx'}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    };

    const bindLanguageActions = () => {
        selectors.saveLanguage?.addEventListener('click', async () => {
            const formData = new FormData(selectors.languageForm);
            const payload = Object.fromEntries(formData.entries());
            try {
                const translations = JSON.parse(payload.translations);
                const response = await fetch('api/languages.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        code: payload.code,
                        label: payload.label,
                        translations,
                    }),
                });
                const result = await response.json();
                if (result.error) {
                    throw new Error(result.message);
                }
                state.languages = result.languages;
                renderLanguages();
                toast.fire({ icon: 'success', title: result.message });
                bootstrap.Modal.getInstance(selectors.languageModal)?.hide();
            } catch (error) {
                toast.fire({ icon: 'error', title: error.message || 'Dil kaydedilemedi.' });
            }
        });
    };

    const bindCurrencyActions = () => {
        selectors.saveCurrency?.addEventListener('click', async () => {
            const formData = new FormData(selectors.currencyForm);
            const payload = Object.fromEntries(formData.entries());
            payload.is_default = Number(payload.is_default);
            const response = await fetch('api/currencies.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const result = await response.json();
            if (result.error) {
                toast.fire({ icon: 'error', title: result.message || 'Para birimi kaydedilemedi.' });
                return;
            }
            state.currencies = result.currencies;
            renderCurrencies();
            toast.fire({ icon: 'success', title: result.message });
            bootstrap.Modal.getInstance(selectors.currencyModal)?.hide();
        });

        selectors.currenciesTable?.off('click', 'button[data-set-default]');
        selectors.currenciesTable?.off('click', 'button[data-delete-currency]');

        selectors.currenciesTable?.on('click', 'button[data-set-default]', async (event) => {
            const code = event.currentTarget.dataset.setDefault;
            const response = await fetch('api/currencies.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'default', code }),
            });
            const result = await response.json();
            if (!result.error) {
                state.currencies = result.currencies;
                renderCurrencies();
                toast.fire({ icon: 'success', title: result.message });
            }
        });

        selectors.currenciesTable?.on('click', 'button[data-delete-currency]', async (event) => {
            const id = event.currentTarget.dataset.deleteCurrency;
            const response = await fetch(`api/currencies.php?id=${id}`, { method: 'DELETE' });
            const result = await response.json();
            if (!result.error) {
                state.currencies = result.currencies;
                renderCurrencies();
                toast.fire({ icon: 'success', title: result.message });
            }
        });
    };

    const bindLogout = () => {
        selectors.logoutButton?.addEventListener('click', async () => {
            await fetch('api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' }),
            });
            window.location.href = 'login.php';
        });
    };

    const bindQrPreview = () => {
        selectors.qrForm?.addEventListener('change', () => {
            const formData = new FormData(selectors.qrForm);
            const params = new URLSearchParams({
                token: formData.get('token') || '',
                type: 'url',
                url: `${window.location.origin}/menu.php`,
                width: formData.get('width') || 400,
                height: formData.get('height') || 400,
                color: formData.get('color') || '#000000',
                background: formData.get('background') || '#ffffff',
                format: formData.get('format') || 'png',
                background_transparent: formData.get('transparent') === 'true' ? 'true' : 'false',
            });
            updateQrPreview(`${window.APP_STATE?.qrPreview?.split('?')[0] || 'https://qrcode.noasoft.org/api/v1/qr'}?${params.toString()}`);
        });
    };

    const init = async () => {
        const themeColor = document.querySelector('.dashboard')?.dataset.themeColor;
        if (themeColor) {
            document.documentElement.style.setProperty('--theme-color', themeColor);
        }

        bindNavigation();
        bindOrderFilters();
        bindReportButtons();
        bindLanguageActions();
        bindCurrencyActions();
        bindLogout();
        bindQrPreview();
        initDropzones();

        document.querySelector('[data-report="weekly"]')?.classList.add('active');

        if (selectors.generalSettingsForm) {
            handleSettingsSubmit(selectors.generalSettingsForm, (formData) => ({
                restaurant: {
                    name: formData.get('name'),
                    phone: formData.get('phone'),
                    description: formData.get('description'),
                    address: formData.get('address'),
                    currency: formData.get('currency'),
                    timezone: formData.get('timezone'),
                    language: formData.get('language'),
                    theme_color: formData.get('theme_color'),
                },
            }));
        }

        if (selectors.brandingForm) {
            handleSettingsSubmit(selectors.brandingForm, (formData) => ({
                branding: {
                    logo: formData.get('logo'),
                    favicon: formData.get('favicon'),
                    qr_logo: formData.get('qr_logo'),
                },
            }), fetchSettings);
        }

        if (selectors.qrForm) {
            handleSettingsSubmit(selectors.qrForm, (formData) => ({
                qr: {
                    token: formData.get('token'),
                    width: Number(formData.get('width') || 400),
                    height: Number(formData.get('height') || 400),
                    format: formData.get('format'),
                    transparent: formData.get('transparent') === 'true',
                    color: formData.get('color'),
                    background: formData.get('background'),
                },
            }), fetchSettings);
        }

        await Promise.all([
            fetchDashboard(),
            fetchTables(),
            fetchOrders(),
            fetchWaiterCalls(),
            fetchReports(),
            fetchSettings(),
        ]);
    };

    return { init };
})();

document.addEventListener('DOMContentLoaded', () => AdminApp.init());
