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
    rejected: { label: 'Reddedildi', class: 'bg-danger' },
    cancelled: { label: 'İptal Edildi', class: 'bg-secondary' },
};

const paymentStatusMap = {
    pending: { label: 'Bekliyor', class: 'bg-warning text-dark' },
    approved: { label: 'Onaylandı', class: 'bg-success' },
    rejected: { label: 'Reddedildi', class: 'bg-danger' },
};

window.appHandlers = {
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
};

let usageChart;
let usageMetrics = [];

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
                borderColor: '#38bdf8',
                backgroundColor: 'rgba(56, 189, 248, 0.25)',
                fill: true,
                tension: 0.3,
            },
        ],
    };

    if (!usageChart) {
        usageChart = new Chart(canvas, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: { color: '#f8fafc' },
                    },
                },
                scales: {
                    x: { ticks: { color: '#cbd5f5' }, grid: { color: 'rgba(148, 163, 184, 0.2)' } },
                    y: { ticks: { color: '#cbd5f5' }, grid: { color: 'rgba(148, 163, 184, 0.15)' } },
                },
            },
        });
    } else {
        usageChart.data = chartData;
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
});
