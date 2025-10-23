if (window.Dropzone) {
    Dropzone.autoDiscover = false;
}

const escapeHtml = (value) => {
    if (value === null || value === undefined) {
        return '';
    }
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

const registerTurkishFont = (doc) => {
    if (!doc || !window.APP_FONTS || !window.APP_FONTS.DejaVuSans) {
        return;
    }
    const available = typeof doc.getFontList === 'function' ? doc.getFontList() : {};
    if (available.DejaVuSans) {
        return;
    }
    const fontFile = 'DejaVuSans.ttf';
    doc.addFileToVFS(fontFile, window.APP_FONTS.DejaVuSans);
    doc.addFont(fontFile, 'DejaVuSans', 'normal');
    doc.addFont(fontFile, 'DejaVuSans', 'bold');
};

const formatDateTime = (value) => {
    if (!value) {
        return '-';
    }
    const date = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
        return escapeHtml(value);
    }
    return date.toLocaleString('tr-TR');
};

const formatDateOnly = (value) => {
    if (!value) {
        return '-';
    }
    const normalised = value.length === 10 ? `${value}T00:00:00` : value.replace(' ', 'T');
    const date = new Date(normalised);
    if (Number.isNaN(date.getTime())) {
        return escapeHtml(value);
    }
    return date.toLocaleDateString('tr-TR');
};

const refreshTable = (tableId) => {
    if (!tableId || !window.jQuery) {
        return;
    }
    try {
        window.jQuery(`#${tableId}`).bootstrapTable('refresh');
    } catch (error) {
        console.warn('Tablo yenilenemedi', error);
    }
};

const subscriptionStatusMap = {
    active: { label: 'Aktif', class: 'bg-success' },
    pending: { label: 'İşleniyor', class: 'bg-secondary' },
    awaiting_payment: { label: 'Ödeme Bekliyor', class: 'bg-warning text-dark' },
    payment_missing: { label: 'Eksik Ödeme', class: 'bg-danger' },
    failed: { label: 'Başarısız', class: 'bg-danger' },
    rejected: { label: 'Reddedildi', class: 'bg-danger' },
    cancelled: { label: 'İptal Edildi', class: 'bg-secondary' },
};

const paymentStatusMap = {
    pending: { label: 'Bekliyor', class: 'bg-warning text-dark' },
    approved: { label: 'Onaylandı', class: 'bg-success' },
    rejected: { label: 'Reddedildi', class: 'bg-danger' },
};

