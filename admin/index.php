<?php
session_start();
require_once __DIR__ . '/../lib/MenuService.php';
require_once __DIR__ . '/../lib/OrderService.php';
require_once __DIR__ . '/../lib/helpers.php';

$isAuthenticated = $_SESSION['admin_authenticated'] ?? false;
$menuService = new MenuService();
$orderService = new OrderService();
$menu = $menuService->getMenu();
$tables = $menuService->getTables();
$stats = $orderService->getStats();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Menü Yönetimi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #f3f4f6; }
        .sidebar { min-height: 100vh; background: #111827; color: #fff; }
        .sidebar a { color: rgba(255,255,255,.8); text-decoration: none; display: block; padding: .75rem 1rem; border-radius: .5rem; }
        .sidebar a.active, .sidebar a:hover { background: rgba(59,130,246,.4); color: #fff; }
        .card { border: none; border-radius: 1rem; }
        .card h3 { font-size: 1.5rem; }
        .drag-handle { cursor: grab; }
        .qr-preview img { max-width: 180px; }
        .login-wrapper { min-height: 100vh; display: flex; justify-content: center; align-items: center; background: linear-gradient(135deg,#1f2937,#111827); }
        .login-card { width: 100%; max-width: 420px; }
        .call-indicator { animation: pulse 1.5s infinite; }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220,38,38, 0.5); }
            70% { box-shadow: 0 0 0 15px rgba(220,38,38, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220,38,38, 0); }
        }
    </style>
</head>
<body>
<?php if (!$isAuthenticated): ?>
<div class="login-wrapper text-white">
    <div class="card login-card shadow-lg">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <div class="display-6">QR Menü Yönetim</div>
                <p class="text-muted">Devam etmek için yönetici şifresini giriniz.</p>
            </div>
            <form id="loginForm" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label class="form-label">Yönetici Şifresi</label>
                    <input type="password" class="form-control" id="password" required>
                    <div class="invalid-feedback">Lütfen şifre giriniz.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
            </form>
        </div>
    </div>
</div>
<script>
const loginForm = document.getElementById('loginForm');
loginForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!loginForm.checkValidity()) {
        loginForm.classList.add('was-validated');
        return;
    }
    const password = document.getElementById('password').value;
    const response = await fetch('api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ password })
    });
    const result = await response.json();
    if (result.success) {
        location.reload();
    } else {
        alert(result.error || 'Giriş başarısız');
    }
});
</script>
<?php else: ?>
<div class="container-fluid">
    <div class="row">
        <aside class="col-12 col-md-3 col-xl-2 p-4 sidebar">
            <h1 class="h4 mb-4">Yönetim Paneli</h1>
            <nav class="nav flex-column" id="adminNav">
                <a href="#dashboard" class="active" data-target="dashboard"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
                <a href="#categories" data-target="categories"><i class="bi bi-list-ul me-2"></i>Kategori Yönetimi</a>
                <a href="#products" data-target="products"><i class="bi bi-basket me-2"></i>Ürün Yönetimi</a>
                <a href="#media" data-target="media"><i class="bi bi-image me-2"></i>Medya & Özelleştirme</a>
                <a href="#tables" data-target="tables"><i class="bi bi-qr-code me-2"></i>Masa Yönetimi</a>
                <a href="#orders" data-target="orders"><i class="bi bi-receipt me-2"></i>Adisyon & Siparişler</a>
                <a href="#waiter" data-target="waiter"><i class="bi bi-bell me-2"></i>Garson Çağrıları</a>
                <a href="logout.php" class="mt-4"><i class="bi bi-box-arrow-right me-2"></i>Çıkış Yap</a>
            </nav>
        </aside>
        <main class="col-12 col-md-9 col-xl-10 py-4 px-4 px-xl-5">
            <div id="dashboard" class="admin-section">
                <h2 class="h4 mb-4">Genel Bakış</h2>
                <div class="row g-4">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card shadow-sm p-4">
                            <h3><?= $stats['total_orders'] ?></h3>
                            <p class="text-muted mb-0">Toplam Sipariş</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card shadow-sm p-4">
                            <h3><?= $stats['pending'] ?></h3>
                            <p class="text-muted mb-0">Bekleyen</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card shadow-sm p-4">
                            <h3><?= $stats['preparing'] ?></h3>
                            <p class="text-muted mb-0">Hazırlanan</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card shadow-sm p-4">
                            <h3><?= $stats['completed'] ?></h3>
                            <p class="text-muted mb-0">Tamamlanan</p>
                        </div>
                    </div>
                </div>
                <div class="card shadow-sm p-4 mt-4">
                    <h3 class="h5 mb-3">Sistem Özeti</h3>
                    <ul class="list-unstyled mb-0 text-muted">
                        <li><i class="bi bi-check-circle text-success me-2"></i>5 dakikalık akıllı cache ile ultra hızlı menü</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Tüm cihazlarda sorunsuz çalışan responsive müşteri arayüzü</li>
                        <li><i class="bi bi-check-circle text-success me-2"></i>Gerçek zamanlı sipariş durumu ve garson çağrıları</li>
                    </ul>
                </div>
            </div>

            <div id="categories" class="admin-section d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4">Kategori Yönetimi</h2>
                    <button class="btn btn-primary" id="addCategory"><i class="bi bi-plus-circle me-2"></i>Yeni Kategori</button>
                </div>
                <p class="text-muted">Kategorileri sürükleyerek sıralayabilir, içerisine ürün ekleyebilirsiniz.</p>
                <div id="categoryList" class="row g-3"></div>
            </div>

            <div id="products" class="admin-section d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4">Ürün Yönetimi</h2>
                    <button class="btn btn-success" id="addProduct"><i class="bi bi-plus-circle me-2"></i>Yeni Ürün</button>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted">Ürünleri düzenlemek için bir kategori seçin, fiyat, açıklama ve görselleri güncelleyin.</p>
                        <div class="row g-3" id="productEditor"></div>
                    </div>
                </div>
            </div>

            <div id="media" class="admin-section d-none">
                <h2 class="h4 mb-4">Medya & Marka Özelleştirme</h2>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form id="brandingForm" class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Logo URL</label>
                                <input type="url" class="form-control" id="brandingLogo" placeholder="https://...">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Banner URL</label>
                                <input type="url" class="form-control" id="brandingBanner" placeholder="https://...">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Hoşgeldiniz Mesajı</label>
                                <textarea class="form-control" id="brandingMessage" rows="3" placeholder="Menünüze özel mesaj"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="tables" class="admin-section d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4">Masa Yönetimi</h2>
                    <button class="btn btn-dark" id="addTable"><i class="bi bi-plus-circle me-2"></i>Yeni Masa</button>
                </div>
                <div class="row g-4" id="tableList"></div>
            </div>

            <div id="orders" class="admin-section d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4">Adisyon & Siparişler</h2>
                    <span class="badge bg-secondary" id="orderRefreshInfo">-</span>
                </div>
                <div id="ordersContainer" class="row g-3"></div>
            </div>

            <div id="waiter" class="admin-section d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4">Garson Çağrıları</h2>
                    <span class="badge bg-danger" id="waiterInfo">Bekleyen çağrı yok</span>
                </div>
                <div id="waiterCalls" class="row g-3"></div>
                <audio id="waiterAudio">
                    <source src="data:audio/mp3;base64,//uQZAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAACAAACcQCA" type="audio/mp3">
                </audio>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
