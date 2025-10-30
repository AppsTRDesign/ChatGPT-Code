const dashboardContent = document.getElementById('dashboardContent');
const dashboardLinks = document.querySelectorAll('#dashboardApp .nav-link');
const logoutBtn = document.getElementById('logoutBtn');
const clockEl = document.getElementById('dashboardClock');
const context = window.dashboardContext || {};
const socket = context.socketUrl ? io(context.socketUrl, { auth: { restaurantId: String(context.restaurantId || '') } }) : null;

let ordersHistoryTable = null;

const state = {
    currentPage: 'overview',
    orders: [],
    metrics: {},
    tables: [],
    categories: [],
    products: [],
    calls: [],
    report: [],
    settings: null,
    api: null,
    orderRange: 'month',
    orderFilter: { from: '', to: '' },
};

const playTone = (frequency = 880, duration = 0.3) => {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioContext();
        const oscillator = ctx.createOscillator();
        const gain = ctx.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = frequency;
        oscillator.connect(gain);
        gain.connect(ctx.destination);
        oscillator.start();
        gain.gain.setValueAtTime(0.2, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + duration);
        oscillator.stop(ctx.currentTime + duration);
    } catch (error) {
        console.debug('Audio not supported', error);
    }
};

const fetchJSON = async (url, options = {}) => {
    const response = await fetch(url, options);
    const text = await response.text();
    let data = {};
    if (text) {
        try {
            data = JSON.parse(text);
        } catch (error) {
            console.error('JSON parse error', error, text);
            throw { error: 'Sunucudan beklenmeyen cevap alındı' };
        }
    }
    if (!response.ok) {
        throw data?.error ? data : { error: 'İşlem başarısız' };
    }
    return data;
};

const toast = (title, icon = 'info') => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        timer: 2600,
        showConfirmButton: false,
        title,
        icon,
    });
};

const formatCurrency = (amount = 0, currency = context.currency || 'TRY') => {
    return `${Number(amount || 0).toFixed(2)} ${currency}`;
};

const formatDateTime = (value) => {
    if (!value) return '';
    const normalized = typeof value === 'string' ? value.replace(' ', 'T') : value;
    const date = new Date(normalized);
    if (Number.isNaN(date.getTime())) {
        return value;
    }
    return date.toLocaleString('tr-TR');
};

const downloadBase64 = (base64, filename, mime = 'application/octet-stream') => {
    if (!base64) return;
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i += 1) {
        bytes[i] = binary.charCodeAt(i);
    }
    const blob = new Blob([bytes], { type: mime });
    saveAs(blob, filename);
};

const renderOverview = () => {
    const totalOrders = state.orders.length;
    const revenue = state.orders.reduce((sum, order) => sum + Number(order.total_amount), 0);
    const activeTables = state.tables.filter((t) => t.status === 'occupied').length;
    const pendingCalls = state.calls.filter((c) => c.status === 'pending').length;

    dashboardContent.innerHTML = `
        <div class="row g-3">
            <div class="col-md-3">
                <div class="stat-card">
                    <p class="mb-1">Toplam Sipariş</p>
                    <h3>${totalOrders}</h3>
                    <small>Bugüne kadar alınan siparişler</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(180deg,#0ea5e9,#0284c7);">
                    <p class="mb-1">Toplam Ciro</p>
                    <h3>${formatCurrency(revenue)}</h3>
                    <small>Anlık rapor</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(180deg,#9333ea,#6d28d9);">
                    <p class="mb-1">Aktif Masalar</p>
                    <h3>${activeTables}</h3>
                    <small>Şu an dolu olan masalar</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(180deg,#f97316,#ea580c);">
                    <p class="mb-1">Garson Çağrısı</p>
                    <h3>${pendingCalls}</h3>
                    <small>Bekleyen çağrılar</small>
                </div>
            </div>
        </div>
    `;
};

const statusLabels = {
    pending: 'Beklemede',
    preparing: 'Hazırlanıyor',
    ready: 'Hazırlandı',
    completed: 'Tamamlandı',
    cancelled: 'İptal',
};

const statusColumns = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];

const categoryIconLibrary = [
    'bi bi-cup-hot',
    'bi bi-egg-fried',
    'bi bi-basket',
    'bi bi-ice-cream',
    'bi bi-cup-straw',
    'bi bi-pizza',
    'bi bi-sunrise',
    'bi bi-bag-heart',
    'bi bi-emoji-smile',
    'bi bi-stars',
];