window.appHandlers = {
    packageResponseHandler: (response) => response,
    packagePriceFormatter: (value) => {
        const price = Number(value || 0);
        return `${price.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₺`;
    },
    packageStatusFormatter: (value) => {
        const active = value === true || value === 1 || value === '1';
        const label = active ? 'Aktif' : 'Pasif';
        const badge = active ? 'bg-success' : 'bg-secondary';
        return `<span class="badge rounded-pill ${badge}">${label}</span>`;
    },
    packageActionsFormatter: (value, row) => {
        const table = document.getElementById('packagesTable');
        const csrf = table ? table.dataset.csrf || '' : '';
        const toggleLabel = row.is_active ? 'Pasif Yap' : 'Aktif Yap';
        const id = encodeURIComponent(row.id);
        const buttons = [
            `<button type="button" class="btn btn-sm btn-outline-light" data-ajax-action data-url="/admin/package-toggle" data-id="${id}" data-table="packagesTable" data-csrf="${csrf}">${toggleLabel}</button>`,
            `<a href="/admin/package-edit?id=${id}" class="btn btn-sm btn-outline-primary">Düzenle</a>`,
            `<button type="button" class="btn btn-sm btn-outline-danger" data-ajax-action data-action-value="delete" data-url="/admin/package-delete" data-id="${id}" data-table="packagesTable" data-csrf="${csrf}" data-confirm="Paket silinecek. Onaylıyor musunuz?">Sil</button>`,
        ];
        return `<div class="d-flex flex-wrap gap-2 justify-content-end">${buttons.join('')}</div>`;
    },
    userResponseHandler: (response) => response,
    roleFormatter: (value, row) => {
        const role = value === 'admin' ? 'Admin' : 'Müşteri';
        const badgeClass = value === 'admin' ? 'bg-danger' : 'bg-info';
        return `<span class="badge rounded-pill ${badgeClass}">${escapeHtml(role)}</span>`;
    },
    userActionsFormatter: (value, row) => {
        const table = document.getElementById('usersTable');
        const csrf = table ? table.dataset.csrf || '' : '';
        let output = `<a href="/admin/user-edit?id=${encodeURIComponent(row.id)}" class="btn btn-sm btn-outline-primary">Düzenle</a>`;
        if (!row.self) {
            output += `<button type="button" class="btn btn-sm btn-outline-danger" data-ajax-action data-action-value="delete" data-url="/admin/user-delete" data-id="${row.id}" data-table="usersTable" data-csrf="${csrf}" data-confirm="Bu üyeyi silmek istediğinizden emin misiniz?">Sil</button>`;
        }
        return `<div class="d-flex flex-wrap gap-2 justify-content-end">${output}</div>`;
    },
    purchaseResponseHandler: (response) => response,
    paymentFormatter: (value) => (value === 'iyzico' ? 'Kredi Kartı (İyzico)' : 'Banka Havalesi'),
    purchaseStatusFormatter: (value) => {
        const status = subscriptionStatusMap[value] || { label: value, class: 'bg-secondary' };
        return `<span class="badge rounded-pill ${status.class}">${escapeHtml(status.label)}</span>`;
    },
    noteFormatter: (value) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        return `<span class="text-break">${escapeHtml(value).replace(/\n/g, '<br>')}</span>`;
    },
    purchaseErrorFormatter: (value, row) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        const note = escapeHtml(value).replace(/\n/g, '<br>');
        const time = row.last_error_at ? `<div class="small text-white-50 mt-1">${escapeHtml(formatDateTime(row.last_error_at))}</div>` : '';
        return `<span class="text-danger">${note}</span>${time}`;
    },
    purchaseDateFormatter: (value, row) => {
        const created = formatDateTime(row.created_at);
        const activated = row.activated_at ? formatDateTime(row.activated_at) : null;
        const expires = row.expires_at ? formatDateTime(row.expires_at) : null;
        let html = `<div class="small text-white-50">Talep: ${created}</div>`;
        if (activated) {
            html += `<div class="small text-white-50">Aktif: ${activated}</div>`;
        }
        if (expires) {
            html += `<div class="small text-white-50">Bitiş: ${expires}</div>`;
        }
        return html;
    },
    purchaseActionsFormatter: (value, row) => {
        const table = document.getElementById('purchasesTable');
        const csrf = table ? table.dataset.csrf || '' : '';
        const buttons = [];
        if (row.status !== 'active') {
            buttons.push(`<button type="button" class="btn btn-sm btn-primary" data-ajax-action data-action-value="activate" data-url="/admin/purchases" data-id="${row.id}" data-table="purchasesTable" data-csrf="${csrf}" data-confirm="Bu paketi onaylamak istediğinizden emin misiniz?">Onayla</button>`);
        }
        if (row.payment_method === 'bank' && row.status !== 'active') {
            buttons.push(`<button type="button" class="btn btn-sm btn-outline-light" data-ajax-action data-action-value="awaiting" data-url="/admin/purchases" data-id="${row.id}" data-table="purchasesTable" data-csrf="${csrf}">Ödeme Bekleniyor</button>`);
            buttons.push(`<button type="button" class="btn btn-sm btn-outline-warning" data-ajax-action data-action-value="missing" data-url="/admin/purchases" data-id="${row.id}" data-table="purchasesTable" data-csrf="${csrf}">Eksik Ödeme</button>`);
        }
        if (row.status !== 'rejected' && row.status !== 'cancelled' && row.status !== 'active') {
            buttons.push(`<button type="button" class="btn btn-sm btn-outline-danger" data-ajax-action data-action-value="reject" data-url="/admin/purchases" data-id="${row.id}" data-table="purchasesTable" data-csrf="${csrf}" data-confirm="Bu satın alımı reddetmek istediğinizden emin misiniz?">Reddet</button>`);
        }
        if (row.status === 'active') {
            buttons.push(`<button type="button" class="btn btn-sm btn-outline-light" data-ajax-action data-action-value="cancel" data-url="/admin/purchases" data-id="${row.id}" data-table="purchasesTable" data-csrf="${csrf}" data-confirm="Aktif paketi iptal etmek istediğinizden emin misiniz?">İptal Et</button>`);
        }
        if (!buttons.length) {
            return '<span class="text-white-50">-</span>';
        }
        return `<div class="d-flex flex-wrap gap-2 justify-content-end">${buttons.join('')}</div>`;
    },
    paymentsResponseHandler: (response) => response,
    paymentStatusFormatter: (value) => {
        const status = paymentStatusMap[value] || { label: value, class: 'bg-secondary' };
        return `<span class="badge rounded-pill ${status.class}">${escapeHtml(status.label)}</span>`;
    },
    paymentActionsFormatter: (value, row) => {
        if (row.status !== 'pending') {
            return '<span class="text-white-50">-</span>';
        }
        const table = document.getElementById('paymentsTable');
        const csrf = table ? table.dataset.csrf || '' : '';
        return `<div class="d-flex flex-wrap gap-2 justify-content-end">
            <button type="button" class="btn btn-sm btn-success" data-ajax-action data-action-value="approve" data-url="/admin/payments" data-id="${row.id}" data-table="paymentsTable" data-csrf="${csrf}">Onayla</button>
            <button type="button" class="btn btn-sm btn-outline-danger" data-ajax-action data-action-value="reject" data-url="/admin/payments" data-id="${row.id}" data-table="paymentsTable" data-csrf="${csrf}" data-confirm="Bu bildirimi reddetmek istediğinizden emin misiniz?">Reddet</button>
        </div>`;
    },
    usageResponseHandler: (response) => response,
    usageStatusFormatter: (value) => {
        const status = value === 'success'
            ? { label: 'Başarılı', class: 'bg-success' }
            : value === 'error'
                ? { label: 'Hata', class: 'bg-danger' }
                : { label: escapeHtml(value), class: 'bg-secondary' };
        return `<span class="badge rounded-pill ${status.class}">${escapeHtml(status.label)}</span>`;
    },
    clientUsageResponseHandler: (response) => response,
    clientUsageDateFormatter: (value) => formatDateOnly(value),
    clientTokenResponseHandler: (response) => response,
    clientTokenStatusFormatter: (value) => {
        const status = value === 'revoked'
            ? { label: 'Pasif', class: 'bg-danger' }
            : { label: 'Aktif', class: 'bg-success' };
        return `<span class="badge rounded-pill ${status.class}">${status.label}</span>`;
    },
    clientTokenValueFormatter: (value) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        return `<span class="d-inline-block w-100 text-break small font-monospace">${escapeHtml(value)}</span>`;
    },
    clientTokenDateFormatter: (value) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        return `<span class="small">${formatDateTime(value)}</span>`;
    },
    clientTokenActionsFormatter: (value, row) => {
        const table = document.getElementById('tokensTable');
        if (!table) {
            return '';
        }
        const csrf = table.dataset.csrf || '';
        const forms = [];
        if (row.status === 'revoked') {
            forms.push(`
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="${escapeHtml(csrf)}">
                    <input type="hidden" name="token_id" value="${escapeHtml(row.id)}">
                    <input type="hidden" name="action" value="restore">
                    <button type="submit" class="btn btn-sm btn-outline-success">Aktif Et</button>
                </form>
            `);
        } else {
            forms.push(`
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="${escapeHtml(csrf)}">
                    <input type="hidden" name="token_id" value="${escapeHtml(row.id)}">
                    <input type="hidden" name="action" value="revoke">
                    <button type="submit" class="btn btn-sm btn-outline-light" data-confirm="Token pasif edilsin mi?">Pasif Et</button>
                </form>
            `);
        }
        forms.push(`
            <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token" value="${escapeHtml(csrf)}">
                <input type="hidden" name="token_id" value="${escapeHtml(row.id)}">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Token tamamen silinecek. Onaylıyor musunuz?">Sil</button>
            </form>
        `);
        return `<div class="d-flex flex-wrap gap-2 justify-content-end">${forms.join('')}</div>`;
    },
    clientPurchaseHistoryResponse: (response) => response,
    clientPurchaseStatusFormatter: (value, row) => {
        const fallbackLabel = row && row.status_label ? row.status_label : value;
        const map = subscriptionStatusMap[value] || { label: fallbackLabel, class: 'bg-secondary' };
        const label = row && row.status_label ? row.status_label : map.label;
        return `<span class="badge rounded-pill ${map.class}">${escapeHtml(label)}</span>`;
    },
    clientPurchasePaymentFormatter: (value) => {
        if (value === 'iyzico') {
            return '<span class="badge bg-info text-dark">Kredi Kartı (İyzico)</span>';
        }
        if (value === 'bank') {
            return '<span class="badge bg-primary">Banka Havalesi</span>';
        }
        return `<span class="badge bg-secondary">${escapeHtml(value || '-')}</span>`;
    },
    clientPurchaseDateFormatter: (value) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        return `<span class="small">${formatDateTime(value)}</span>`;
    },
};