let menu = <?= json_encode($menu, JSON_UNESCAPED_UNICODE) ?>;
let tables = <?= json_encode($tables, JSON_UNESCAPED_UNICODE) ?>;

const navLinks = document.querySelectorAll('#adminNav a[data-target]');
navLinks.forEach(link => {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        navLinks.forEach(item => item.classList.remove('active'));
        event.currentTarget.classList.add('active');
        document.querySelectorAll('.admin-section').forEach(section => section.classList.add('d-none'));
        document.getElementById(event.currentTarget.dataset.target).classList.remove('d-none');
    });
});

function saveMenu() {
    return fetch('api/save_menu.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(menu)
    }).then(res => res.json());
}

function saveTables() {
    return fetch('api/save_tables.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(tables)
    }).then(res => res.json());
}

function renderCategories() {
    const container = document.getElementById('categoryList');
    container.innerHTML = '';
    menu.categories = menu.categories || [];
    menu.categories.forEach((category, index) => {
        const col = document.createElement('div');
        col.className = 'col-12 col-md-6';
        col.innerHTML = `
            <div class="card shadow-sm p-3" data-id="${category.id}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <span class="badge bg-light text-dark me-2 drag-handle"><i class="bi bi-grip-vertical"></i></span>
                        <strong>${category.name}</strong>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" data-action="edit">Düzenle</button>
                        <button class="btn btn-outline-danger" data-action="delete">Sil</button>
                    </div>
                </div>
                <p class="text-muted mb-0">${category.description || 'Açıklama eklenmedi'}</p>
            </div>
        `;
        const card = col.querySelector('.card');
        card.querySelector('[data-action="edit"]').addEventListener('click', () => editCategory(category));
        card.querySelector('[data-action="delete"]').addEventListener('click', () => deleteCategory(category.id));
        container.appendChild(col);
    });

    Sortable.create(container, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: (event) => {
            const moved = menu.categories.splice(event.oldIndex, 1)[0];
            menu.categories.splice(event.newIndex, 0, moved);
            saveMenu();
        }
    });
}

