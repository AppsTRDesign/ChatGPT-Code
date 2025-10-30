const dashboardContent = document.getElementById('dashboardContent');
const dashboardLinks = document.querySelectorAll('#dashboardApp .nav-link');
const logoutBtn = document.getElementById('logoutBtn');
const clockEl = document.getElementById('dashboardClock');
const context = window.dashboardContext || {};
const socket = context.socketUrl ? io(context.socketUrl, { auth: { restaurantId: String(context.restaurantId || '') } }) : null;

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

const formatCurrency = (amount = 0) => {
    return `${Number(amount || 0).toFixed(2)} ${(context.currency || 'TRY')}`;
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

const renderOrders = () => {
    const grouped = statusColumns.map((status) => ({
        status,
        orders: state.orders.filter((order) => order.status === status),
    }));

    dashboardContent.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Gelen Siparişler</h2>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" id="orderRange" style="width: 150px;">
                    <option value="day">Günlük</option>
                    <option value="week">Haftalık</option>
                    <option value="month" selected>Aylık</option>
                    <option value="year">Yıllık</option>
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
    `;

    document.getElementById('orderRange').addEventListener('change', (e) => loadOrders(e.target.value));
    document.getElementById('exportOrdersExcel').addEventListener('click', () => exportOrders('excel'));
    document.getElementById('exportOrdersPdf').addEventListener('click', () => exportOrders('pdf'));

    dashboardContent.querySelectorAll('[data-action]').forEach((button) => {
        button.addEventListener('click', handleOrderAction);
    });
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
                <span class="fw-semibold">${formatCurrency(order.total_amount)}</span>
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
            await loadOrders();
        } catch (error) {
            Swal.fire('Hata', error.error || 'Durum güncellenemedi', 'error');
        }
        return;
    }
    if (button.dataset.action === 'payment') {
        try {
            await fetchJSON('/dashboard/orders', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'payment', order_id: orderId }),
            });
            toast('Ödeme alındı', 'success');
            await loadOrders();
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
                <p class="mb-3"><strong>Toplam:</strong> ${formatCurrency(order.total_amount)}</p>
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
                    const data = await fetchJSON('/dashboard/orders/receipt', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order_number: order.order_number }),
                    });
                    generateCashReceipt(data.restaurant, data.order);
                } catch (error) {
                    Swal.fire('Hata', error.error || 'Adisyon hazırlanamadı', 'error');
                }
            });
        }
    });
};

const generateCashReceipt = (restaurant, order) => {
    const doc = new jspdf.jsPDF({ unit: 'mm', format: [80, 200] });
    const line = (y) => doc.line(4, y, 76, y);
    let y = 10;
    doc.setFontSize(12);
    doc.text(restaurant.name || 'Restoran', 40, y, { align: 'center' });
    doc.setFontSize(9);
    y += 5;
    doc.text(restaurant.address ? restaurant.address : '', 40, y, { align: 'center' });
    y += 8;
    line(y);
    y += 6;
    doc.text(`Sipariş #: ${order.order_number}`, 6, y);
    y += 5;
    doc.text(`Masa: ${order.table_number}`, 6, y);
    y += 5;
    doc.text(`Durum: ${statusLabels[order.status]}`, 6, y);
    y += 6;
    line(y);
    (order.items || []).forEach((item) => {
        y += 5;
        doc.text(`${item.quantity || 1} x ${item.name}`, 6, y);
        doc.text(`${Number(item.price || 0).toFixed(2)}`, 74, y, { align: 'right' });
    });
    y += 8;
    line(y);
    y += 6;
    doc.setFontSize(11);
    doc.text(`TOPLAM: ${Number(order.total_amount).toFixed(2)} ${(order.currency || context.currency)}`, 40, y, { align: 'center' });
    y += 8;
    doc.setFontSize(8);
    doc.text('NoaSoft QR Menü sistemi ile hazırlanmıştır.', 40, y, { align: 'center' });
    doc.save(`adisyon-${order.order_number}.pdf`);
};

const generateOrdersPdf = (orders = [], restaurant = {}, range = {}) => {
    const doc = new jspdf.jsPDF();
    const title = `${restaurant.name || 'Restoran'} - Sipariş Raporu`;
    doc.setFontSize(14);
    doc.text(title, 14, 18);
    doc.setFontSize(10);
    if (range.from && range.to) {
        doc.text(`Dönem: ${range.from} - ${range.to}`, 14, 26);
    }
    let y = 36;
    doc.setFontSize(9);
    doc.text('Sipariş', 14, y);
    doc.text('Masa', 60, y);
    doc.text('Durum', 96, y);
    doc.text('Toplam', 132, y);
    doc.text('Tarih', 186, y, { align: 'right' });
    y += 6;

    orders.forEach((order, index) => {
        const orderNumber = order.order_number || order.id || `#${index + 1}`;
        const status = statusLabels[order.status] || order.status || '-';
        const total = `${Number(order.total_amount || 0).toFixed(2)} ${(order.currency || context.currency || 'TRY')}`;
        const createdAt = (order.created_at || '').replace('T', ' ').slice(0, 19);

        doc.text(String(orderNumber), 14, y);
        doc.text(order.table_number || '-', 60, y);
        doc.text(status, 96, y);
        doc.text(total, 132, y, { align: 'right' });
        doc.text(createdAt, 186, y, { align: 'right' });
        y += 6;

        if (y > 270 && index < orders.length - 1) {
            doc.addPage();
            y = 20;
        }
    });

    const fileName = `siparis-raporu-${new Date().toISOString().slice(0, 10)}.pdf`;
    doc.save(fileName);
};

const exportOrders = async (format = 'excel') => {
    try {
        const range = document.getElementById('orderRange').value;
        const response = await fetchJSON('/dashboard/orders/export', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ range, format }),
        });

        if (format === 'excel') {
            const { content, filename, mime } = response;
            const blob = new Blob([Uint8Array.from(atob(content), (c) => c.charCodeAt(0))], { type: mime });
            saveAs(blob, filename);
            return;
        }

        if (format === 'pdf') {
            generateOrdersPdf(response.orders, response.restaurant, response.range);
            return;
        }

        toast('Bilinmeyen format için veri alındı', 'info');
    } catch (error) {
        Swal.fire('Hata', error.error || 'Rapor indirilemedi', 'error');
    }
};