let usageChart;
let usageMetrics = [];
let dashboardTrafficChart;
let dashboardRevenueChart;
let clientUsageChart;
let clientUsageMetrics = [];

const updateUsageChart = (labels, data) => {
    const canvas = document.getElementById('usageChart');
    if (!canvas || !window.Chart) {
        return;
    }

    const chartData = {
        labels,
        datasets: [
            {
                label: 'Toplam İstek',
                data,
                backgroundColor: 'rgba(56, 189, 248, 0.65)',
                borderColor: '#38bdf8',
                borderWidth: 1.5,
                borderRadius: 8,
                maxBarThickness: 36,
            },
        ],
    };

    const maxValue = data.length ? Math.max(...data) : 0;
    const stepSize = maxValue <= 6 ? 1 : Math.ceil(maxValue / 5);

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: 8 },
        scales: {
            x: {
                ticks: { color: '#cbd5f5', maxRotation: 0, minRotation: 0, autoSkip: true },
                grid: { color: 'rgba(148, 163, 184, 0.08)', drawBorder: false },
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#cbd5f5',
                    callback: (value) => (Number.isInteger(value) ? value : ''),
                    stepSize,
                },
                grid: { color: 'rgba(148, 163, 184, 0.12)', drawBorder: false },
            },
        },
        plugins: {
            legend: {
                labels: { color: '#e2e8f0' },
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.88)',
                borderColor: 'rgba(56, 189, 248, 0.4)',
                borderWidth: 1,
                titleColor: '#f8fafc',
                bodyColor: '#f8fafc',
            },
        },
    };

    if (!usageChart) {
        usageChart = new Chart(canvas, {
            type: 'bar',
            data: chartData,
            options: chartOptions,
        });
    } else {
        usageChart.data = chartData;
        usageChart.options = chartOptions;
        usageChart.update();
    }
};