const renderOrders = () => {
    const grouped = statusColumns.map((status) => ({
        status,
        orders: state.orders.filter((order) => order.status === status),
    }));
    const statusColors = {
        pending: 'bg-warning text-dark',
        preparing: 'bg-info text-dark',
        ready: 'bg-primary',
        completed: 'bg-success',
        cancelled: 'bg-danger',
    };
    const historyRows = state.orders.map((order) => {
        const paymentStatus = order.payment_status === 'paid' ? 'Ödendi' : 'Beklemede';
        const paymentBadge = order.payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark';
        return `
            <tr>
                <td>#${order.order_number}</td>
                <td>${order.table_number || '-'}</td>
                <td><span class="badge ${statusColors[order.status] || 'bg-secondary'}">${statusLabels[order.status] || order.status}</span></td>
                <td><span class="badge ${paymentBadge}">${paymentStatus}</span></td>
                <td>${formatCurrency(order.total_amount, order.currency || context.currency || 'TRY')}</td>
                <td>${formatDateTime(order.created_at)}</td>
                <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-primary me-1" data-history-detail="${order.id}"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-outline-secondary me-1" data-history-receipt="${order.order_number}"><i class="bi bi-printer"></i></button>
                    ${order.payment_status === 'unpaid' ? `<button class="btn btn-sm btn-outline-success" data-history-payment="${order.id}" data-order-number="${order.order_number}"><i class="bi bi-cash"></i></button>` : ''}
                </td>
            </tr>
        `;
    }).join('');

    dashboardContent.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Gelen Siparişler</h2>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <select class="form-select form-select-sm" id="orderRange" style="width: 150px;">
                    <option value="day" ${state.orderRange === 'day' ? 'selected' : ''}>Günlük</option>
                    <option value="week" ${state.orderRange === 'week' ? 'selected' : ''}>Haftalık</option>
                    <option value="month" ${state.orderRange === 'month' ? 'selected' : ''}>Aylık</option>
                    <option value="year" ${state.orderRange === 'year' ? 'selected' : ''}>Yıllık</option>
                    <option value="custom" ${state.orderRange === 'custom' ? 'selected' : ''}>Özel</option>
                </select>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-success" id="exportOrdersExcel" type="button"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel</button>
                    <button class="btn btn-sm btn-outline-primary" id="exportOrdersPdf" type="button"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</button>
                </div>
            </div>
        </div>
        <div class="orders-board">
            ${grouped.map((column) => `
                <div class="order-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 text-uppercase fw-bold">${statusLabels[column.status]}</h3>
                        <span class="badge bg-light text-dark">${column.orders.length}</span>
                    </div>
                    <div class="flex-grow-1 overflow-auto pe-2">
                        ${column.orders.length ? column.orders.map(renderOrderCard).join('') : '<p class="text-muted small">Kayıt yok</p>'}
                    </div>
                </div>
            `).join('')}
        </div>
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="h6 mb-0">Sipariş Geçmişi</h3>
                    <small class="text-muted">Arama, filtreleme ve dışa aktarma için tabloyu kullanın.</small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <input type="date" class="form-control form-control-sm" id="orderFrom" value="${state.orderFilter.from || ''}">
                    <input type="date" class="form-control form-control-sm" id="orderTo" value="${state.orderFilter.to || ''}">
                    <button class="btn btn-sm btn-outline-primary" id="orderFilterBtn"><i class="bi bi-funnel"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" id="orderResetBtn"><i class="bi bi-arrow-counterclockwise"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="ordersHistoryTable">
                        <thead>
                            <tr>
                                <th>Sipariş</th>
                                <th>Masa</th>
                                <th>Durum</th>
                                <th>Ödeme</th>
                                <th>Toplam</th>
                                <th>Tarih</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${historyRows}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;

    const rangeSelect = document.getElementById('orderRange');
    if (rangeSelect) {
        rangeSelect.addEventListener('change', (e) => {
            if (e.target.value === 'custom') {
                toast('Özel tarih aralığı için tarih alanlarını kullanın', 'info');
                return;
            }
            loadOrders({ range: e.target.value });
        });
    }

    const filterBtn = document.getElementById('orderFilterBtn');
    const resetBtn = document.getElementById('orderResetBtn');
    const fromInput = document.getElementById('orderFrom');
    const toInput = document.getElementById('orderTo');
    if (filterBtn && fromInput && toInput) {
        filterBtn.addEventListener('click', () => {
            const from = fromInput.value;
            const to = toInput.value;
            if (!from || !to) {
                Swal.fire('Uyarı', 'Başlangıç ve bitiş tarihini seçiniz', 'warning');
                return;
            }
            loadOrders({ from, to });
        });
    }
    if (resetBtn && fromInput && toInput) {
        resetBtn.addEventListener('click', () => {
            fromInput.value = '';
            toInput.value = '';
            loadOrders({ range: 'month' });
        });
    }

    document.getElementById('exportOrdersExcel').addEventListener('click', () => exportOrders('excel'));
    document.getElementById('exportOrdersPdf').addEventListener('click', () => exportOrders('pdf'));

    dashboardContent.querySelectorAll('[data-action]').forEach((button) => {
        button.addEventListener('click', handleOrderAction);
    });

    const historyTableEl = document.getElementById('ordersHistoryTable');
    if (historyTableEl) {
        if (window.simpleDatatables) {
            if (ordersHistoryTable) {
                ordersHistoryTable.destroy();
            }
            ordersHistoryTable = new simpleDatatables.DataTable(historyTableEl, {
                perPage: 10,
                labels: {
                    placeholder: 'Ara...',
                    perPage: '{select} kayıt',
                    noRows: 'Kayıt bulunamadı',
                    info: 'Gösterilen: {start}-{end} / {rows}',
                },
            });
        }
        historyTableEl.addEventListener('click', async (event) => {
            const detailBtn = event.target.closest('[data-history-detail]');
            if (detailBtn) {
                const order = state.orders.find((o) => String(o.id) === String(detailBtn.dataset.historyDetail));
                if (order) openOrderDetail(order);
                return;
            }
            const receiptBtn = event.target.closest('[data-history-receipt]');
            if (receiptBtn) {
                try {
                    const payload = await fetchJSON('/dashboard/orders/receipt', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order_number: receiptBtn.dataset.historyReceipt }),
                    });
                    downloadBase64(payload.content, payload.filename, payload.mime);
                } catch (error) {
                    Swal.fire('Hata', error.error || 'Adisyon oluşturulamadı', 'error');
                }
                return;
            }
            const paymentBtn = event.target.closest('[data-history-payment]');
            if (paymentBtn) {
                try {
                    await fetchJSON('/dashboard/orders', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'payment', order_id: Number(paymentBtn.dataset.historyPayment), order_number: paymentBtn.dataset.orderNumber }),
                    });
                    toast('Ödeme alındı', 'success');
                    await loadOrders({ range: state.orderRange, from: state.orderFilter.from, to: state.orderFilter.to });
                    await loadTables();
                } catch (error) {
                    Swal.fire('Hata', error.error || 'Ödeme tamamlanamadı', 'error');
                }
            }
        });
    }
};

const renderOrderCard = (order) => {
    const items = order.items || [];
    const nextStatus = {
        pending: 'preparing',
        preparing: 'ready',
        ready: 'completed',
    }[order.status];
    return `
        <div class="order-card" data-order="${order.id}">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h4 class="h6 mb-1">Masa ${order.table_number || '-'}</h4>
                    <small class="text-muted">#${order.order_number}</small>
                </div>
                <span class="badge rounded-pill bg-${order.status === 'cancelled' ? 'danger' : order.status === 'completed' ? 'success' : 'warning'}">${statusLabels[order.status]}</span>
            </div>
            <ul class="mt-3 list-unstyled small">
                ${items.map((item) => `<li class="d-flex justify-content-between"><span>${item.quantity || 1} x ${item.name}</span><strong>${Number(item.price || 0).toFixed(2)}</strong></li>`).join('')}
            </ul>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="fw-semibold">${formatCurrency(order.total_amount, order.currency || context.currency || 'TRY')}</span>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary" data-action="detail" data-id="${order.id}"><i class="bi bi-eye"></i></button>
                    ${nextStatus ? `<button class="btn btn-sm btn-success" data-action="status" data-status="${nextStatus}" data-id="${order.id}" data-table-id="${order.table_id || ''}" data-table-token="${order.table_token || ''}">${statusLabels[nextStatus]}</button>` : ''}
                    ${order.status !== 'cancelled' && order.status !== 'completed' ? `<button class="btn btn-sm btn-outline-danger" data-action="status" data-status="cancelled" data-id="${order.id}" data-table-id="${order.table_id || ''}" data-table-token="${order.table_token || ''}"><i class="bi bi-x"></i></button>` : ''}
                    ${order.status === 'completed' && order.payment_status === 'unpaid' ? `<button class="btn btn-sm btn-outline-success" data-action="payment" data-id="${order.id}">Ödeme Alındı</button>` : ''}
                </div>
            </div>
        </div>
    `;
};

const handleOrderAction = async (event) => {
    const button = event.currentTarget;
    const orderId = button.dataset.id;
    if (button.dataset.action === 'detail') {
        const order = state.orders.find((o) => String(o.id) === String(orderId));
        if (order) openOrderDetail(order);
        return;
    }
    if (button.dataset.action === 'status') {
        const status = button.dataset.status;
        try {
            await fetchJSON('/dashboard/orders', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'status',
                    order_id: orderId,
                    status,
                    table_id: button.dataset.tableId || null,
                    table_token: button.dataset.tableToken || null,
                }),
            });
            toast('Sipariş güncellendi', 'success');
            playTone(status === 'cancelled' ? 420 : 900);
            await loadOrders({ range: state.orderRange, from: state.orderFilter.from, to: state.orderFilter.to });
            await loadTables();
        } catch (error) {
            Swal.fire('Hata', error.error || 'Durum güncellenemedi', 'error');
        }
        return;
    }
    if (button.dataset.action === 'payment') {
        try {
            const order = state.orders.find((o) => String(o.id) === String(orderId));
            await fetchJSON('/dashboard/orders', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'payment', order_id: orderId, order_number: order?.order_number }),
            });
            toast('Ödeme alındı', 'success');
            await loadOrders({ range: state.orderRange, from: state.orderFilter.from, to: state.orderFilter.to });
            await loadTables();
        } catch (error) {
            Swal.fire('Hata', error.error || 'İşlem tamamlanamadı', 'error');
        }
    }
};

