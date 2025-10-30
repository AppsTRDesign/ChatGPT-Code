<?php
$slug = $slug ?? '';
$tableToken = $token ?? ($table['qr_token'] ?? '');
$tableName = $table['name'] ?? '';
$menuApiKey = $menuApiKey ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Menü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/public/css/styles.css" rel="stylesheet">
</head>
<body class="menu-body">
<div class="menu-shell" id="menuApp" data-slug="<?= htmlspecialchars($slug) ?>" data-api-key="<?= htmlspecialchars($menuApiKey) ?>" data-table-token="<?= htmlspecialchars($tableToken) ?>" data-table-name="<?= htmlspecialchars($tableName) ?>">
    <section class="menu-hero">
        <div class="menu-hero-info">
            <span class="badge rounded-pill bg-white text-success" id="tableBadge"></span>
            <h1 id="restaurantName">NoaSoft Restoran</h1>
            <p class="lead text-white-50" id="restaurantDescription"></p>
            <div class="hero-actions">
                <div class="language-switch" id="languageSwitcher"></div>
                <button class="btn btn-light btn-sm" id="waiterCall"><i class="bi bi-bell me-1"></i>Garson Çağır</button>
            </div>
        </div>
        <div class="menu-search">
            <div class="search-card">
                <i class="bi bi-search"></i>
                <input type="text" id="menuSearch" placeholder="Ne yemek istersiniz?">
            </div>
        </div>
    </section>
    <section class="menu-grid">
        <div class="menu-products">
            <div id="featuredProducts" class="featured-grid"></div>
            <div id="menuCategories"></div>
        </div>
        <aside class="menu-cart" id="cartPanel">
            <div class="cart-header">
                <h2>Siparişiniz</h2>
                <small class="text-muted" id="orderStatusText"></small>
            </div>
            <div id="cartItems" class="cart-items"></div>
            <div class="cart-summary">
                <div class="summary-row"><span>Toplam</span><span id="cartTotal">0</span></div>
                <label class="form-label">Masa Notu</label>
                <textarea id="customerNote" class="form-control form-control-sm" rows="2" placeholder="Örn: Az pişmiş olsun"></textarea>
                <button class="btn btn-success w-100 mt-3" id="submitOrder"><i class="bi bi-bag-check me-1"></i>Siparişi Gönder</button>
                <button class="btn btn-outline-success w-100 mt-2 d-none" id="downloadReceipt"><i class="bi bi-printer me-1"></i>Adisyonu İndir</button>
            </div>
        </aside>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://qrmenu.noasoft.org:4000/socket.io/socket.io.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="/public/js/menu.js"></script>
<script src="/public/lang/tr.json" type="application/json" id="defaultLang"></script>
</body>
</html>