const updateUsageSummary = (rows) => {
    const container = document.getElementById('usageSummary');
    if (!container) {
        return;
    }
    if (!rows.length) {
        container.innerHTML = '<li class="text-white-50">Veri bulunamadı.</li>';
        return;
    }

    const totals = rows.map((row) => Number(row.total || 0));
    const total = totals.reduce((sum, value) => sum + value, 0);
    const peak = Math.max(...totals);
    const average = Math.round(total / totals.length);
    const last = totals[0];

    container.innerHTML = `
        <li class="mb-2"><strong>Toplam İstek:</strong> ${total}</li>
        <li class="mb-2"><strong>Güncel Dönem:</strong> ${last}</li>
        <li class="mb-2"><strong>Ortalama:</strong> ${average}</li>
        <li class="mb-0"><strong>Zirve:</strong> ${peak}</li>
    `;
};

const loadUsageMetrics = async (range) => {
    try {
        const response = await fetch(`/admin/data/usage-metrics?range=${encodeURIComponent(range)}`, {
            headers: { Accept: 'application/json' },
        });
        const json = await response.json();
        usageMetrics = json.rows || [];
        const labels = usageMetrics.map((row) => row.label).reverse();
        const values = usageMetrics.map((row) => Number(row.total || 0)).reverse();
        updateUsageChart(labels, values);
        updateUsageSummary(usageMetrics);
    } catch (error) {
        console.error('Kullanım metrikleri yüklenemedi', error);
    }
};