const loadOrders = async (range = 'month') => {
    try {
        const params = new URLSearchParams();
        params.set('range', range);
        const { orders, metrics, tables } = await fetchJSON(`/dashboard/orders?${params.toString()}`);
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
        const { tables } = await fetchJSON('/dashboard/tables');
        state.tables = tables;
        renderTables();
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
    const tableUrl = `${context.baseUrl}/menu/${context.restaurantSlug}/table/${table.slug}?token=${table.qr_token}`;
    const qrUrl = `${context.qrApi}?token=ff47a9fc9403a50f45662cbef42cb6ca864b8237ff1838f176124ba1201631bf&type=url&url=${encodeURIComponent(tableUrl)}`;
    return `
        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="h6 mb-0">${table.name}</h3>
                <span class="badge ${table.status === 'occupied' ? 'bg-danger' : 'bg-success'}">${table.status === 'occupied' ? 'Dolu' : 'Boş'}</span>
            </div>
            <p class="text-muted small mb-3">${tableUrl}</p>
            <div class="qr-frame mb-3">
                <img src="${qrUrl}" alt="QR" class="img-fluid">
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm flex-grow-1" data-table-action="status" data-id="${table.id}" data-status="${table.status === 'occupied' ? 'vacant' : 'occupied'}">${table.status === 'occupied' ? 'Boş Olarak İşaretle' : 'Dolu Olarak İşaretle'}</button>
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
    const { tableAction: action, id, status } = event.currentTarget.dataset;
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
    if (action === 'status') {
        try {
            await fetchJSON('/dashboard/tables', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'status', id, status }),
            });
            toast('Masa durumu güncellendi', 'success');
            await loadTables();
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
                                    <div class="fw-semibold">${category.name}</div>
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
    Swal.fire({
        title: category ? 'Kategori Düzenle' : 'Yeni Kategori',
        html: `
            <div class="text-start">
                <label class="form-label">Kategori Adı</label>
                <input type="text" id="categoryName" class="form-control" value="${category?.name || ''}">
                <label class="form-label mt-2">Açıklama</label>
                <textarea id="categoryDescription" class="form-control" rows="2">${category?.description || ''}</textarea>
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
        didOpen: () => initDropzone('categoryDropzone', 'categoryImage', async (file) => {
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
        }),
        preConfirm: () => ({
            name: document.getElementById('categoryName').value,
            description: document.getElementById('categoryDescription').value,
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
                            <input type="text" class="form-control" name="currency" value="${settings.currency || 'TRY'}">
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

    document.getElementById('settingsForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const payload = Object.fromEntries(formData.entries());
        payload.supported_languages = payload.supported_languages.split(',').map((lang) => lang.trim()).filter(Boolean);
        try {
            await fetchJSON('/dashboard/settings', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            context.currency = payload.currency || context.currency;
            state.settings = { ...state.settings, ...payload };
            toast('Ayarlar kaydedildi', 'success');
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
    const orders = state.report.map((row) => Number(row.order_count));
    const totals = state.report.map((row) => Number(row.total));

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Sipariş',
                    data: orders,
                    borderColor: '#0ea5e9',
                    tension: 0.4,
                    fill: false,
                },
                {
                    label: 'Ciro',
                    data: totals,
                    borderColor: '#22c55e',
                    tension: 0.4,
                    fill: false,
                },
            ],
        },
        options: {
            plugins: { legend: { display: true } },
            scales: {
                y: { beginAtZero: true },
            },
        },
    });
};

const exportReportPdf = () => {
    const doc = new jspdf.jsPDF();
    doc.setFontSize(14);
    doc.text('NoaSoft QR Menü - Rapor', 14, 20);
    doc.setFontSize(10);
    let y = 32;
    state.report.forEach((row) => {
        doc.text(`${row.date} - Sipariş: ${row.order_count} - Ciro: ${Number(row.total).toFixed(2)} ${context.currency}`, 14, y);
        y += 8;
    });
    doc.save('rapor.pdf');
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
        switch (page) {
            case 'overview':
                renderOverview();
                break;
            case 'orders':
                await loadOrders();
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
            await loadOrders();
            renderOverview();
        }
    });
    socket.on('order:status', async (payload) => {
        playTone(payload.status === 'ready' ? 1200 : 760);
        toast(`Sipariş #${payload.order_id} ${statusLabels[payload.status]}`, 'success');
        if (state.currentPage === 'orders' || state.currentPage === 'overview') {
            await loadOrders();
            renderOverview();
        }
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
};

const bootstrapDashboard = async () => {
    renderOverview();
    await Promise.all([loadOrders(), loadTables(), loadCalls(), loadMenuManager(), loadSettings(), loadReports()]);
    renderOverview();
};

initSocket();
bootstrapDashboard();
