<?php
session_start();
$config = require __DIR__ . '/../config.php';
$loggedIn = !empty($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Menü Yönetim Paneli</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <style>
        body { background: #f3f4f7; }
        .nav-pills .nav-link.active { background-color: #0d6efd; }
        .card-shadow { box-shadow: 0 1rem 3rem rgba(0,0,0,.1); }
        .product-card { border-radius: 1rem; border: 1px solid #e5e7eb; padding: 1rem; background: #fff; }
        .dropzone { border: 2px dashed #4f46e5; border-radius: 1rem; background: #eef2ff; }
    </style>
</head>
<body>
<div class="container py-5">
    <div id="loginView" class="row justify-content-center <?= $loggedIn ? 'd-none' : '' ?>">
        <div class="col-md-6">
            <div class="card card-shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 mb-2">Yönetici Girişi</h1>
                        <p class="text-muted">qrmenu.noasoft.org yönetim paneline hoş geldiniz.</p>
                    </div>
                    <form id="loginForm">
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" name="username" required value="admin">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="password" class="form-control" name="password" required value="admin">
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Giriş Yap</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="appView" class="<?= $loggedIn ? '' : 'd-none' ?>">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3">QR Menü Yönetim Paneli</h1>
                <p class="text-muted mb-0">Gerçek zamanlı sipariş, menü ve masa yönetimi.</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-success" id="adminUser">Hoş geldiniz</span>
                <button type="button" class="btn btn-outline-danger btn-sm" id="logoutButton">Çıkış Yap</button>
            </div>
        </div>

        <ul class="nav nav-pills mb-4" id="panelTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-dashboard" type="button">Dashboard</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-menu" type="button">Menü Yönetimi</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-tables" type="button">Masa Yönetimi</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-orders" type="button">Siparişler</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-calls" type="button">Garson Çağrıları</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-dashboard">
                <div class="row g-4" id="dashboardStats"></div>
                <div class="card card-shadow mt-4">
                    <div class="card-header">Son Siparişler</div>
                    <div class="card-body" id="recentOrders"></div>
                </div>
            </div>
            <div class="tab-pane fade" id="tab-menu">
                <div class="card card-shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h5 mb-0">Menü Düzenleme</h2>
                            <small class="text-muted">Kategori ve ürünlerinizi dinamik olarak yönetin.</small>
                        </div>
                        <div>
                            <button class="btn btn-outline-secondary btn-sm" id="addCategory"><i class="bi bi-plus-lg"></i> Kategori Ekle</button>
                            <button class="btn btn-primary btn-sm" id="saveMenu"><i class="bi bi-save"></i> Kaydet</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-4" id="brandingContainer">
                            <div class="col-md-4">
                                <label class="form-label">Logo URL</label>
                                <input type="text" class="form-control" id="brandingLogo" placeholder="https://...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Banner URL</label>
                                <input type="text" class="form-control" id="brandingBanner" placeholder="https://...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Karşılama Mesajı</label>
                                <input type="text" class="form-control" id="brandingMessage" placeholder="Hoş geldiniz mesajı">
                            </div>
                        </div>
                        <div class="row g-4" id="categoriesContainer"></div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="tab-tables">
                <div class="card card-shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h5 mb-0">Masalar</h2>
                            <small class="text-muted">Her masa için benzersiz QR kodları oluşturun.</small>
                        </div>
                        <div>
                            <button class="btn btn-outline-secondary btn-sm" id="addTable"><i class="bi bi-plus-lg"></i> Masa Ekle</button>
                            <button class="btn btn-primary btn-sm" id="saveTables"><i class="bi bi-save"></i> Kaydet</button>
                        </div>
                    </div>
                    <div class="card-body" id="tablesContainer"></div>
                </div>
            </div>
            <div class="tab-pane fade" id="tab-orders">
                <div class="card card-shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Aktif Siparişler</h2>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" id="orderFilter" style="width:200px;">
                                <option value="">Tümü</option>
                                <option value="pending">Bekliyor</option>
                                <option value="preparing">Hazırlanıyor</option>
                                <option value="ready">Servise Hazır</option>
                                <option value="completed">Tamamlandı</option>
                            </select>
                            <button class="btn btn-outline-secondary btn-sm" id="refreshOrders"><i class="bi bi-arrow-repeat"></i></button>
                        </div>
                    </div>
                    <div class="card-body" id="ordersContainer"></div>
                </div>
            </div>
            <div class="tab-pane fade" id="tab-calls">
                <div class="card card-shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Garson Çağrıları</h2>
                        <button class="btn btn-outline-secondary btn-sm" id="refreshCalls"><i class="bi bi-arrow-repeat"></i></button>
                    </div>
                    <div class="card-body" id="callsContainer"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ürün Görseli Yükle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <form id="imageDropzone" class="dropzone"></form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CONFIG = {
    orderSound: <?= json_encode($config['order_sound_url'] ?? '') ?>,
    waiterSound: <?= json_encode($config['waiter_sound_url'] ?? '') ?>,
    loggedIn: <?= $loggedIn ? 'true' : 'false' ?>,
    username: <?= json_encode($loggedIn ? ($_SESSION['admin']['username'] ?? 'admin') : null) ?>
};

const state = {
    categories: [],
    branding: {},
    tables: [],
    dashboard: {},
    orders: [],
    calls: [],
    activeProductRef: null,
    lastOrderCount: 0,
    lastCallCount: 0,
    orderIntervalId: null,
    callIntervalId: null
};

Dropzone.autoDiscover = false;
let dropzoneInstance;

function initDropzone() {
    const dzElement = document.getElementById('imageDropzone');
    dropzoneInstance = new Dropzone(dzElement, {
        url: '/admin/api/upload.php',
        maxFiles: 1,
        acceptedFiles: 'image/*',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        init() {
            this.on('success', (file, response) => {
                if (response && response.url && state.activeProductRef) {
                    state.activeProductRef.querySelector('.product-image').value = response.url;
                    state.activeProductRef.querySelector('.product-image-preview').src = response.url;
                    Swal.fire('Başarılı', 'Görsel yüklendi.', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('imageModal'));
                    modal.hide();
                }
                this.removeAllFiles();
            });
            this.on('error', (file, message) => {
                const errorMessage = typeof message === 'string' ? message : (message?.error || 'Dosya yükleme başarısız.');
                Swal.fire('Hata', errorMessage, 'error');
                this.removeAllFiles();
            });
        }
    });
}

function showError(message) {
    Swal.fire('Hata', message, 'error');
}

function showSuccess(message) {
    Swal.fire('Başarılı', message, 'success');
}

async function api(path, options = {}) {
    const headers = { 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) };
    if (typeof options.body !== 'undefined' && !headers['Content-Type']) {
        headers['Content-Type'] = 'application/json';
    }
    const response = await fetch(path, {
        credentials: 'include',
        ...options,
        headers
    });
    if (!response.ok) {
        const data = await response.json().catch(() => ({ error: 'Bilinmeyen hata' }));
        throw new Error(data.error || 'İstek başarısız');
    }
    return response.json();
}

function renderDashboard() {
    const stats = state.dashboard.stats || {};
    const container = document.getElementById('dashboardStats');
    container.innerHTML = '';
    const items = [
        { title: 'Toplam Sipariş', value: stats.total_orders || 0, icon: 'bi-clipboard-check', color: 'primary' },
        { title: 'Bekleyen Sipariş', value: stats.pending_orders || 0, icon: 'bi-hourglass-split', color: 'warning' },
        { title: 'Aktif Masalar', value: stats.active_tables || 0, icon: 'bi-people', color: 'success' },
        { title: 'Garson Çağrıları', value: stats.waiter_calls || 0, icon: 'bi-bell', color: 'danger' },
    ];
    items.forEach(item => {
        const col = document.createElement('div');
        col.className = 'col-md-3';
        col.innerHTML = `
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="text-${item.color}">${item.value}</h5>
                            <p class="text-muted mb-0">${item.title}</p>
                        </div>
                        <span class="display-6 text-${item.color}"><i class="bi ${item.icon}"></i></span>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(col);
    });
    const recentContainer = document.getElementById('recentOrders');
    const recent = state.dashboard.recent_orders || [];
    if (!recent.length) {
        recentContainer.innerHTML = '<p class="text-muted mb-0">Son sipariş bulunmuyor.</p>';
        return;
    }
    recentContainer.innerHTML = recent.map(order => `
        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
            <div>
                <strong>#${order.id}</strong> - ${order.table_name}
                <span class="badge bg-light text-dark ms-2">${order.status.toUpperCase()}</span>
            </div>
            <div>${Number(order.total).toFixed(2)} ₺</div>
        </div>
    `).join('');
}

function renderCategories() {
    const container = document.getElementById('categoriesContainer');
    container.innerHTML = '';
    state.categories.forEach((category, index) => {
        const col = document.createElement('div');
        col.className = 'col-12';
        col.innerHTML = `
            <div class="card border-0 card-shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="w-50">
                            <input class="form-control form-control-lg mb-2 category-name" value="${category.name || ''}" placeholder="Kategori adı">
                            <textarea class="form-control category-description" rows="2" placeholder="Açıklama">${category.description || ''}</textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm move-up" data-index="${index}"><i class="bi bi-arrow-up"></i></button>
                            <button class="btn btn-outline-secondary btn-sm move-down" data-index="${index}"><i class="bi bi-arrow-down"></i></button>
                            <button class="btn btn-outline-danger btn-sm remove-category" data-index="${index}"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                    <div class="row g-3 products" data-index="${index}"></div>
                    <button class="btn btn-outline-primary btn-sm mt-3 add-product" data-index="${index}"><i class="bi bi-plus-lg"></i> Ürün Ekle</button>
                </div>
            </div>
        `;
        container.appendChild(col);
        const productsContainer = col.querySelector('.products');
        (category.products || []).forEach((product, pIndex) => {
            productsContainer.appendChild(createProductCard(index, pIndex, product));
        });
    });
}

function renderBranding() {
    document.getElementById('brandingLogo').value = state.branding.logo || '';
    document.getElementById('brandingBanner').value = state.branding.banner || '';
    document.getElementById('brandingMessage').value = state.branding.message || '';
}

function createProductCard(categoryIndex, productIndex, product = {}) {
    const div = document.createElement('div');
    const imageUrl = product.image_path || product.image || 'https://via.placeholder.com/300x200?text=Ürün';
    const isActive = Number(product.is_active ?? 1) === 1 ? 'checked' : '';
    div.className = 'col-md-4';
    div.innerHTML = `
        <div class="product-card" data-category="${categoryIndex}" data-product="${productIndex}">
            <img src="${imageUrl}" class="img-fluid rounded mb-3 product-image-preview" alt="Ürün görseli">
            <input type="hidden" class="product-image" value="${product.image_path || product.image || ''}">
            <div class="mb-2">
                <label class="form-label">Ürün Adı</label>
                <input class="form-control product-name" value="${product.name || ''}">
            </div>
            <div class="mb-2">
                <label class="form-label">Açıklama</label>
                <textarea class="form-control product-description" rows="2">${product.description || ''}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Fiyat (₺)</label>
                <input type="number" step="0.01" class="form-control product-price" value="${product.price || 0}">
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input class="form-check-input product-active" type="checkbox" ${isActive}>
                    <label class="form-check-label">Aktif</label>
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-secondary btn-sm upload-image"><i class="bi bi-cloud-upload"></i></button>
                    <button class="btn btn-outline-danger btn-sm remove-product"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        </div>
    `;
    return div;
}

function renderTables() {
    const container = document.getElementById('tablesContainer');
    container.innerHTML = '';
    if (!state.tables.length) {
        container.innerHTML = '<p class="text-muted">Tanımlı masa bulunmuyor. Yeni masa ekleyin.</p>';
        return;
    }
    const list = document.createElement('div');
    list.className = 'row g-3';
    state.tables.forEach((table, index) => {
        const col = document.createElement('div');
        col.className = 'col-md-4';
        col.innerHTML = `
            <div class="card border-0 card-shadow h-100">
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label">Masa Adı</label>
                        <input class="form-control table-name" value="${table.name || ''}" data-index="${index}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">QR Token</label>
                        <div class="input-group">
                            <input class="form-control table-token" value="${table.token || ''}" data-index="${index}">
                            <button class="btn btn-outline-secondary generate-token" data-index="${index}"><i class="bi bi-arrow-repeat"></i></button>
                        </div>
                    </div>
                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <span class="badge bg-light text-dark">Durum: ${table.status || 'available'}</span>
                        <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= rtrim($config['base_url'], '/') ?>/index.php?table=${table.token}">QR Aç</a>
                    </div>
                </div>
            </div>
        `;
        list.appendChild(col);
    });
    container.appendChild(list);
}

function renderOrders() {
    const container = document.getElementById('ordersContainer');
    container.innerHTML = '';
    if (!state.orders.length) {
        container.innerHTML = '<p class="text-muted">Sipariş bulunmuyor.</p>';
        return;
    }
    state.orders.forEach(order => {
        const div = document.createElement('div');
        div.className = 'card border-0 card-shadow mb-3';
        const items = order.items || [];
        div.innerHTML = `
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <strong>#${order.id}</strong> - ${order.table_name}
                        <span class="badge bg-light text-dark ms-2">${order.status.toUpperCase()}</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-success btn-sm" data-action="next" data-id="${order.id}">Durum İlerlet</button>
                        <a class="btn btn-outline-secondary btn-sm" href="/admin/api/invoice.php?order_id=${order.id}" target="_blank">Adisyon</a>
                    </div>
                </div>
                <ul class="list-unstyled mb-2">
                    ${items.map(item => `<li>${item.quantity} x ${item.name} - ${(item.price * item.quantity).toFixed(2)} ₺</li>`).join('')}
                </ul>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Toplam: ${Number(order.total).toFixed(2)} ₺</span>
                    <button class="btn btn-outline-primary btn-sm" data-action="complete" data-id="${order.id}">Tamamlandı</button>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function renderCalls() {
    const container = document.getElementById('callsContainer');
    container.innerHTML = '';
    if (!state.calls.length) {
        container.innerHTML = '<p class="text-muted">Bekleyen garson çağrısı yok.</p>';
        return;
    }
    state.calls.forEach(call => {
        const div = document.createElement('div');
        div.className = 'card border-0 card-shadow mb-3';
        div.innerHTML = `
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <strong>${call.table_name}</strong>
                    <span class="badge bg-${call.status === 'pending' ? 'danger' : 'secondary'} ms-2">${call.status.toUpperCase()}</span>
                    <div class="text-muted small">${call.created_at}</div>
                </div>
                <button class="btn btn-outline-success btn-sm" data-id="${call.id}">Tamamlandı</button>
            </div>
        `;
        container.appendChild(div);
    });
}

function serializeCategories() {
    const cards = document.querySelectorAll('#categoriesContainer .card');
    return state.categories.map((category, index) => {
        const categoryCard = cards[index];
        const name = categoryCard.querySelector('.category-name').value.trim();
        const description = categoryCard.querySelector('.category-description').value.trim();
        const products = Array.from(categoryCard.querySelectorAll('.product-card')).map(card => ({
            name: card.querySelector('.product-name').value.trim(),
            description: card.querySelector('.product-description').value.trim(),
            price: parseFloat(card.querySelector('.product-price').value) || 0,
            image_path: card.querySelector('.product-image').value.trim(),
            is_active: card.querySelector('.product-active').checked ? 1 : 0,
        }));
        return { name, description, products };
    });
}

function serializeTables() {
    return Array.from(document.querySelectorAll('.table-name')).map((input, index) => ({
        name: input.value.trim() || `Masa ${index + 1}`,
        token: document.querySelectorAll('.table-token')[index].value.trim(),
        status: state.tables[index]?.status || 'available'
    }));
}

function nextStatus(current) {
    const flow = ['pending', 'preparing', 'ready', 'completed'];
    const idx = flow.indexOf(current);
    return flow[Math.min(idx + 1, flow.length - 1)] || 'completed';
}

async function loadDashboard() {
    try {
        state.dashboard = await api('/admin/api/dashboard.php');
        renderDashboard();
    } catch (error) {
        showError(error.message);
    }
}

async function loadMenu() {
    try {
        const data = await api('/admin/api/menu.php');
        state.categories = data.categories || [];
        state.branding = data.branding || {};
        renderBranding();
        renderCategories();
    } catch (error) {
        showError(error.message);
    }
}

async function loadTables() {
    try {
        const data = await api('/admin/api/tables.php');
        state.tables = data.tables || [];
        renderTables();
    } catch (error) {
        showError(error.message);
    }
}

async function loadOrders(showAlert = false) {
    try {
        const status = document.getElementById('orderFilter').value;
        const data = await api('/admin/api/orders.php' + (status ? `?status=${status}` : ''));
        const hadOrders = state.orders.length > 0;
        const previousPending = state.orders.filter(order => order.status === 'pending').length;
        state.orders = data.orders || [];
        renderOrders();
        const currentPending = state.orders.filter(order => order.status === 'pending').length;
        if ((hadOrders && currentPending > previousPending) || showAlert) {
            playSound(CONFIG.orderSound);
            Swal.fire('Yeni Sipariş', 'Yeni bir sipariş alındı!', 'info');
        }
    } catch (error) {
        showError(error.message);
    }
}

async function loadCalls(showAlert = false) {
    try {
        const data = await api('/admin/api/waiter_calls.php');
        const hadCalls = state.calls.length > 0;
        const previous = state.calls.filter(call => call.status === 'pending').length;
        state.calls = data.calls || [];
        renderCalls();
        const current = state.calls.filter(call => call.status === 'pending').length;
        if ((hadCalls && current > previous) || showAlert) {
            playSound(CONFIG.waiterSound);
            Swal.fire('Garson Çağrısı', 'Yeni bir garson çağrısı var!', 'warning');
        }
    } catch (error) {
        showError(error.message);
    }
}

function playSound(url) {
    if (!url) return;
    const audio = new Audio(url);
    audio.play();
}

function bindEvents() {
    document.getElementById('loginForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const payload = {
            username: form.username.value.trim(),
            password: form.password.value
        };
        try {
            const result = await api('/admin/api/login.php', { method: 'POST', body: JSON.stringify(payload) });
            showSuccess('Giriş başarılı.');
            document.getElementById('loginView').classList.add('d-none');
            document.getElementById('appView').classList.remove('d-none');
            document.getElementById('adminUser').textContent = `Hoş geldiniz, ${result.user.username}`;
            CONFIG.loggedIn = true;
            CONFIG.username = result.user.username;
            initApp();
        } catch (error) {
            showError(error.message);
        }
    });

    document.getElementById('logoutButton')?.addEventListener('click', async (event) => {
        event.preventDefault();
        try {
            await api('/admin/logout.php', { method: 'POST', body: '{}' });
            showSuccess('Oturum kapatıldı.');
            CONFIG.loggedIn = false;
            CONFIG.username = null;
            if (state.orderIntervalId) {
                clearInterval(state.orderIntervalId);
                state.orderIntervalId = null;
            }
            if (state.callIntervalId) {
                clearInterval(state.callIntervalId);
                state.callIntervalId = null;
            }
            document.getElementById('appView').classList.add('d-none');
            document.getElementById('loginView').classList.remove('d-none');
            document.getElementById('adminUser').textContent = 'Hoş geldiniz';
        } catch (error) {
            showError(error.message);
        }
    });

    document.addEventListener('click', async (event) => {
        if (event.target.closest('#addCategory')) {
            state.categories.push({ name: 'Yeni Kategori', description: '', products: [] });
            renderCategories();
        } else if (event.target.closest('.remove-category')) {
            const index = Number(event.target.closest('button').dataset.index);
            state.categories.splice(index, 1);
            renderCategories();
        } else if (event.target.closest('.move-up')) {
            const index = Number(event.target.closest('button').dataset.index);
            if (index > 0) {
                [state.categories[index - 1], state.categories[index]] = [state.categories[index], state.categories[index - 1]];
                renderCategories();
            }
        } else if (event.target.closest('.move-down')) {
            const index = Number(event.target.closest('button').dataset.index);
            if (index < state.categories.length - 1) {
                [state.categories[index + 1], state.categories[index]] = [state.categories[index], state.categories[index + 1]];
                renderCategories();
            }
        } else if (event.target.closest('.add-product')) {
            const index = Number(event.target.closest('button').dataset.index);
            state.categories[index].products = state.categories[index].products || [];
            state.categories[index].products.push({ name: 'Yeni Ürün', price: 0, description: '', is_active: 1 });
            renderCategories();
        } else if (event.target.closest('.remove-product')) {
            const card = event.target.closest('.product-card');
            const categoryIndex = Number(card.dataset.category);
            const productIndex = Number(card.dataset.product);
            state.categories[categoryIndex].products.splice(productIndex, 1);
            renderCategories();
        } else if (event.target.closest('.upload-image')) {
            state.activeProductRef = event.target.closest('.product-card');
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        } else if (event.target.closest('#saveMenu')) {
            saveMenu();
        } else if (event.target.closest('#addTable')) {
            state.tables.push({ name: `Masa ${state.tables.length + 1}`, token: Math.random().toString(36).substring(2, 8), status: 'available' });
            renderTables();
        } else if (event.target.closest('.generate-token')) {
            const index = Number(event.target.closest('button').dataset.index);
            document.querySelectorAll('.table-token')[index].value = Math.random().toString(36).substring(2, 8);
        } else if (event.target.closest('#saveTables')) {
            saveTables();
        } else if (event.target.closest('#refreshOrders')) {
            loadOrders(true);
        } else if (event.target.closest('#refreshCalls')) {
            loadCalls(true);
        } else if (event.target.closest('[data-action="next"]')) {
            const orderId = Number(event.target.closest('button').dataset.id);
            const order = state.orders.find(o => o.id === orderId);
            updateOrderStatus(orderId, nextStatus(order.status));
        } else if (event.target.closest('[data-action="complete"]')) {
            const orderId = Number(event.target.closest('button').dataset.id);
            updateOrderStatus(orderId, 'completed');
        } else if (event.target.closest('#callsContainer button')) {
            const callId = Number(event.target.closest('button').dataset.id);
            acknowledgeCall(callId);
        }
    });
    document.getElementById('orderFilter')?.addEventListener('change', () => loadOrders());
}

async function saveMenu() {
    try {
        const branding = {
            logo: document.getElementById('brandingLogo').value.trim(),
            banner: document.getElementById('brandingBanner').value.trim(),
            message: document.getElementById('brandingMessage').value.trim(),
        };
        const payload = {
            categories: serializeCategories(),
            branding
        };
        await api('/admin/api/save_menu.php', { method: 'POST', body: JSON.stringify(payload) });
        showSuccess('Menü başarıyla güncellendi.');
        loadMenu();
    } catch (error) {
        showError(error.message);
    }
}

async function saveTables() {
    try {
        const payload = { tables: serializeTables() };
        await api('/admin/api/tables.php', { method: 'POST', body: JSON.stringify(payload) });
        showSuccess('Masalar güncellendi.');
        loadTables();
    } catch (error) {
        showError(error.message);
    }
}

async function updateOrderStatus(orderId, status) {
    try {
        await api('/admin/api/update_order.php', { method: 'POST', body: JSON.stringify({ order_id: orderId, status }) });
        showSuccess('Sipariş güncellendi.');
        loadOrders();
    } catch (error) {
        showError(error.message);
    }
}

async function acknowledgeCall(callId) {
    try {
        await api('/admin/api/handle_call.php', { method: 'POST', body: JSON.stringify({ call_id: callId }) });
        showSuccess('Çağrı tamamlandı.');
        loadCalls();
    } catch (error) {
        showError(error.message);
    }
}

async function initApp() {
    if (!CONFIG.loggedIn) return;
    document.getElementById('adminUser').textContent = CONFIG.username ? `Hoş geldiniz, ${CONFIG.username}` : 'Hoş geldiniz';
    await Promise.all([loadDashboard(), loadMenu(), loadTables(), loadOrders(), loadCalls()]);
    if (state.orderIntervalId) {
        clearInterval(state.orderIntervalId);
    }
    if (state.callIntervalId) {
        clearInterval(state.callIntervalId);
    }
    state.orderIntervalId = setInterval(() => loadOrders(), 10000);
    state.callIntervalId = setInterval(() => loadCalls(), 12000);
}

bindEvents();
if (CONFIG.loggedIn) {
    initDropzone();
    initApp();
} else {
    initDropzone();
}
</script>
</body>
</html>