function editCategory(category) {
    const name = prompt('Kategori adı', category.name || '');
    if (name === null) return;
    const description = prompt('Açıklama', category.description || '');
    category.name = name;
    category.description = description;
    saveMenu().then(renderCategories);
}

function deleteCategory(id) {
    if (!confirm('Kategoriyi silmek istediğinizden emin misiniz?')) return;
    menu.categories = menu.categories.filter(cat => cat.id !== id);
    saveMenu().then(() => {
        renderCategories();
        renderProducts();
    });
}

document.getElementById('addCategory').addEventListener('click', () => {
    const name = prompt('Yeni kategori adı');
    if (!name) return;
    const description = prompt('Kategori açıklaması');
    menu.categories = menu.categories || [];
    menu.categories.push({ id: `cat_${Date.now()}`, name, description, products: [] });
    saveMenu().then(() => {
        renderCategories();
        renderProducts();
    });
});

function renderProducts() {
    const editor = document.getElementById('productEditor');
    editor.innerHTML = '';
    (menu.categories || []).forEach(category => {
        const wrapper = document.createElement('div');
        wrapper.className = 'col-12';
        wrapper.innerHTML = `
            <div class="border rounded p-3 mb-3" data-category="${category.id}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0">${category.name}</h3>
                    <button class="btn btn-sm btn-outline-primary" data-action="add-product">Ürün Ekle</button>
                </div>
                <div class="list-group"></div>
            </div>
        `;
        const list = wrapper.querySelector('.list-group');
        (category.products || []).forEach(product => {
            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action flex-column align-items-start';
            item.innerHTML = `
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">${product.name}</h5>
                    <small>${product.price} ₺</small>
                </div>
                <p class="mb-1 text-muted">${product.description || ''}</p>
                <small>${product.image || 'Görsel URL yok'}</small>
                <div class="mt-2">
                    <button class="btn btn-sm btn-outline-secondary me-2" data-action="edit">Düzenle</button>
                    <button class="btn btn-sm btn-outline-danger" data-action="delete">Sil</button>
                </div>
            `;
            item.querySelector('[data-action="edit"]').addEventListener('click', () => editProduct(category, product));
            item.querySelector('[data-action="delete"]').addEventListener('click', () => deleteProduct(category, product.id));
            list.appendChild(item);
        });
        wrapper.querySelector('[data-action="add-product"]').addEventListener('click', () => addProduct(category));
        editor.appendChild(wrapper);
    });
}

function addProduct(category) {
    const name = prompt('Ürün adı');
    if (!name) return;
    const price = parseFloat(prompt('Ürün fiyatı (₺)')) || 0;
    const description = prompt('Ürün açıklaması');
    const image = prompt('Görsel URL (opsiyonel)');
    category.products = category.products || [];
    category.products.push({ id: `prd_${Date.now()}`, name, price, description, image });
    saveMenu().then(renderProducts);
}

function editProduct(category, product) {
    const name = prompt('Ürün adı', product.name || '');
    if (name === null) return;
    const price = parseFloat(prompt('Fiyat', product.price)) || 0;
    const description = prompt('Açıklama', product.description || '');
    const image = prompt('Görsel URL', product.image || '');
    Object.assign(product, { name, price, description, image });
    saveMenu().then(renderProducts);
}

function deleteProduct(category, productId) {
    if (!confirm('Ürünü silmek istediğinizden emin misiniz?')) return;
    category.products = (category.products || []).filter(item => item.id !== productId);
    saveMenu().then(renderProducts);
}

