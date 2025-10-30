<?php
$config = require __DIR__ . '/../app/config/config.php';
$restaurantId = $_SESSION['restaurant_id'] ?? null;
$restaurant = null;
if ($restaurantId) {
    $restaurantModel = new Restaurant();
    $restaurant = $restaurantModel->find($restaurantId);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restoran Paneli - NoaSoft QR Menü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/public/css/styles.css" rel="stylesheet">
</head>
<body class="dashboard-body">
<div class="dashboard-layout" id="dashboardApp">
    <nav class="dashboard-sidebar">
        <div class="sidebar-header">
            <h2>NoaSoft</h2>
            <p class="text-white-50 mb-0">QR Menü Yönetimi</p>
        </div>
        <ul class="nav flex-column dashboard-menu">
            <li class="nav-item"><a href="#" class="nav-link active" data-page="overview"><i class="bi bi-speedometer2"></i><span>Genel Bakış</span></a></li>
            <li class="nav-item"><a href="#" class="nav-link" data-page="orders"><i class="bi bi-receipt-cutoff"></i><span>Siparişler</span></a></li>
            <li class="nav-item"><a href="#" class="nav-link" data-page="tables"><i class="bi bi-qr-code"></i><span>Masalar & QR</span></a></li>
            <li class="nav-item"><a href="#" class="nav-link" data-page="menu"><i class="bi bi-card-list"></i><span>Menü Yönetimi</span></a></li>
            <li class="nav-item"><a href="#" class="nav-link" data-page="reports"><i class="bi bi-graph-up"></i><span>Raporlar</span></a></li>
            <li class="nav-item"><a href="#" class="nav-link" data-page="settings"><i class="bi bi-gear"></i><span>Restoran Ayarları</span></a></li>
            <li class="nav-item"><a href="#" class="nav-link" data-page="calls"><i class="bi bi-bell"></i><span>Garson Çağrıları</span></a></li>
        </ul>
        <button class="btn btn-outline-light mt-auto w-100" id="logoutBtn"><i class="bi bi-box-arrow-right me-2"></i>Çıkış</button>
    </nav>
    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div>
                <h1 class="h4 mb-1">Merhaba, <?= htmlspecialchars($restaurant['name'] ?? 'Restoran') ?></h1>
                <p class="text-muted mb-0">Masalarınızı, siparişlerinizi ve menünüzü tek panelden yönetin.</p>
            </div>
            <div class="topbar-badges">
                <span class="badge rounded-pill bg-light text-dark"><i class="bi bi-clock-history me-1"></i><span id="dashboardClock"></span></span>
                <span class="badge rounded-pill bg-light text-dark"><i class="bi bi-coin me-1"></i><?= htmlspecialchars($restaurant['currency'] ?? 'TRY') ?></span>
            </div>
        </header>
        <section id="dashboardContent" class="dashboard-content"></section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://qrmenu.noasoft.org:4000/socket.io/socket.io.js"></script>
<script>
    window.dashboardContext = {
        restaurantId: <?= json_encode($restaurantId) ?>,
        restaurantSlug: <?= json_encode($restaurant['slug'] ?? '') ?>,
        socketUrl: <?= json_encode($config['api']['socket_client']) ?>,
        baseUrl: <?= json_encode($config['base_url']) ?>,
        currency: <?= json_encode($restaurant['currency'] ?? 'TRY') ?>,
        qrApi: <?= json_encode($config['api']['qr_api']) ?>
    };
</script>
<script src="/public/js/dashboard.js"></script>
</body>
</html>
