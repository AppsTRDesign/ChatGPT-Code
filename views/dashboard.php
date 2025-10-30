<?php
$config = require __DIR__ . '/../app/config/config.php';
$restaurantId = $_SESSION['restaurant_id'] ?? null;
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
<body>
<div class="d-flex" id="dashboardApp">
    <nav class="sidebar bg-primary text-white p-3">
        <h2 class="fs-5">Restoran Paneli</h2>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="#" class="nav-link text-white" data-page="orders">Siparişler</a></li>
            <li class="nav-item"><a href="#" class="nav-link text-white" data-page="menu">Menü</a></li>
            <li class="nav-item"><a href="#" class="nav-link text-white" data-page="report">Raporlar</a></li>
            <li class="nav-item"><a href="#" class="nav-link text-white" data-page="theme">Tema</a></li>
            <li class="nav-item"><a href="#" class="nav-link text-white" data-page="qr">QR Kod</a></li>
        </ul>
        <button class="btn btn-outline-light mt-3" id="logoutBtn">Çıkış Yap</button>
    </nav>
    <main class="flex-grow-1 p-4">
        <div id="dashboardContent"></div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/socket.io-client@4/dist/socket.io.min.js"></script>
<script>
    window.dashboardContext = {
        restaurantId: <?php echo json_encode($restaurantId); ?>,
        socketUrl: <?php echo json_encode($config['api']['socket_client']); ?>
    };
</script>
<script src="/public/js/dashboard.js"></script>
</body>
</html>