document.getElementById('addProduct').addEventListener('click', () => {
    if (!menu.categories || menu.categories.length === 0) {
        alert('Önce bir kategori oluşturun.');
        return;
    }
    const options = menu.categories.map((cat, index) => `${index + 1}) ${cat.name}`).join('\n');
    const selection = prompt(`Ürün eklenecek kategoriyi seçin:\n${options}`);
    const index = parseInt(selection, 10) - 1;
    if (isNaN(index) || !menu.categories[index]) return;
    addProduct(menu.categories[index]);
});

const brandingForm = document.getElementById('brandingForm');
brandingForm.addEventListener('submit', (event) => {
    event.preventDefault();
    menu.branding = {
        logo: document.getElementById('brandingLogo').value,
        banner: document.getElementById('brandingBanner').value,
        message: document.getElementById('brandingMessage').value
    };
    saveMenu().then(() => alert('Marka bilgileri güncellendi.'));
});

function populateBranding() {
    const branding = menu.branding || {};
    document.getElementById('brandingLogo').value = branding.logo || '';
    document.getElementById('brandingBanner').value = branding.banner || '';
    document.getElementById('brandingMessage').value = branding.message || '';
}

function renderTables() {
    const list = document.getElementById('tableList');
    list.innerHTML = '';
    tables.tables = tables.tables || [];
    tables.tables.forEach(table => {
        const col = document.createElement('div');
        col.className = 'col-12 col-md-6 col-xl-4';
        const qrUrl = `${location.origin.replace(/\/$/, '')}/public/index.php?table=${table.qr_token}`;
        const qrImg = `https://quickchart.io/qr?size=180&text=${encodeURIComponent(qrUrl)}`;
        col.innerHTML = `
            <div class="card shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>${table.name}</strong>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" data-action="rename">Düzenle</button>
                        <button class="btn btn-outline-danger" data-action="delete">Sil</button>
                    </div>
                </div>
                <div class="qr-preview text-center mb-3">
                    <img src="${qrImg}" alt="${table.name} QR">
                </div>
                <div class="small text-muted">URL: ${qrUrl}</div>
                <button class="btn btn-sm btn-outline-primary mt-2" data-action="new-token">QR Yenile</button>
            </div>
        `;
        const card = col.querySelector('.card');
        card.querySelector('[data-action="rename"]').addEventListener('click', () => renameTable(table));
        card.querySelector('[data-action="delete"]').addEventListener('click', () => deleteTable(table.id));
        card.querySelector('[data-action="new-token"]').addEventListener('click', () => regenerateToken(table));
        list.appendChild(col);
    });
}

function renameTable(table) {
    const name = prompt('Masa adı', table.name || '');
    if (name === null) return;
    table.name = name;
    saveTables().then(renderTables);
}

function deleteTable(id) {
    if (!confirm('Masayı silmek istediğinize emin misiniz?')) return;
    tables.tables = tables.tables.filter(tbl => tbl.id !== id);
    saveTables().then(renderTables);
}

function regenerateToken(table) {
    if (!confirm('Yeni QR kodu oluşturulacak. Devam edilsin mi?')) return;
    table.qr_token = Math.random().toString(36).substring(2, 10);
    saveTables().then(renderTables);
}

document.getElementById('addTable').addEventListener('click', () => {
    const name = prompt('Masa adı');
    if (!name) return;
    tables.tables = tables.tables || [];
    tables.tables.push({ id: `tbl_${Date.now()}`, name, qr_token: Math.random().toString(36).substring(2, 10) });
    saveTables().then(renderTables);
});