const exportUsage = (format) => {
    if (!usageMetrics.length) {
        Swal.fire({ icon: 'warning', title: 'İndirilecek veri bulunamadı.', confirmButtonColor: '#0d6efd' });
        return;
    }

    const rows = usageMetrics.map((row) => [row.label, Number(row.total || 0)]);

    if (format === 'pdf' && window.jspdf && window.jspdf.jsPDF) {
        const doc = new window.jspdf.jsPDF({ orientation: 'landscape' });
        registerTurkishFont(doc);
        doc.setFont('DejaVuSans', 'bold');
        doc.setFontSize(16);
        doc.text('API Kullanım Raporu', 14, 18);
        doc.setFont('DejaVuSans', 'normal');
        doc.autoTable({
            head: [['Dönem', 'Toplam İstek']],
            body: rows,
            startY: 26,
            styles: {
                font: 'DejaVuSans',
                fontStyle: 'normal',
                fillColor: [13, 17, 35],
                textColor: [241, 246, 249],
            },
            headStyles: {
                font: 'DejaVuSans',
                fontStyle: 'bold',
                fillColor: [13, 110, 253],
                textColor: 255,
            },
            alternateRowStyles: {
                fillColor: [24, 33, 58],
            },
        });
        doc.save('api-raporu.pdf');
        return;
    }

    if (format === 'excel' && window.XLSX) {
        const worksheet = window.XLSX.utils.aoa_to_sheet([
            ['Dönem', 'Toplam İstek'],
            ...rows,
        ]);
        const workbook = window.XLSX.utils.book_new();
        window.XLSX.utils.book_append_sheet(workbook, worksheet, 'Rapor');
        window.XLSX.writeFile(workbook, 'api-raporu.xlsx');
    }
};

const renderClientUsageChart = (rows) => {
    const canvas = document.getElementById('clientUsageChart');
    if (!canvas || !window.Chart) {
        return;
    }

    const labels = rows.map((row) => row.label).reverse();
    const totals = rows.map((row) => Number(row.total || 0)).reverse();

    const data = {
        labels,
        datasets: [
            {
                label: 'Toplam İstek',
                data: totals,
                borderColor: '#60a5fa',
                backgroundColor: 'rgba(96, 165, 250, 0.18)',
                fill: true,
                tension: 0.35,
            },
        ],
    };

    const maxValue = totals.length ? Math.max(...totals) : 0;
    const stepSize = maxValue <= 6 ? 1 : Math.ceil(maxValue / 5);

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        layout: { padding: 6 },
        elements: {
            point: { radius: 4, hoverRadius: 6 },
            line: { borderWidth: 3, borderCapStyle: 'round' },
        },
        plugins: {
            legend: { labels: { color: '#e2e8f0', font: { family: 'Inter, "Segoe UI", sans-serif', size: 13 } } },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.88)',
                borderColor: 'rgba(96, 165, 250, 0.4)',
                borderWidth: 1,
                titleColor: '#f8fafc',
                bodyColor: '#f8fafc',
            },
        },
        scales: {
            x: { ticks: { color: '#cbd5f5', maxRotation: 0, minRotation: 0, autoSkip: true }, grid: { color: 'rgba(148, 163, 184, 0.16)', drawBorder: false } },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#cbd5f5',
                    callback: (value) => (Number.isInteger(value) ? value : ''),
                    stepSize: stepSize,
                },
                grid: { color: 'rgba(148, 163, 184, 0.12)', drawBorder: false },
            },
        },
    };

    if (!clientUsageChart) {
        clientUsageChart = new Chart(canvas, {
            type: 'line',
            data,
            options,
        });
    } else {
        clientUsageChart.data = data;
        clientUsageChart.options = options;
        clientUsageChart.update();
    }
};