const openOrderDetail = (order) => {
    Swal.fire({
        title: `Sipariş #${order.order_number}`,
        html: `
            <div class="text-start">
                <p class="mb-1"><strong>Masa:</strong> ${order.table_number}</p>
                <p class="mb-1"><strong>Durum:</strong> ${statusLabels[order.status]}</p>
                <p class="mb-3"><strong>Toplam:</strong> ${formatCurrency(order.total_amount, order.currency || context.currency || 'TRY')}</p>
                <ul class="list-group small mb-3">
                    ${(order.items || []).map((item) => `<li class="list-group-item d-flex justify-content-between align-items-center">${item.quantity || 1} x ${item.name}<span>${Number(item.price || 0).toFixed(2)}</span></li>`).join('')}
                </ul>
                <button class="btn btn-outline-success w-100" id="downloadCashReceipt"><i class="bi bi-printer me-1"></i>Yazar Kasa Adisyonu</button>
            </div>
        `,
        showConfirmButton: false,
        didOpen: () => {
            document.getElementById('downloadCashReceipt').addEventListener('click', async () => {
                try {
                    const payload = await fetchJSON('/dashboard/orders/receipt', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order_number: order.order_number }),
                    });
                    downloadBase64(payload.content, payload.filename, payload.mime);
                } catch (error) {
                    Swal.fire('Hata', error.error || 'Adisyon hazırlanamadı', 'error');
                }
            });
        }
    });
};