async function refreshOrders() {
    const response = await fetch('api/orders.php');
    const result = await response.json();
    const container = document.getElementById('ordersContainer');
    const info = document.getElementById('orderRefreshInfo');
    info.textContent = `Son güncelleme: ${new Date().toLocaleTimeString('tr-TR')}`;
    if (!response.ok || result.error) {
        container.innerHTML = '<div class="alert alert-warning">Sipariş bilgileri alınamadı.</div>';
        return;
    }
    container.innerHTML = '';
    result.orders.forEach(order => {
        const statusMap = {
            pending: { label: 'Beklemede', class: 'bg-warning text-dark' },
            preparing: { label: 'Hazırlanıyor', class: 'bg-info text-dark' },
            completed: { label: 'Tamamlandı', class: 'bg-success' }
        };
        const statusInfo = statusMap[order.status] || { label: order.status, class: 'bg-secondary' };
        const col = document.createElement('div');
        col.className = 'col-12 col-xl-6';
        const items = order.items.map(item => `<li>${item.quantity} x ${item.name} <span class="text-muted">${item.price} ₺</span></li>`).join('');
        col.innerHTML = `
            <div class="card shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${order.table_name}</strong>
                        <div class="text-muted small">Sipariş #${order.id}</div>
                    </div>
                    <span class="badge ${statusInfo.class}">${statusInfo.label}</span>
                </div>
                <ul class="mt-3 mb-3">${items}</ul>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-warning" data-status="pending">Beklemede</button>
                    <button class="btn btn-sm btn-outline-info" data-status="preparing">Hazırlanıyor</button>
                    <button class="btn btn-sm btn-outline-success" data-status="completed">Tamamlandı</button>
                </div>
            </div>
        `;
        col.querySelectorAll('button[data-status]').forEach(button => {
            button.addEventListener('click', () => updateOrderStatus(order.id, button.dataset.status));
        });
        container.appendChild(col);
    });
}

async function updateOrderStatus(orderId, status) {
    await fetch('api/update_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ orderId, status })
    });
    refreshOrders();
    fetchOrderStats();
}

async function fetchOrderStats() {
    const response = await fetch('api/orders.php');
    const result = await response.json();
    if (response.ok && !result.error) {
        document.querySelector('#dashboard .row').children[0].querySelector('h3').textContent = result.stats.total_orders;
        document.querySelector('#dashboard .row').children[1].querySelector('h3').textContent = result.stats.pending;
        document.querySelector('#dashboard .row').children[2].querySelector('h3').textContent = result.stats.preparing;
        document.querySelector('#dashboard .row').children[3].querySelector('h3').textContent = result.stats.completed;
    }
}

let lastCallCount = 0;
async function refreshWaiterCalls() {
    const response = await fetch('api/waiter_calls.php');
    const result = await response.json();
    const container = document.getElementById('waiterCalls');
    const badge = document.getElementById('waiterInfo');
    if (!response.ok || result.error) {
        container.innerHTML = '<div class="alert alert-warning">Çağrı bilgileri alınamadı.</div>';
        return;
    }
    container.innerHTML = '';
    let pendingCount = 0;
    result.calls.forEach(call => {
        const col = document.createElement('div');
        col.className = 'col-12 col-md-6 col-xl-4';
        col.innerHTML = `
            <div class="card shadow-sm p-3 ${call.handled ? '' : 'border-danger call-indicator'}">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>${call.table_name}</strong>
                    <span class="badge ${call.handled ? 'bg-success' : 'bg-danger'}">${call.handled ? 'Tamamlandı' : 'Bekliyor'}</span>
                </div>
                <div class="text-muted small mt-2">${new Date(call.created_at * 1000).toLocaleTimeString('tr-TR')}</div>
                <button class="btn btn-sm btn-outline-primary mt-3" ${call.handled ? 'disabled' : ''} data-id="${call.id}">Çağrıyı Kapat</button>
            </div>
        `;
        if (!call.handled) {
            pendingCount++;
        }
        col.querySelector('button[data-id]').addEventListener('click', () => handleCall(call.id));
        container.appendChild(col);
    });
    if (pendingCount > 0) {
        badge.textContent = `${pendingCount} aktif çağrı`; 
        badge.classList.remove('bg-danger');
        badge.classList.add('bg-warning', 'text-dark');
    } else {
        badge.textContent = 'Bekleyen çağrı yok';
        badge.classList.remove('bg-warning', 'text-dark');
        badge.classList.add('bg-danger');
    }
    if (pendingCount > lastCallCount) {
        document.getElementById('waiterAudio').play().catch(() => {});
    }
    lastCallCount = pendingCount;
}

async function handleCall(callId) {
    await fetch('api/handle_call.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ callId })
    });
    refreshWaiterCalls();
}

renderCategories();
renderProducts();
populateBranding();
renderTables();
refreshOrders();
refreshWaiterCalls();
setInterval(refreshOrders, 8000);
setInterval(refreshWaiterCalls, 5000);
</script>
<?php endif; ?>
</body>
</html>