const updateClientUsageSummary = (summary) => {
    const container = document.getElementById('clientUsageSummary');
    if (!container) {
        return;
    }

    if (!summary || (Number(summary.total) === 0 && Number(summary.peak) === 0 && Number(summary.latest) === 0)) {
        container.innerHTML = '<li class="text-white-50">Henüz kullanım verisi yok.</li>';
        return;
    }

    const rows = [];
    rows.push(`<li class="mb-2"><strong>Toplam İstek:</strong> ${Number(summary.total || 0)}</li>`);
    rows.push(`<li class="mb-2"><strong>Son Dönem:</strong> ${Number(summary.latest || 0)}</li>`);
    rows.push(`<li class="mb-2"><strong>Günlük Ortalama:</strong> ${Number(summary.average || 0)}</li>`);
    rows.push(`<li class="mb-0"><strong>Zirve:</strong> ${Number(summary.peak || 0)}</li>`);
    container.innerHTML = rows.join('');
};

const loadClientUsageMetrics = async () => {
    if (!window.clientUsageConfig || !window.clientUsageConfig.endpoint) {
        return;
    }

    try {
        const response = await fetch(window.clientUsageConfig.endpoint, { headers: { Accept: 'application/json' } });
        const json = await response.json();
        clientUsageMetrics = json.rows || [];
        renderClientUsageChart(clientUsageMetrics);
        updateClientUsageSummary(json.summary || null);
    } catch (error) {
        console.error('Kullanıcı kullanım verileri yüklenemedi', error);
        updateClientUsageSummary(null);
    }
};

const renderDashboardTraffic = (labels, usage, registrations) => {
    const canvas = document.getElementById('dashboardTrafficChart');
    if (!canvas || !window.Chart) {
        return;
    }

    const data = {
        labels,
        datasets: [
            {
                label: 'API İstekleri',
                data: usage,
                borderColor: '#38bdf8',
                backgroundColor: 'rgba(56, 189, 248, 0.2)',
                fill: true,
                tension: 0.35,
            },
            {
                label: 'Yeni Üyeler',
                data: registrations,
                borderColor: '#a855f7',
                backgroundColor: 'rgba(168, 85, 247, 0.18)',
                fill: true,
                tension: 0.35,
            },
        ],
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#e2e8f0',
                },
            },
        },
        scales: {
            x: {
                ticks: { color: '#cbd5f5' },
                grid: { color: 'rgba(148, 163, 184, 0.12)' },
            },
            y: {
                ticks: { color: '#cbd5f5' },
                grid: { color: 'rgba(148, 163, 184, 0.12)' },
                beginAtZero: true,
                precision: 0,
            },
        },
    };

    if (!dashboardTrafficChart) {
        dashboardTrafficChart = new Chart(canvas, {
            type: 'line',
            data,
            options,
        });
    } else {
        dashboardTrafficChart.data = data;
        dashboardTrafficChart.options = options;
        dashboardTrafficChart.update();
    }
};

const renderDashboardRevenue = (labels, totals) => {
    const canvas = document.getElementById('dashboardRevenueChart');
    if (!canvas || !window.Chart) {
        return;
    }

    const data = {
        labels,
        datasets: [
            {
                label: 'Onaylı Gelir (₺)',
                data: totals,
                backgroundColor: 'rgba(34, 197, 94, 0.6)',
                borderColor: '#22c55e',
                borderWidth: 1.5,
            },
        ],
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                ticks: { color: '#cbd5f5' },
                grid: { color: 'rgba(148, 163, 184, 0.08)' },
            },
            y: {
                ticks: { color: '#cbd5f5' },
                grid: { color: 'rgba(148, 163, 184, 0.12)' },
                beginAtZero: true,
            },
        },
        plugins: {
            legend: {
                labels: { color: '#e2e8f0' },
            },
        },
    };

    if (!dashboardRevenueChart) {
        dashboardRevenueChart = new Chart(canvas, {
            type: 'bar',
            data,
            options,
        });
    } else {
        dashboardRevenueChart.data = data;
        dashboardRevenueChart.options = options;
        dashboardRevenueChart.update();
    }
};

