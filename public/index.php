<?php
require_once __DIR__ . '/../lib/MenuService.php';

$service = new MenuService();

$tableToken = $_GET['table'] ?? '';
if (!$tableToken) {
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><title>QR Menü</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">';
    echo '</head><body class="bg-light"><div class="container py-5">';
    echo '<div class="alert alert-warning shadow">QR menüye erişmek için masaya özel QR kodunu tarayın.</div>';
    echo '</div></body></html>';
    exit;
}

$table = $service->findTableByToken($tableToken);
if (!$table) {
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><title>QR Menü</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">';
    echo '</head><body class="bg-light"><div class="container py-5">';
    echo '<div class="alert alert-danger shadow">Masa bulunamadı. Lütfen QR kodu tekrar tarayın.</div>';
    echo '</div></body></html>';
    exit;
}

$cacheKey = 'menu_page_' . $tableToken;
if ($cached = cache_get($cacheKey)) {
    echo $cached;
    exit;
}

$menu = $service->getMenu();
$menuJson = json_encode($menu, JSON_UNESCAPED_UNICODE);
$tableJson = json_encode($table, JSON_UNESCAPED_UNICODE);

ob_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= sanitize($table['name']) ?> | QR Menü</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #f4f6f9; }
        .category-nav { overflow-x: auto; white-space: nowrap; }
        .category-nav button { margin-right: .5rem; }
        .menu-card { transition: transform .2s ease, box-shadow .2s ease; }
        .menu-card:hover { transform: translateY(-4px); box-shadow: 0 1rem 3rem rgba(0,0,0,.175); }
        .menu-image { height: 180px; object-fit: cover; border-radius: .75rem; }
        .floating-cart { position: fixed; bottom: 20px; right: 20px; z-index: 1000; }
        .badge-status { text-transform: capitalize; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand">QR Menü - <?= sanitize($table['name']) ?></span>
        <button class="btn btn-outline-light" id="callWaiter"><i class="bi bi-bell"></i> Garson Çağır</button>
    </div>
</nav>
<?php if (!empty($menu['branding']['banner'])): ?>
<div class="container mt-4">
    <div class="rounded-4 overflow-hidden shadow-sm">
        <img src="<?= sanitize($menu['branding']['banner']) ?>" alt="Banner" class="w-100" style="max-height:280px;object-fit:cover;">
    </div>
</div>
<?php endif; ?>

<section class="container my-4">
    <div class="row align-items-center mb-4">
        <div class="col">
            <div class="d-flex align-items-center gap-3">
                <?php if (!empty($menu['branding']['logo'])): ?>
                    <img src="<?= sanitize($menu['branding']['logo']) ?>" alt="Logo" style="height:64px;width:64px;object-fit:cover;border-radius:16px;">
                <?php endif; ?>
                <div>
                    <h1 class="h3 mb-1">Menümüz</h1>
                    <p class="text-muted mb-0"><?= sanitize($menu['branding']['message'] ?? 'Temassız sipariş deneyimi - telefonunuzdan sipariş verin.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <div class="badge bg-success p-3 shadow-sm">
                <i class="bi bi-wifi"></i> Online Sipariş Açık
            </div>
        </div>
    </div>
    <div class="category-nav mb-4" id="categoryNav"></div>
    <div id="menuContainer" class="row g-4"></div>
</section>

<section class="container mb-5">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Sipariş Durumu</h2>
            <span class="text-muted small" id="lastStatusCheck">-</span>
        </div>
        <div class="card-body" id="orderStatus"></div>
    </div>
</section>

<div class="floating-cart">
    <button class="btn btn-primary btn-lg shadow" data-bs-toggle="modal" data-bs-target="#cartModal">
        <i class="bi bi-basket"></i> <span id="cartCount" class="badge bg-light text-dark ms-2">0</span>
    </button>
</div>

<div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Sepetiniz</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body" id="cartItems"></div>
      <div class="modal-footer justify-content-between">
        <div>
            <strong>Toplam: <span id="cartTotal">0</span> ₺</strong>
        </div>
        <button class="btn btn-success" id="submitOrder">Sipariş Ver</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const MENU = <?= $menuJson ?>;
const TABLE = <?= $tableJson ?>;
const TABLE_TOKEN = <?= json_encode($tableToken) ?>;
let cart = [];

function renderMenu() {
    const nav = document.getElementById('categoryNav');
    const container = document.getElementById('menuContainer');
    nav.innerHTML = '';
    container.innerHTML = '';
    MENU.categories.forEach(category => {
        const button = document.createElement('button');
        button.className = 'btn btn-outline-dark';
        button.textContent = category.name;
        button.addEventListener('click', () => {
            document.getElementById(`category-${category.id}`).scrollIntoView({ behavior: 'smooth' });
        });
        nav.appendChild(button);

        const section = document.createElement('div');
        section.id = `category-${category.id}`;
        section.className = 'col-12';
        section.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h4 mb-0">${category.name}</h2>
                    <small class="text-muted">${category.description || ''}</small>
                </div>
                <a href="#top" class="btn btn-sm btn-outline-secondary">Başa Dön</a>
            </div>
        `;
        container.appendChild(section);

        (category.products || []).forEach(product => {
            const col = document.createElement('div');
            col.className = 'col-12 col-md-6 col-lg-4';
            col.innerHTML = `
                <div class="card menu-card h-100">
                    <img src="${product.image}" class="menu-image" alt="${product.name}">
                    <div class="card-body d-flex flex-column">
                        <h3 class="h5">${product.name}</h3>
                        <p class="text-muted flex-grow-1">${product.description || ''}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">${product.price} ₺</span>
                            <button class="btn btn-primary" data-product='${JSON.stringify(product)}'>Sepete Ekle</button>
                        </div>
                    </div>
                </div>
            `;
            col.querySelector('button').addEventListener('click', (event) => {
                const productData = JSON.parse(event.currentTarget.getAttribute('data-product'));
                addToCart(productData);
            });
            container.appendChild(col);
        });
    });
}

function addToCart(product) {
    const existing = cart.find(item => item.id === product.id);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({ ...product, quantity: 1 });
    }
    renderCart();
}

function updateQuantity(productId, delta) {
    cart = cart.map(item => item.id === productId ? { ...item, quantity: Math.max(1, item.quantity + delta) } : item);
    renderCart();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    renderCart();
}

function renderCart() {
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    const cartCount = document.getElementById('cartCount');

    if (cart.length === 0) {
        cartItems.innerHTML = '<p class="text-muted">Sepetiniz boş. Lezzetli bir seçim yapın!</p>';
        cartTotal.textContent = '0';
        cartCount.textContent = '0';
        return;
    }

    let total = 0;
    cartItems.innerHTML = '';
    cart.forEach(item => {
        total += item.price * item.quantity;
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center justify-content-between mb-3';
        div.innerHTML = `
            <div>
                <strong>${item.name}</strong>
                <div class="text-muted small">${item.price} ₺ x ${item.quantity}</div>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-secondary btn-sm" data-action="decrease">-</button>
                <button class="btn btn-outline-secondary btn-sm" disabled>${item.quantity}</button>
                <button class="btn btn-outline-secondary btn-sm" data-action="increase">+</button>
                <button class="btn btn-outline-danger btn-sm" data-action="remove"><i class="bi bi-trash"></i></button>
            </div>
        `;
        div.querySelector('[data-action="decrease"]').addEventListener('click', () => updateQuantity(item.id, -1));
        div.querySelector('[data-action="increase"]').addEventListener('click', () => updateQuantity(item.id, 1));
        div.querySelector('[data-action="remove"]').addEventListener('click', () => removeFromCart(item.id));
        cartItems.appendChild(div);
    });
    cartTotal.textContent = total.toFixed(2);
    cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
}

async function submitOrder() {
    if (cart.length === 0) return;
    const response = await fetch('api/place_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tableToken: TABLE_TOKEN,
            items: cart.map(item => ({ id: item.id, name: item.name, price: item.price, quantity: item.quantity }))
        })
    });
    const result = await response.json();
    if (result.success) {
        cart = [];
        renderCart();
        bootstrap.Modal.getInstance(document.getElementById('cartModal')).hide();
        fetchOrderStatus();
        alert('Siparişiniz alındı! Hazırlanmaya başlıyor.');
    } else {
        alert(result.error || 'Sipariş gönderilirken hata oluştu.');
    }
}

async function callWaiter() {
    const response = await fetch('api/call_waiter.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ tableToken: TABLE_TOKEN })
    });
    const result = await response.json();
    if (result.success) {
        alert('Garson çağrınız iletildi. Lütfen bekleyiniz.');
    } else {
        alert(result.error || 'Garson çağrılırken hata oluştu.');
    }
}

async function fetchOrderStatus() {
    const response = await fetch(`api/order_status.php?tableToken=${TABLE_TOKEN}`);
    const result = await response.json();
    const container = document.getElementById('orderStatus');
    const timestamp = new Date().toLocaleTimeString('tr-TR');
    document.getElementById('lastStatusCheck').textContent = `Son güncelleme: ${timestamp}`;
    if (!response.ok || result.error) {
        container.innerHTML = '<div class="alert alert-warning">Sipariş bilgileri alınamadı.</div>';
        return;
    }
    if (result.orders.length === 0) {
        container.innerHTML = '<p class="text-muted">Aktif siparişiniz bulunmuyor.</p>';
        return;
    }
    container.innerHTML = '';
    result.orders.forEach(order => {
        const statusMap = {
            pending: { label: 'Beklemede', class: 'bg-warning text-dark' },
            preparing: { label: 'Hazırlanıyor', class: 'bg-info text-dark' },
            completed: { label: 'Servis Edildi', class: 'bg-success' }
        };
        const statusInfo = statusMap[order.status] || { label: order.status, class: 'bg-secondary' };
        const wrapper = document.createElement('div');
        wrapper.className = 'border rounded p-3 mb-3';
        const items = order.items.map(item => `<li>${item.quantity} x ${item.name}</li>`).join('');
        wrapper.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Sipariş #${order.id.slice(-6)}</strong>
                    <div class="text-muted small">${new Date(order.created_at * 1000).toLocaleTimeString('tr-TR')}</div>
                </div>
                <span class="badge ${statusInfo.class} badge-status">${statusInfo.label}</span>
            </div>
            <ul class="mt-3 mb-0">${items}</ul>
        `;
        container.appendChild(wrapper);
    });
}

renderMenu();
renderCart();
fetchOrderStatus();
setInterval(fetchOrderStatus, 10000);

document.getElementById('submitOrder').addEventListener('click', submitOrder);
document.getElementById('callWaiter').addEventListener('click', callWaiter);
</script>
</body>
</html>
<?php
$html = ob_get_clean();
cache_put($cacheKey, $html);
echo $html;