const exportOrders = async (format = 'excel') => {
    try {
        const payload = { format };
        if (state.orderRange === 'custom' && state.orderFilter.from && state.orderFilter.to) {
            payload.from = state.orderFilter.from;
            payload.to = state.orderFilter.to;
        } else {
            payload.range = state.orderRange || 'month';
        }
        const response = await fetchJSON('/dashboard/orders/export', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        if (format === 'excel') {
            const { content, filename, mime } = response;
            const blob = new Blob([Uint8Array.from(atob(content), (c) => c.charCodeAt(0))], { type: mime });
            saveAs(blob, filename);
            return;
        }

        if (format === 'pdf') {
            downloadBase64(response.content, response.filename, response.mime);
            return;
        }

        toast('Bilinmeyen format için veri alındı', 'info');
    } catch (error) {
        Swal.fire('Hata', error.error || 'Rapor indirilemedi', 'error');
    }
};

const loadOrders = async ({ range, from, to } = {}) => {
    try {
        const params = new URLSearchParams();
        const allowedRanges = ['day', 'week', 'month', 'year'];
        if (from && to) {
            params.set('from', from);
            params.set('to', to);
            state.orderFilter = { from, to };
            state.orderRange = 'custom';
        } else {
            const requestedRange = range || state.orderRange || 'month';
            const effectiveRange = allowedRanges.includes(requestedRange) ? requestedRange : 'month';
            params.set('range', effectiveRange);
            state.orderRange = effectiveRange;
            state.orderFilter = { from: '', to: '' };
        }
        const query = params.toString();
        const { orders, metrics, tables } = await fetchJSON(`/dashboard/orders${query ? `?${query}` : ''}`);
        state.orders = orders;
        state.metrics = metrics;
        state.tables = tables;
        renderOrders();
    } catch (error) {
        Swal.fire('Hata', error.error || 'Siparişler alınamadı', 'error');
    }
};

const loadTables = async () => {
    try {
        const { tables, qr_settings: qrSettings } = await fetchJSON('/dashboard/tables');
        state.tables = tables;
        if (qrSettings) {
            state.settings = state.settings || {};
            state.settings.qr_settings = qrSettings;
            state.api = state.api || {};
            state.api.qr_defaults = qrSettings;
        }
        renderTables();
        if (state.currentPage === 'settings') {
            renderSettings();
        }
    } catch (error) {
        Swal.fire('Hata', error.error || 'Masalar alınamadı', 'error');
    }
};

const renderTables = () => {
    dashboardContent.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h5 mb-0">Masalar ve QR Kodlar</h2>
                <small class="text-muted">Her masa için özel QR kod oluşturun ve takibini yapın.</small>
            </div>
            <button class="btn btn-success" id="addTable"><i class="bi bi-plus-lg me-1"></i>Masa Ekle</button>
        </div>
        <div class="table-grid">
            ${state.tables.map(renderTableCard).join('')}
        </div>
    `;
    document.getElementById('addTable').addEventListener('click', openTableModal);
    dashboardContent.querySelectorAll('[data-table-action]').forEach((btn) => btn.addEventListener('click', handleTableAction));
};

const renderTableCard = (table) => {
    const qrData = table.qr || {
        url: `${context.baseUrl}/menu/${context.restaurantSlug}/table/${table.slug}?token=${table.qr_token}`,
        image: `${context.qrApi}?token=&type=url&url=${encodeURIComponent(`${context.baseUrl}/menu/${context.restaurantSlug}/table/${table.slug}?token=${table.qr_token}`)}`,
        settings: state.settings?.qr_settings || {},
    };
    const openOrder = table.open_order || null;
    return `
        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="h6 mb-0">${table.name}</h3>
                <span class="badge ${table.status === 'occupied' ? 'bg-danger' : 'bg-success'}">${table.status === 'occupied' ? 'Dolu' : 'Boş'}</span>
            </div>
            <p class="text-muted small mb-2">${qrData.url}</p>
            <div class="qr-frame mb-3">
                <img src="${qrData.image}" alt="QR" class="img-fluid">
            </div>
            ${openOrder ? `
                <div class="table-order-summary">
                    <div class="d-flex justify-content-between">
                        <span>#${openOrder.order_number}</span>
                        <strong>${formatCurrency(openOrder.total_amount, openOrder.currency || context.currency || 'TRY')}</strong>
                    </div>
                    <small class="text-muted">Durum: ${statusLabels[openOrder.status] || openOrder.status}</small>
                </div>
            ` : '<p class="text-muted small mb-3">Aktif sipariş bulunmuyor</p>'}
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-primary btn-sm flex-grow-1" data-table-action="status" data-id="${table.id}" data-status="${table.status === 'occupied' ? 'vacant' : 'occupied'}">${table.status === 'occupied' ? 'Boş Olarak İşaretle' : 'Dolu Olarak İşaretle'}</button>
                ${openOrder ? `<button class="btn btn-outline-success btn-sm" data-table-action="order" data-id="${table.id}" data-order-id="${openOrder.id}" data-order-number="${openOrder.order_number}"><i class="bi bi-eye"></i></button>` : ''}
                ${openOrder ? `<button class="btn btn-outline-warning btn-sm" data-table-action="settle" data-id="${table.id}" data-order-id="${openOrder.id}" data-order-number="${openOrder.order_number}"><i class="bi bi-cash-stack"></i></button>` : ''}
                <button class="btn btn-outline-secondary btn-sm" data-table-action="edit" data-id="${table.id}"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-outline-danger btn-sm" data-table-action="delete" data-id="${table.id}"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    `;
};

const openTableModal = (table = null) => {
    Swal.fire({
        title: table ? 'Masa Düzenle' : 'Yeni Masa',
        html: `
            <div class="text-start">
                <label class="form-label">Masa Adı</label>
                <input type="text" id="tableName" class="form-control" value="${table?.name || ''}">
                <label class="form-label mt-2">Kısa Adres</label>
                <input type="text" id="tableSlug" class="form-control" value="${table?.slug || ''}" placeholder="Örn: masa-5">
                <label class="form-label mt-2">Kişi Sayısı</label>
                <input type="number" id="tableSeats" class="form-control" value="${table?.seats || 4}">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: table ? 'Güncelle' : 'Oluştur',
        preConfirm: () => ({
            name: document.getElementById('tableName').value,
            slug: document.getElementById('tableSlug').value,
            seats: Number(document.getElementById('tableSeats').value),
        }),
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        try {
            if (table) {
                await fetchJSON('/dashboard/tables', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: table.id, ...result.value }),
                });
            } else {
                await fetchJSON('/dashboard/tables', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(result.value),
                });
            }
            toast('Masa kaydedildi', 'success');
            await loadTables();
        } catch (error) {
            Swal.fire('Hata', error.error || 'Masa kaydedilemedi', 'error');
        }
    });
};

const handleTableAction = async (event) => {
    const { tableAction: action, id, status, orderId, orderNumber } = event.currentTarget.dataset;
    const table = state.tables.find((t) => String(t.id) === String(id));
    if (action === 'edit') {
        openTableModal(table);
        return;
    }
    if (action === 'delete') {
        Swal.fire({
            title: 'Emin misiniz?',
            text: 'Masa ve QR bilgileri silinecek.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sil',
        }).then(async (result) => {
            if (!result.isConfirmed) return;
            try {
                await fetchJSON('/dashboard/tables', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id }),
                });
                toast('Masa silindi', 'success');
                await loadTables();
            } catch (error) {
                Swal.fire('Hata', error.error || 'Silme işlemi başarısız', 'error');
            }
        });
        return;
    }
    if (action === 'order' && table?.open_order) {
        openOrderDetail(table.open_order);
        return;
    }
    if (action === 'settle' && orderId) {
        try {
            await fetchJSON('/dashboard/tables', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'settle', id, order_id: Number(orderId), order_number: orderNumber, method: 'cash' }),
            });
            toast('Masa hesabı kapatıldı', 'success');
            await loadTables();
            if (state.currentPage === 'orders') {
                await loadOrders({ range: state.orderRange, from: state.orderFilter.from, to: state.orderFilter.to });
            }
        } catch (error) {
            Swal.fire('Hata', error.error || 'Hesap kapatılamadı', 'error');
        }
        return;
    }
    if (action === 'status') {
        try {
            await fetchJSON('/dashboard/tables', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'status', id, status }),
            });
            toast('Masa durumu güncellendi', 'success');
            await loadTables();
            if (state.currentPage === 'orders') {
                await loadOrders({ range: state.orderRange, from: state.orderFilter.from, to: state.orderFilter.to });
            }
        } catch (error) {
            Swal.fire('Hata', error.error || 'Durum değiştirilemedi', 'error');
        }
    }
};

const loadMenuManager = async () => {
    try {
        const [categories, products] = await Promise.all([
            fetchJSON('/dashboard/categories'),
            fetchJSON('/dashboard/products'),
        ]);
        state.categories = categories.categories || [];
        state.products = products.products || [];
        renderMenuManager();
    } catch (error) {
        Swal.fire('Hata', error.error || 'Menü alınamadı', 'error');
    }
};

const renderMenuManager = () => {
    dashboardContent.innerHTML = `
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="category-block">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 mb-0">Kategoriler</h3>
                        <button class="btn btn-sm btn-success" id="addCategory"><i class="bi bi-plus"></i></button>
                    </div>
                    <div class="list-group" id="categoryList">
                        ${state.categories.map((category) => `
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">${category.icon_class ? `<i class="${category.icon_class} me-2"></i>` : ''}${category.name}</div>
                                    <small class="text-muted">${category.description || ''}</small>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" data-category-edit="${category.id}"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" data-category-delete="${category.id}"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="category-block">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 mb-0">Ürünler</h3>
                        <button class="btn btn-sm btn-success" id="addProduct"><i class="bi bi-plus"></i></button>
                    </div>
                    <div class="list-group" id="productList">
                        ${state.products.map(renderProductRow).join('')}
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('addCategory').addEventListener('click', () => openCategoryModal());
    document.getElementById('addProduct').addEventListener('click', () => openProductModal());
    dashboardContent.querySelectorAll('[data-category-edit]').forEach((btn) => btn.addEventListener('click', () => {
        const category = state.categories.find((c) => String(c.id) === btn.dataset.categoryEdit);
        openCategoryModal(category);
    }));
    dashboardContent.querySelectorAll('[data-category-delete]').forEach((btn) => btn.addEventListener('click', () => deleteCategory(btn.dataset.categoryDelete)));
    dashboardContent.querySelectorAll('[data-product-edit]').forEach((btn) => btn.addEventListener('click', () => {
        const product = state.products.find((p) => String(p.id) === btn.dataset.productEdit);
        openProductModal(product);
    }));
    dashboardContent.querySelectorAll('[data-product-delete]').forEach((btn) => btn.addEventListener('click', () => deleteProduct(btn.dataset.productDelete)));
};

const renderProductRow = (product) => {
    return `
        <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">${product.name}</div>
                    <small class="text-muted">${product.category_name || ''}</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="fw-semibold">${Number(product.price || 0).toFixed(2)} ${product.currency}</span>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" data-product-edit="${product.id}"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger" data-product-delete="${product.id}"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>
    `;
};

const openCategoryModal = (category = null) => {
    let imageUrl = category?.image_url || '';
    let iconClass = category?.icon_class || '';
    const iconOptions = categoryIconLibrary.map((icon) => `<option value="${icon}"></option>`).join('');
    Swal.fire({
        title: category ? 'Kategori Düzenle' : 'Yeni Kategori',
        html: `
            <div class="text-start">
                <label class="form-label">Kategori Adı</label>
                <input type="text" id="categoryName" class="form-control" value="${category?.name || ''}">
                <label class="form-label mt-2">Açıklama</label>
                <textarea id="categoryDescription" class="form-control" rows="2">${category?.description || ''}</textarea>
                <label class="form-label mt-2">İkon</label>
                <div class="input-group">
                    <span class="input-group-text"><i id="categoryIconPreview" class="${iconClass || 'bi bi-tag'}"></i></span>
                    <input type="text" id="categoryIcon" class="form-control" value="${iconClass}" placeholder="Örn: bi bi-cup-hot" list="iconSuggestions">
                </div>
                <datalist id="iconSuggestions">${iconOptions}</datalist>
                <div class="upload-dropzone mt-3" id="categoryDropzone">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <p class="mb-1">Resim sürükleyin veya tıklayın</p>
                    ${imageUrl ? `<img src="${imageUrl}" class="img-fluid rounded mt-2" id="categoryPreview">` : '<small class="text-muted">(Opsiyonel)</small>'}
                    <input type="file" id="categoryImage" class="d-none" accept="image/*">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: category ? 'Güncelle' : 'Kaydet',
        didOpen: () => {
            initDropzone('categoryDropzone', 'categoryImage', async (file) => {
                const formData = new FormData();
                formData.append('image', file);
                const upload = await fetchJSON('/dashboard/categories/upload', {
                    method: 'POST',
                    body: formData,
                });
                imageUrl = upload.url;
                const preview = document.getElementById('categoryPreview');
                if (preview) {
                    preview.src = upload.url;
                } else {
                    const img = document.createElement('img');
                    img.src = upload.url;
                    img.className = 'img-fluid rounded mt-2';
                    document.getElementById('categoryDropzone').appendChild(img);
                }
            });
            const input = document.getElementById('categoryIcon');
            const previewIcon = document.getElementById('categoryIconPreview');
            if (input && previewIcon) {
                const updatePreview = () => {
                    const value = input.value.trim();
                    previewIcon.className = value ? value : 'bi bi-tag';
                };
                input.addEventListener('input', updatePreview);
                updatePreview();
            }
        },
        preConfirm: () => ({
            name: document.getElementById('categoryName').value,
            description: document.getElementById('categoryDescription').value,
            icon_class: document.getElementById('categoryIcon').value,
            image_url: imageUrl,
        }),
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        try {
            if (category) {
                await fetchJSON('/dashboard/categories', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: category.id, ...result.value }),
                });
            } else {
                await fetchJSON('/dashboard/categories', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(result.value),
                });
            }
            toast('Kategori kaydedildi', 'success');
            await loadMenuManager();
        } catch (error) {
            Swal.fire('Hata', error.error || 'Kategori kaydedilemedi', 'error');
        }
    });
};

