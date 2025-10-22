<?php
require_once __DIR__ . '/lib/MenuService.php';
require_once __DIR__ . '/lib/helpers.php';

$service = new MenuService();
$tableToken = $_GET['table'] ?? '';

if (!$tableToken) {
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">';
    echo '<title>QR Menü</title></head><body class="bg-light">';
    echo '<div class="container py-5"><div class="alert alert-warning shadow">Masaya özel QR kodunu tarayarak menüye erişebilirsiniz.</div></div>';
    echo '</body></html>';
    exit;
}

$table = $service->findTableByToken($tableToken);
if (!$table) {
    echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">';
    echo '<title>QR Menü</title></head><body class="bg-light">';
    echo '<div class="container py-5"><div class="alert alert-danger shadow">Masa bulunamadı. Lütfen QR kodunu tekrar deneyin.</div></div>';
    echo '</body></html>';
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
$tableTokenJson = json_encode($tableToken, JSON_UNESCAPED_UNICODE);

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f4f6f9; }
        .category-nav { overflow-x: auto; white-space: nowrap; }
        .category-nav button { margin-right: .5rem; }
        .menu-card { transition: transform .2s ease, box-shadow .2s ease; border-radius: 1rem; }
        .menu-card:hover { transform: translateY(-4px); box-shadow: 0 1rem 3rem rgba(0,0,0,.175); }
        .menu-image { height: 200px; object-fit: cover; border-radius: 1rem 1rem 0 0; }
        .floating-cart { position: fixed; bottom: 20px; right: 20px; z-index: 1000; }
        .badge-status { text-transform: capitalize; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand">QR Menü - <?= sanitize($table['name']) ?></span>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-light" id="callWaiter"><i class="bi bi-bell"></i> Garson Çağır</button>
            <button class="btn btn-outline-light" id="refreshStatus"><i class="bi bi-arrow-repeat"></i></button>
        </div>
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
                    <p class="text-muted mb-0"><?= sanitize($menu['branding']['message'] ?? 'Telefonunuzdan temassız sipariş verin.') ?></p>
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
const TABLE_TOKEN = <?= $tableTokenJson ?>;
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
                            <span class="fw-bold">${Number(product.price).toFixed(2)} ₺</span>
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
    Swal.fire('Sepete Eklendi', `${product.name} sepetinize eklendi.`, 'success');
}

function renderCart() {
    const cartItems = document.getElementById('cartItems');
    const cartCount = document.getElementById('cartCount');
    const cartTotal = document.getElementById('cartTotal');
    cartItems.innerHTML = '';
    let total = 0;
    cart.forEach((item, index) => {
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center justify-content-between mb-3';
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        row.innerHTML = `
            <div>
                <strong>${item.name}</strong><br>
                <span class="text-muted">${item.price.toFixed(2)} ₺</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary btn-sm" data-action="dec" data-index="${index}"><i class="bi bi-dash"></i></button>
                <span>${item.quantity}</span>
                <button class="btn btn-outline-secondary btn-sm" data-action="inc" data-index="${index}"><i class="bi bi-plus"></i></button>
                <button class="btn btn-outline-danger btn-sm" data-action="remove" data-index="${index}"><i class="bi bi-trash"></i></button>
            </div>
        `;
        cartItems.appendChild(row);
    });
    if (!cart.length) {
        cartItems.innerHTML = '<p class="text-muted">Sepetiniz boş.</p>';
    }
    cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartTotal.textContent = total.toFixed(2);
}

function updateCart(action, index) {
    const item = cart[index];
    if (!item) return;
    if (action === 'inc') {
        item.quantity += 1;
    } else if (action === 'dec') {
        item.quantity = Math.max(1, item.quantity - 1);
    } else if (action === 'remove') {
        cart.splice(index, 1);
    }
    renderCart();
}

document.getElementById('cartItems').addEventListener('click', (event) => {
    const button = event.target.closest('button');
    if (!button) return;
    const action = button.getAttribute('data-action');
    const index = Number(button.getAttribute('data-index'));
    updateCart(action, index);
});

async function submitOrder() {
    if (!cart.length) {
        Swal.fire('Bilgi', 'Sepete ürün ekleyin.', 'info');
        return;
    }
    try {
        const response = await fetch('/api/place_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                table_token: TABLE_TOKEN,
                items: cart.map(item => ({ id: item.id, quantity: item.quantity, price: item.price })),
            })
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Sipariş gönderilemedi');
        }
        cart = [];
        renderCart();
        Swal.fire('Teşekkürler', 'Siparişiniz alındı. Hazırlanmaya başlıyor.', 'success');
        const modal = bootstrap.Modal.getInstance(document.getElementById('cartModal'));
        modal.hide();
        loadOrderStatus();
    } catch (error) {
        Swal.fire('Hata', error.message, 'error');
    }
}

document.getElementById('submitOrder').addEventListener('click', submitOrder);

document.getElementById('callWaiter').addEventListener('click', async () => {
    try {
        const response = await fetch('/api/call_waiter.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table_token: TABLE_TOKEN })
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Çağrı gönderilemedi');
        }
        Swal.fire('Tamamlandı', 'Garson çağrınız alındı.', 'success');
    } catch (error) {
        Swal.fire('Hata', error.message, 'error');
    }
});

async function loadOrderStatus(showToast = false) {
    try {
        const response = await fetch(`/api/order_status.php?table=${encodeURIComponent(TABLE_TOKEN)}`);
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'Sipariş durumları alınamadı');
        }
        const container = document.getElementById('orderStatus');
        container.innerHTML = '';
        const orders = data.orders || [];
        if (!orders.length) {
            container.innerHTML = '<p class="text-muted mb-0">Henüz sipariş oluşturulmadı.</p>';
            return;
        }
        orders.forEach(order => {
            const div = document.createElement('div');
            div.className = 'border rounded-3 p-3 mb-3';
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>#${order.id}</strong>
                        <span class="badge bg-info text-dark ms-2 badge-status">${order.status}</span>
                    </div>
                    <div>${Number(order.total).toFixed(2)} ₺</div>
                </div>
                ${order.note ? `<div class="mt-2 text-muted small">${order.note}</div>` : ''}
                <div class="text-muted small mt-2">Güncelleme: ${order.updated_at}</div>
            `;
            container.appendChild(div);
        });
        document.getElementById('lastStatusCheck').textContent = new Date().toLocaleTimeString('tr-TR');
        if (showToast) {
            Swal.fire('Güncellendi', 'Sipariş durumu yenilendi.', 'success');
        }
    } catch (error) {
        Swal.fire('Hata', error.message, 'error');
    }
}

document.getElementById('refreshStatus').addEventListener('click', () => loadOrderStatus(true));

renderMenu();
renderCart();
loadOrderStatus();
setInterval(loadOrderStatus, 15000);
</script>
</body>
</html>
<?php
$html = ob_get_clean();
cache_put($cacheKey, $html);
echo $html;
