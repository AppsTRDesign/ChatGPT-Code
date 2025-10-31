Dropzone.autoDiscover = false;
const AdminApp = (() => {
    const state = {
        tables: [],
        orders: [],
        waiterCalls: [],
        settings: {},
        languages: [],
        charts: null,
    };

    const selectors = {
        summaryCards: document.querySelectorAll('[data-summary]'),
        chartCanvas: document.querySelector('#ordersChart'),
        tablesTable: $('#tablesTable'),
        ordersTable: $('#ordersTable'),
        waiterTable: $('#waiterTable'),
        languageSelect: document.querySelector('#defaultLanguage'),
    };

    const swalToast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
    });

    const loadDashboard = async () => {
        const response = await fetch('api/dashboard.php');
        const data = await response.json();
        const { summary, charts } = data;

        selectors.summaryCards.forEach((card) => {
            const key = card.getAttribute('data-summary');
            if (summary[key] !== undefined) {
                card.querySelector('strong').innerText = summary[key];
            }
        });

        renderChart(charts);
    };

    const loadTables = async () => {
        const response = await fetch('api/tables.php');
        const data = await response.json();
        state.tables = data.tables;

        selectors.tablesTable.DataTable({
            data: state.tables,
            destroy: true,
            responsive: true,
            columns: [
                { data: 'name', title: 'Masa' },
                { data: 'status', title: 'Durum' },
                { data: 'qr_url', title: 'QR URL' },
            ],
        });
    };

    const loadOrders = async () => {
        const response = await fetch('api/orders.php');
        const data = await response.json();
        state.orders = data.orders;

        selectors.ordersTable.DataTable({
            data: state.orders,
            destroy: true,
            responsive: true,
            columns: [
                { data: 'id', title: 'ID' },
                { data: 'table', title: 'Masa' },
                { data: 'status', title: 'Durum' },
                { data: 'total', title: 'Toplam' },
                { data: 'created_at', title: 'Tarih' },
            ],
        });
    };

    const loadWaiterCalls = async () => {
        const response = await fetch('api/waiter-calls.php');
        const data = await response.json();
        state.waiterCalls = data.calls;

        selectors.waiterTable.DataTable({
            data: state.waiterCalls,
            destroy: true,
            responsive: true,
            columns: [
                { data: 'table', title: 'Masa' },
                { data: 'status', title: 'Durum' },
                { data: 'created_at', title: 'Tarih' },
            ],
        });
    };

    const loadSettings = async () => {
        const response = await fetch('api/settings.php');
        const data = await response.json();
        state.settings = data.settings;
        state.languages = data.languages;

        selectors.languageSelect.innerHTML = '';
        state.languages.forEach((lang) => {
            const option = document.createElement('option');
            option.value = lang;
            option.innerText = lang.toUpperCase();
            if (lang === state.settings.restaurant.language) {
                option.selected = true;
            }
            selectors.languageSelect.appendChild(option);
        });
    };

    const renderChart = (chartData) => {
        if (!selectors.chartCanvas) return;
        if (state.charts) {
            state.charts.destroy();
        }

        const datasets = chartData.datasets.map((dataset) => ({
            ...dataset,
            stack: 'stack1',
            borderRadius: 12,
        }));

        state.charts = new Chart(selectors.chartCanvas, {
            type: 'bar',
            data: {
                labels: chartData.labels,
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
                    legend: {
                        position: 'bottom',
                    },
                },
            },
        });
    };

    const bindEvents = () => {
        const settingsForm = document.querySelector('#settingsForm');
        if (settingsForm) {
            settingsForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const formData = new FormData(settingsForm);
                const payload = Object.fromEntries(formData.entries());

                const response = await fetch('api/settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        restaurant: {
                            name: payload.name,
                            phone: payload.phone,
                            description: payload.description,
                            address: payload.address,
                            currency: payload.currency,
                            timezone: payload.timezone,
                            language: payload.language,
                            theme_color: payload.theme_color,
                        },
                        qr: {
                            token: payload.qr_token,
                            width: Number(payload.qr_width),
                            height: Number(payload.qr_height),
                            format: payload.qr_format,
                            transparent: payload.qr_transparent === 'true',
                            color: payload.qr_color,
                            background: payload.qr_background,
                            logo: payload.qr_logo,
                        },
                    }),
                });

                const result = await response.json();
                if (result.settings) {
                    swalToast.fire({ icon: 'success', title: result.message });
                } else {
                    swalToast.fire({ icon: 'error', title: 'Ayarlar kaydedilemedi' });
                }
            });
        }

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
                if (response.success) {
                    const input = document.querySelector(element.dataset.target);
                    if (input) {
                        input.value = response.path;
                    }
                    swalToast.fire({ icon: 'success', title: 'Dosya yüklendi' });
                }
            });

            dz.on('error', () => {
                swalToast.fire({ icon: 'error', title: 'Dosya yüklenirken hata oluştu' });
            });
        });
    };

    const init = async () => {
        await Promise.all([loadDashboard(), loadTables(), loadOrders(), loadWaiterCalls(), loadSettings()]);
        bindEvents();
    };

    return { init };
})();

document.addEventListener('DOMContentLoaded', () => {
    AdminApp.init();
});