const deleteCategory = (id) => {
    Swal.fire({
        title: 'Emin misiniz?',
        text: 'Kategori silinecek',
        icon: 'warning',
        showCancelButton: true,
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        try {
            await fetchJSON('/dashboard/categories', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id }),
            });
            toast('Kategori silindi', 'success');
            await loadMenuManager();
        } catch (error) {
            Swal.fire('Hata', error.error || 'Kategori silinemedi', 'error');
        }
    });
};

const openProductModal = (product = null) => {
    if (!state.categories.length) {
        Swal.fire('Uyarı', 'Önce en az bir kategori oluşturun', 'warning');
        return;
    }
    let imageUrl = product?.image_url || '';
    Swal.fire({
        title: product ? 'Ürün Düzenle' : 'Yeni Ürün',
        html: `
            <div class="text-start">
                <label class="form-label">Ürün Adı</label>
                <input type="text" id="productName" class="form-control" value="${product?.name || ''}">
                <label class="form-label mt-2">Kategori</label>
                <select id="productCategory" class="form-select">
                    ${state.categories.map((cat) => `<option value="${cat.id}" ${product && cat.id === product.category_id ? 'selected' : ''}>${cat.name}</option>`).join('')}
                </select>
                <label class="form-label mt-2">Fiyat (${context.currency})</label>
                <input type="number" step="0.01" id="productPrice" class="form-control" value="${product?.price || ''}">
                <label class="form-label mt-2">Açıklama</label>
                <textarea id="productDescription" class="form-control" rows="2">${product?.description || ''}</textarea>
                <div class="upload-dropzone mt-3" id="productDropzone">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <p class="mb-1">Ürün görseli yükleyin</p>
                    ${imageUrl ? `<img src="${imageUrl}" class="img-fluid rounded mt-2" id="productPreview">` : '<small class="text-muted">(Opsiyonel)</small>'}
                    <input type="file" id="productImage" class="d-none" accept="image/*">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: product ? 'Güncelle' : 'Kaydet',
        didOpen: () => initDropzone('productDropzone', 'productImage', async (file) => {
            const formData = new FormData();
            formData.append('image', file);
            const upload = await fetchJSON('/dashboard/products/upload', {
                method: 'POST',
                body: formData,
            });
            imageUrl = upload.url;
            const preview = document.getElementById('productPreview');
            if (preview) {
                preview.src = imageUrl;
            } else {
                const img = document.createElement('img');
                img.src = imageUrl;
                img.className = 'img-fluid rounded mt-2';
                document.getElementById('productDropzone').appendChild(img);
            }
        }),
        preConfirm: () => ({
            name: document.getElementById('productName').value,
            category_id: Number(document.getElementById('productCategory').value),
            price: Number(document.getElementById('productPrice').value),
            description: document.getElementById('productDescription').value,
            image_url: imageUrl,
            currency: context.currency,
        }),
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        try {
            if (product) {
                await fetchJSON('/dashboard/products', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: product.id, ...result.value }),
                });
            } else {
                await fetchJSON('/dashboard/products', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(result.value),
                });
            }
            toast('Ürün kaydedildi', 'success');
            await loadMenuManager();
        } catch (error) {
            Swal.fire('Hata', error.error || 'Ürün kaydedilemedi', 'error');
        }
    });
};

const deleteProduct = (id) => {
    Swal.fire({
        title: 'Emin misiniz?',
        text: 'Ürün silinecek',
        icon: 'warning',
        showCancelButton: true,
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        try {
            await fetchJSON('/dashboard/products', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id }),
            });
            toast('Ürün silindi', 'success');
            await loadMenuManager();
        } catch (error) {
            Swal.fire('Hata', error.error || 'Ürün silinemedi', 'error');
        }
    });
};

const initDropzone = (containerId, inputId, onFile) => {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    container.addEventListener('click', () => input.click());
    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        container.classList.add('is-dragover');
    });
    container.addEventListener('dragleave', () => container.classList.remove('is-dragover'));
    container.addEventListener('drop', async (e) => {
        e.preventDefault();
        container.classList.remove('is-dragover');
        const file = e.dataTransfer.files[0];
        if (file) {
            try {
                await onFile(file);
            } catch (error) {
                Swal.fire('Hata', error.error || 'Yükleme başarısız', 'error');
            }
        }
    });
    input.addEventListener('change', async () => {
        const file = input.files[0];
        if (file) {
            try {
                await onFile(file);
            } catch (error) {
                Swal.fire('Hata', error.error || 'Yükleme başarısız', 'error');
            }
        }
    });
};

const loadCalls = async (renderView = true) => {
    try {
        const { calls } = await fetchJSON('/dashboard/calls');
        state.calls = calls;
        if (renderView) renderCalls();
    } catch (error) {
        if (renderView) Swal.fire('Hata', error.error || 'Çağrılar alınamadı', 'error');
    }
};

const renderCalls = () => {
    dashboardContent.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h5 mb-0">Garson Çağrıları</h2>
                <small class="text-muted">Müşterilerinizin taleplerini yönetin.</small>
            </div>
        </div>
        <div class="list-group">
            ${state.calls.length ? state.calls.map((call) => `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">Masa ${call.table_number}</div>
                        <small class="text-muted">${new Date(call.created_at).toLocaleString()}</small>
                    </div>
                    <div class="btn-group">
                        ${call.status !== 'acknowledged' ? `<button class="btn btn-sm btn-outline-primary" data-call-action="ack" data-id="${call.id}">Yolda</button>` : ''}
                        <button class="btn btn-sm btn-outline-success" data-call-action="resolve" data-id="${call.id}">Tamamlandı</button>
                    </div>
                </div>
            `).join('') : '<p class="text-muted">Aktif çağrı bulunmuyor.</p>'}
        </div>
    `;
    dashboardContent.querySelectorAll('[data-call-action]').forEach((btn) => btn.addEventListener('click', async () => {
        try {
            await fetchJSON('/dashboard/calls', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: btn.dataset.id, status: btn.dataset.callAction === 'ack' ? 'acknowledged' : 'resolved' }),
            });
            await loadCalls();
        } catch (error) {
            Swal.fire('Hata', error.error || 'Çağrı güncellenemedi', 'error');
        }
    }));
};