const updateDashboardSummary = (summary) => {
    const container = document.getElementById('dashboardSummary');
    if (!container) {
        return;
    }

    if (!summary) {
        container.innerHTML = '<li class="text-white-50">Özet verisi bulunamadı.</li>';
        return;
    }

    const rows = [];
    if (typeof summary.totalRevenue !== 'undefined') {
        rows.push(`<li class="mb-2"><strong>Toplam Onaylı Gelir:</strong> ${Number(summary.totalRevenue).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₺</li>`);
    }
    if (typeof summary.pendingPurchases !== 'undefined') {
        rows.push(`<li class="mb-2"><strong>Bekleyen Satın Alım:</strong> ${Number(summary.pendingPurchases)}</li>`);
    }
    if (typeof summary.failedPurchases !== 'undefined') {
        rows.push(`<li class="mb-2"><strong>Başarısız Ödeme:</strong> ${Number(summary.failedPurchases)}</li>`);
    }
    if (typeof summary.newClients7 !== 'undefined') {
        rows.push(`<li class="mb-0"><strong>Son 7 Gün Yeni Üye:</strong> ${Number(summary.newClients7)}</li>`);
    }

    container.innerHTML = rows.join('') || '<li class="text-white-50">Özet verisi bulunamadı.</li>';
};

const loadDashboardMetrics = async () => {
    if (!window.dashboardConfig || !window.dashboardConfig.endpoint) {
        return;
    }

    try {
        const response = await fetch(window.dashboardConfig.endpoint, { headers: { Accept: 'application/json' } });
        const json = await response.json();
        if (json.usage && json.registrations) {
            const labels = json.usage.map((row) => row.label);
            const usageData = json.usage.map((row) => Number(row.total || 0));
            const registrationData = json.registrations.map((row) => Number(row.total || 0));
            renderDashboardTraffic(labels, usageData, registrationData);
        }
        if (json.revenue) {
            const labels = json.revenue.map((row) => row.label);
            const totals = json.revenue.map((row) => Number(row.total || 0));
            renderDashboardRevenue(labels, totals);
        }
        updateDashboardSummary(json.summary || null);
    } catch (error) {
        console.error('Gösterge paneli verileri yüklenemedi', error);
    }
};