const loadSettings = async () => {
    try {
        const [settingsResponse, apiResponse] = await Promise.all([
            fetchJSON('/dashboard/settings'),
            fetchJSON('/dashboard/settings/api'),
        ]);
        state.settings = settingsResponse.settings;
        state.api = apiResponse.api;
        renderSettings();
    } catch (error) {
        Swal.fire('Hata', error.error || 'Ayarlar alınamadı', 'error');
    }
};

const renderSettings = () => {
    const settings = state.settings || {};
    const api = state.api || { key: '', base_url: context.baseUrl, socket_url: context.socketUrl, endpoints: [], table_links: [] };
    const availableCurrencies = settings.available_currencies || api.available_currencies || [context.currency || 'TRY'];
    const qrSettings = settings.qr_settings || {};
    const sampleTable = Array.isArray(state.tables) ? state.tables.find((table) => table?.qr?.image) : null;
    const sampleQrImage = sampleTable?.qr?.image || '';
    const sampleQrUrl = sampleTable?.qr?.url || '';
    dashboardContent.innerHTML = `
        <form id="settingsForm" class="row g-4">
            <div class="col-lg-8">
                <div class="category-block">
                    <h3 class="h6 mb-3">Genel Bilgiler</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Restoran Adı</label>
                            <input type="text" class="form-control" name="name" value="${settings.name || ''}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" class="form-control" name="phone" value="${settings.phone || ''}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" name="description" rows="2">${settings.description || ''}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Adres</label>
                            <textarea class="form-control" name="address" rows="2">${settings.address || ''}</textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Para Birimi</label>
                            <select class="form-select" name="currency">
                                ${availableCurrencies.map((code) => `<option value="${code}" ${settings.currency === code ? 'selected' : ''}>${code}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Saat Dilimi</label>
                            <input type="text" class="form-control" name="timezone" value="${settings.timezone || 'Europe/Istanbul'}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Varsayılan Dil</label>
                            <input type="text" class="form-control" name="primary_language" value="${settings.primary_language || 'tr'}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Masa Etiketi</label>
                            <input type="text" class="form-control" name="qr_table_prefix" value="${settings.qr_table_prefix || 'Masa'}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Desteklenen Diller (virgülle ayırın)</label>
                            <input type="text" class="form-control" name="supported_languages" value="${(settings.supported_languages || []).join(', ')}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tema</label>
                            <select class="form-select" name="theme">
                                <option value="emerald" ${settings.theme === 'emerald' ? 'selected' : ''}>Yeşil</option>
                                <option value="amber" ${settings.theme === 'amber' ? 'selected' : ''}>Turuncu</option>
                                <option value="ocean" ${settings.theme === 'ocean' ? 'selected' : ''}>Mavi</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Birincil Renk</label>
                            <input type="color" class="form-control form-control-color" name="primary_color" value="${settings.primary_color || '#22c55e'}">
                        </div>
                    </div>
                </div>
                <div class="category-block mt-4">
                    <h3 class="h6 mb-3">QR Ayarları</h3>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">QR Token</label>
                            <input type="text" class="form-control" name="qr_token" value="${qrSettings.token || ''}" placeholder="API tarafından verilen token">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Format</label>
                            <select class="form-select" name="qr_format">
                                ${['png','svg','jpg'].map((format) => `<option value="${format}" ${qrSettings.format === format ? 'selected' : ''}>${format.toUpperCase()}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Arka Plan Şeffaf</label>
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" name="qr_transparent" value="1" ${qrSettings.transparent ? 'checked' : ''}>
                                <label class="form-check-label">Şeffaf arka plan</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Genişlik (px)</label>
                            <input type="number" class="form-control" name="qr_width" min="120" max="1000" value="${qrSettings.width || 420}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Yükseklik (px)</label>
                            <input type="number" class="form-control" name="qr_height" min="120" max="1000" value="${qrSettings.height || 420}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ön Plan Rengi</label>
                            <input type="color" class="form-control form-control-color" name="qr_color" value="${qrSettings.color || '#000000'}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Arka Plan Rengi</label>
                            <input type="color" class="form-control form-control-color" name="qr_background" value="${qrSettings.background || '#FFFFFF'}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">QR Örneği</label>
                            ${sampleQrImage ? `<div class="qr-preview"><img src="${sampleQrImage}" alt="QR" class="img-fluid rounded"><p class="small text-muted mt-2">${sampleQrUrl}</p></div>` : '<p class="text-muted small">Masalar oluşturulduğunda örnek QR burada görüntülenecektir.</p>'}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="category-block">
                    <h3 class="h6 mb-3">Logo & Favicon</h3>
                    <div class="upload-dropzone" id="logoDropzone">
                        <i class="bi bi-image"></i>
                        <p class="mb-1">Logo yükleyin</p>
                        ${settings.logo_url ? `<img src="${settings.logo_url}" class="img-fluid rounded mt-2" id="logoPreview">` : '<small class="text-muted">PNG/WEBP önerilir</small>'}
                        <input type="file" id="logoInput" class="d-none" accept="image/*">
                    </div>
                    <div class="upload-dropzone mt-3" id="faviconDropzone">
                        <i class="bi bi-star"></i>
                        <p class="mb-1">Favicon yükleyin</p>
                        ${settings.favicon_url ? `<img src="${settings.favicon_url}" class="img-fluid rounded mt-2" id="faviconPreview">` : '<small class="text-muted">32x32 ikon</small>'}
                        <input type="file" id="faviconInput" class="d-none" accept="image/*">
                    </div>
                    <div class="upload-dropzone mt-3" id="qrLogoDropzone">
                        <i class="bi bi-qr-code"></i>
                        <p class="mb-1">QR Logo Yükleyin</p>
                        ${(qrSettings.logo_url || settings.qr_logo_url) ? `<img src="${qrSettings.logo_url || settings.qr_logo_url}" class="img-fluid rounded mt-2" id="qrLogoPreview">` : '<small class="text-muted">Opsiyonel - QR merkezine logo ekler</small>'}
                        <input type="file" id="qrLogoInput" class="d-none" accept="image/*">
                    </div>
                    <button class="btn btn-success w-100 mt-3" type="submit">Kaydet</button>
                </div>
                <div class="category-block mt-4">
                    <h3 class="h6 mb-3">REST API Anahtarı</h3>
                    <p class="small text-muted">Tüm entegrasyon isteklerinde <code>X-API-KEY</code> başlığına bu anahtarı ekleyin.</p>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" value="${api.key || 'Anahtar oluşturuluyor...'}" readonly>
                        <button class="btn btn-outline-secondary" type="button" data-copy="${api.key || ''}" ${api.key ? '' : 'disabled'}><i class="bi bi-clipboard"></i></button>
                    </div>
                    <button class="btn btn-outline-danger w-100 mt-2" type="button" id="regenerateApiKey"><i class="bi bi-arrow-repeat me-1"></i>API Anahtarını Yenile</button>
                    <div class="alert alert-light border mt-3 small" role="alert">
                        <div><strong>API URL:</strong> ${api.base_url || context.baseUrl}</div>
                        <div><strong>Socket:</strong> ${api.socket_url || context.socketUrl}</div>
                    </div>
                    <div class="small">
                        <p class="fw-semibold mb-2">Yetkili uç noktalar</p>
                        <div class="list-group small">
                            ${(api.endpoints || []).map((endpoint) => `
                                <div class="list-group-item d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <span class="badge bg-success me-2">${endpoint.method}</span>
                                        <code>${endpoint.path}</code>
                                        <div class="text-muted">${endpoint.description}</div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-copy="${endpoint.full_url}"><i class="bi bi-clipboard"></i></button>
                                </div>
                            `).join('') || '<div class="list-group-item text-muted">Tanımlı endpoint yok</div>'}
                        </div>
                        <p class="fw-semibold mt-3 mb-2">Desteklenen Para Birimleri</p>
                        <div class="d-flex flex-wrap gap-2">
                            ${availableCurrencies.map((code) => `<span class="badge bg-light text-dark border">${code}</span>`).join('')}
                        </div>
                        ${api.qr_defaults ? `
                            <p class="fw-semibold mt-3 mb-1">QR Varsayılanları</p>
                            <div class="qr-defaults small text-muted">
                                <div><strong>Token:</strong> ${api.qr_defaults.token || '-'}</div>
                                <div><strong>Format:</strong> ${(api.qr_defaults.format || 'png').toUpperCase()}</div>
                                <div><strong>Boyut:</strong> ${api.qr_defaults.width}x${api.qr_defaults.height}</div>
                                <div><strong>Renk:</strong> ${api.qr_defaults.color}</div>
                                <div><strong>Arka Plan:</strong> ${api.qr_defaults.transparent ? 'Şeffaf' : api.qr_defaults.background}</div>
                            </div>
                        ` : ''}
                    </div>
                </div>
                <div class="category-block mt-4">
                    <h3 class="h6 mb-3">Masa Linkleri & QR</h3>
                    <p class="small text-muted">Her masa için özel bağlantıları kopyalayabilir veya QR çıktısı alabilirsiniz.</p>
                    <div class="table-links-scroll list-group" style="max-height: 240px; overflow:auto;">
                        ${(api.table_links || []).map((table) => `
                            <div class="list-group-item d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-semibold">${table.name}</div>
                                    <div class="text-muted small">Slug: ${table.slug}</div>
                                    <div class="text-muted small">${table.url}</div>
                                </div>
                                <div class="text-end">
                                    <span class="badge ${table.status === 'occupied' ? 'bg-danger' : 'bg-success'} mb-2">${table.status === 'occupied' ? 'Dolu' : 'Boş'}</span>
                                    <button class="btn btn-sm btn-outline-success" type="button" data-copy="${table.url}"><i class="bi bi-link-45deg"></i></button>
                                </div>
                            </div>
                        `).join('') || '<div class="text-muted small">Henüz masa oluşturulmadı.</div>'}
                    </div>
                </div>
            </div>
        </form>
    `;

    initDropzone('logoDropzone', 'logoInput', (file) => uploadBrandAsset('logo', file));
    initDropzone('faviconDropzone', 'faviconInput', (file) => uploadBrandAsset('favicon', file));
    initDropzone('qrLogoDropzone', 'qrLogoInput', (file) => uploadBrandAsset('qr_logo', file));

    document.getElementById('settingsForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const raw = Object.fromEntries(formData.entries());
        raw.supported_languages = (raw.supported_languages || '').split(',').map((lang) => lang.trim()).filter(Boolean);
        raw.qr_transparent = formData.get('qr_transparent') === '1' || formData.get('qr_transparent') === 'on' || formData.get('qr_transparent') === 'true';
        raw.qr_width = Number(raw.qr_width || 420);
        raw.qr_height = Number(raw.qr_height || 420);
        raw.qr_color = raw.qr_color || '#000000';
        raw.qr_background = raw.qr_background || '#FFFFFF';
        const payload = raw;
        try {
            const result = await fetchJSON('/dashboard/settings', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            context.currency = payload.currency || context.currency;
            toast(result.message || 'Ayarlar kaydedildi', 'success');
            await loadSettings();
        } catch (error) {
            Swal.fire('Hata', error.error || 'Ayarlar kaydedilemedi', 'error');
        }
    });

    dashboardContent.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const value = btn.dataset.copy;
            if (!value) {
                Swal.fire('Bilgi', 'Kopyalanacak veri bulunamadı', 'info');
                return;
            }
            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(value);
                } else {
                    const temp = document.createElement('textarea');
                    temp.value = value;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                }
                toast('Panoya kopyalandı', 'success');
            } catch (error) {
                console.error('Copy failed', error);
                Swal.fire('Hata', 'Kopyalama başarısız', 'error');
            }
        });
    });

    const regenerateBtn = document.getElementById('regenerateApiKey');
    regenerateBtn?.addEventListener('click', async () => {
        const confirmation = await Swal.fire({
            title: 'API anahtarını yenile?',
            text: 'Eski anahtar artık çalışmayacak.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, yenile',
            cancelButtonText: 'Vazgeç',
        });
        if (!confirmation.isConfirmed) return;
        try {
            const { api: apiInfo } = await fetchJSON('/dashboard/settings/api', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'regenerate' }),
            });
            state.api = apiInfo;
            toast('API anahtarı yenilendi', 'success');
            renderSettings();
        } catch (error) {
            Swal.fire('Hata', error.error || 'API anahtarı yenilenemedi', 'error');
        }
    });
};

const uploadBrandAsset = async (type, file) => {
    const formData = new FormData();
    if (type === 'logo') formData.append('logo', file);
    if (type === 'favicon') formData.append('favicon', file);
    if (type === 'qr_logo') formData.append('qr_logo', file);
    try {
        const { branding } = await fetchJSON('/dashboard/settings', {
            method: 'POST',
            body: formData,
        });
        if (branding?.logo_url) {
            const preview = document.getElementById('logoPreview');
            if (preview) preview.src = branding.logo_url;
        }
        if (branding?.favicon_url) {
            const preview = document.getElementById('faviconPreview');
            if (preview) preview.src = branding.favicon_url;
        }
        if (branding?.qr_logo_url) {
            const preview = document.getElementById('qrLogoPreview');
            if (preview) {
                preview.src = branding.qr_logo_url;
            }
        }
        toast('Görseller güncellendi', 'success');
        await loadSettings();
    } catch (error) {
        Swal.fire('Hata', error.error || 'Yükleme başarısız', 'error');
    }
};

const loadReports = async (range = 'month') => {
    try {
        const { report } = await fetchJSON('/dashboard/reports', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ range }),
        });
        state.report = report;
        renderReports(range);
    } catch (error) {
        Swal.fire('Hata', error.error || 'Rapor verisi alınamadı', 'error');
    }
};