const submitAjaxAction = async (button) => {
    const url = button.dataset.url;
    const tableId = button.dataset.table;
    const actionValue = button.dataset.actionValue;
    const csrf = button.dataset.csrf || (tableId && document.getElementById(tableId)?.dataset.csrf) || '';
    const id = button.dataset.id;

    if (!url || !id) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('id', id);
    if (csrf) {
        formData.append('csrf_token', csrf);
    }
    if (actionValue && actionValue !== 'delete') {
        formData.append('action', actionValue);
    }

    const confirmMessage = button.dataset.confirm;
    const proceed = async () => {
        button.disabled = true;
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            });
            let json = null;
            try {
                json = await response.json();
            } catch (error) {
                json = null;
            }
            const success = json?.status === 'success' || response.ok;
            const message = json?.message || (success ? 'İşlem tamamlandı.' : 'İşlem gerçekleştirilemedi.');
            Swal.fire({ icon: success ? 'success' : 'error', title: message, confirmButtonColor: '#0d6efd' });
            if (success) {
                refreshTable(tableId);
            }
        } catch (error) {
            console.error('İşlem sırasında hata oluştu', error);
            Swal.fire({ icon: 'error', title: 'İşlem sırasında bir hata oluştu.', confirmButtonColor: '#0d6efd' });
        } finally {
            button.disabled = false;
        }
    };

    if (confirmMessage) {
        Swal.fire({
            title: confirmMessage,
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet',
            cancelButtonText: 'Vazgeç',
        }).then((result) => {
            if (result.isConfirmed) {
                proceed();
            }
        });
    } else {
        proceed();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const flash = document.querySelector('[data-flash-message]');
    if (flash) {
        const { type, message } = flash.dataset;
        Swal.fire({
            icon: type || 'success',
            title: message,
            confirmButtonColor: '#0d6efd',
        });
    }

    document.querySelectorAll('[data-confirm]').forEach((element) => {
        if (element.dataset.confirmInitialized) {
            return;
        }
        if (element.hasAttribute('data-ajax-action')) {
            return;
        }
        element.dataset.confirmInitialized = '1';
        element.addEventListener('click', (event) => {
            const message = element.dataset.confirm || 'Emin misiniz?';
            event.preventDefault();
            Swal.fire({
                title: message,
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Evet',
                cancelButtonText: 'Vazgeç',
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                if (element.tagName === 'A' && element.getAttribute('href')) {
                    window.location.href = element.getAttribute('href');
                    return;
                }

                const form = element.closest('form');
                if (form) {
                    form.submit();
                }
            });
        });
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-ajax-action]');
        if (!button) {
            return;
        }
        event.preventDefault();
        submitAjaxAction(button);
    });

    if (window.Dropzone) {
        document.querySelectorAll('.dropzone[data-dropzone-url]').forEach((element) => {
            if (element.dataset.dropzoneInitialized) {
                return;
            }

            element.dataset.dropzoneInitialized = '1';
            const url = element.dataset.dropzoneUrl;
            const type = element.dataset.dropzoneType || 'logo';
            const csrf = element.dataset.dropzoneCsrf || '';
            const accepted = type === 'favicon'
                ? 'image/png,image/x-icon,image/svg+xml'
                : 'image/png,image/jpeg,image/svg+xml';

            const dz = new Dropzone(element, {
                url,
                paramName: 'file',
                maxFiles: 1,
                acceptedFiles: accepted,
                addRemoveLinks: true,
                dictDefaultMessage: 'Dosyayı sürükleyip bırakın veya tıklayın',
                timeout: 180000,
            });

            dz.on('sending', (file, xhr, formData) => {
                formData.append('type', type);
                if (csrf) {
                    formData.append('csrf_token', csrf);
                }
            });

            dz.on('success', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Görsel güncellendi',
                    confirmButtonColor: '#0d6efd',
                }).then(() => window.location.reload());
            });

            dz.on('error', (file, message) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Yükleme başarısız',
                    text: typeof message === 'string' ? message : 'Dosya yüklenemedi.',
                    confirmButtonColor: '#0d6efd',
                });
            });
        });
    }

    document.querySelectorAll('[data-purchase-form]').forEach((form) => {
        const select = form.querySelector('[data-payment-select]');
        const bankInfo = form.querySelector('[data-bank-info]');
        const hidden = form.querySelector('input[type="hidden"][name="payment_method"]');
        if (!bankInfo) {
            return;
        }

        const toggle = () => {
            let method = '';
            if (select && !select.disabled) {
                method = select.value;
            } else if (hidden) {
                method = hidden.value;
            } else if (select && select.dataset.default) {
                method = select.dataset.default;
            }
            if (method === 'bank') {
                bankInfo.removeAttribute('hidden');
            } else {
                bankInfo.setAttribute('hidden', 'hidden');
            }
        };

        toggle();
        if (select) {
            select.addEventListener('change', toggle);
        }
    });

    const usageRange = document.getElementById('usageRange');
    if (usageRange) {
        loadUsageMetrics(usageRange.value);
        usageRange.addEventListener('change', () => loadUsageMetrics(usageRange.value));
    }

    document.querySelectorAll('[data-export]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            exportUsage(button.dataset.export);
        });
    });

    loadDashboardMetrics();
    loadClientUsageMetrics();
});