const renderReports = (range) => {
    dashboardContent.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h5 mb-0">Raporlar</h2>
                <small class="text-muted">Seçili döneme ait sipariş ve gelir verileri</small>
            </div>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" id="reportRange" style="width: 150px;">
                    <option value="day" ${range === 'day' ? 'selected' : ''}>Günlük</option>
                    <option value="week" ${range === 'week' ? 'selected' : ''}>Haftalık</option>
                    <option value="month" ${range === 'month' ? 'selected' : ''}>Aylık</option>
                    <option value="year" ${range === 'year' ? 'selected' : ''}>Yıllık</option>
                </select>
                <button class="btn btn-outline-success btn-sm" id="reportPdf"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</button>
            </div>
        </div>
        <canvas id="reportChart" height="120"></canvas>
    `;

    document.getElementById('reportRange').addEventListener('change', (e) => loadReports(e.target.value));
    document.getElementById('reportPdf').addEventListener('click', () => exportReportPdf());

    const ctx = document.getElementById('reportChart');
    const labels = state.report.map((row) => row.date);
    const orders = state.report.map((row) => Number(row.order_count || 0));
    const totals = state.report.map((row) => Number(row.total || 0));
    const completedTotals = state.report.map((row) => Number(row.completed_total || 0));
    const pendingRevenue = totals.map((total, index) => Math.max(total - completedTotals[index], 0));

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Sipariş Adedi',
                    data: orders,
                    backgroundColor: '#0ea5e9',
                    stack: 'count',
                    yAxisID: 'y',
                },
                {
                    label: 'Tamamlanan Ciro',
                    data: completedTotals,
                    backgroundColor: '#22c55e',
                    stack: 'revenue',
                    yAxisID: 'y1',
                },
                {
                    label: 'Bekleyen Ciro',
                    data: pendingRevenue,
                    backgroundColor: '#f59e0b',
                    stack: 'revenue',
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true } },
            scales: {
                x: { stacked: true },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    title: { display: true, text: 'Sipariş Adedi' },
                },
                y1: {
                    stacked: true,
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: `Ciro (${context.currency || 'TRY'})` },
                },
            },
        },
    });
};

const exportReportPdf = async () => {
    try {
        const rangeSelect = document.getElementById('reportRange');
        const range = rangeSelect ? rangeSelect.value : 'month';
        const payload = await fetchJSON('/dashboard/orders/export', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ range, format: 'pdf' }),
        });
        downloadBase64(payload.content, payload.filename, payload.mime);
    } catch (error) {
        Swal.fire('Hata', error.error || 'Rapor indirilemedi', 'error');
    }
};

const updateClock = () => {
    if (!clockEl) return;
    const now = new Date();
    clockEl.textContent = now.toLocaleString('tr-TR');
};

setInterval(updateClock, 1000);
updateClock();

logoutBtn?.addEventListener('click', async () => {
    await fetchJSON('/auth/logout', { method: 'POST' });
    window.location.href = '/';
});

dashboardLinks.forEach((link) => {
    link.addEventListener('click', async (event) => {
        event.preventDefault();
        dashboardLinks.forEach((l) => l.classList.remove('active'));
        link.classList.add('active');
        const page = link.dataset.page;
        state.currentPage = page;
        document.querySelector('.dashboard-layout')?.classList.remove('sidebar-open');
        switch (page) {
            case 'overview':
                renderOverview();
                break;
            case 'orders':
                await loadOrders({ range: state.orderRange, from: state.orderFilter.from, to: state.orderFilter.to });
                break;
            case 'tables':
                await loadTables();
                break;
            case 'menu':
                await loadMenuManager();
                break;
            case 'reports':
                await loadReports();
                break;
            case 'settings':
                await loadSettings();
                break;
            case 'calls':
                await loadCalls();
                break;
            default:
                renderOverview();
        }
    });
});

const initSocket = () => {
    if (!socket) return;
    socket.on('order:new', async (payload) => {
        playTone(980, 0.4);
        toast(`Masa ${payload.table_number} yeni sipariş verdi`, 'info');
        if (state.currentPage === 'orders' || state.currentPage === 'overview') {
            await loadOrders({ range: state.orderRange, from: state.orderFilter.from, to: state.orderFilter.to });
        }
        await loadTables();
        renderOverview();
    });
    socket.on('order:status', async (payload) => {
        playTone(payload.status === 'ready' ? 1200 : 760);
        toast(`Sipariş #${payload.order_id} ${statusLabels[payload.status]}`, 'success');
        if (state.currentPage === 'orders' || state.currentPage === 'overview') {
            await loadOrders({ range: state.orderRange, from: state.orderFilter.from, to: state.orderFilter.to });
        }
        await loadTables();
        renderOverview();
    });
    socket.on('waiter:call', async (payload) => {
        playTone(600, 0.5);
        toast(`Masa ${payload.table_number} garson çağırdı`, 'warning');
        await loadCalls(state.currentPage === 'calls');
        if (state.currentPage === 'overview') renderOverview();
    });
    socket.on('waiter:update', async () => {
        await loadCalls(state.currentPage === 'calls');
        if (state.currentPage === 'overview') renderOverview();
    });
    socket.on('table:status', async () => {
        await loadTables();
        if (state.currentPage === 'overview') {
            renderOverview();
        }
    });
};

const initSidebarToggle = () => {
    const layout = document.querySelector('.dashboard-layout');
    const sidebar = document.querySelector('.dashboard-sidebar');
    const toggleBtn = document.getElementById('dashboardSidebarToggle');
    if (!layout || !sidebar || !toggleBtn) return;
    toggleBtn.addEventListener('click', () => {
        layout.classList.toggle('sidebar-open');
    });
    document.addEventListener('click', (event) => {
        if (!layout.classList.contains('sidebar-open')) return;
        if (sidebar.contains(event.target) || toggleBtn.contains(event.target)) return;
        layout.classList.remove('sidebar-open');
    });
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            layout.classList.remove('sidebar-open');
        }
    });
};

const bootstrapDashboard = async () => {
    renderOverview();
    await Promise.all([
        loadOrders({ range: 'month' }),
        loadTables(),
        loadCalls(),
        loadMenuManager(),
        loadSettings(),
        loadReports(),
    ]);
    renderOverview();
};

initSocket();
initSidebarToggle();
bootstrapDashboard();
