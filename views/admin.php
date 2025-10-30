<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - NoaSoft QR Menü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/public/css/styles.css" rel="stylesheet">
</head>
<body>
<div class="admin-layout" id="adminApp">
    <nav class="admin-sidebar bg-dark text-white p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fs-5 mb-0">Admin</h2>
            <button class="btn btn-outline-light d-lg-none" id="adminSidebarToggle" type="button"><i class="bi bi-list"></i></button>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="#" class="nav-link text-white" data-page="dashboard">Gösterge Paneli</a></li>
            <li class="nav-item"><a href="#" class="nav-link text-white" data-page="restaurants">Restoranlar</a></li>
            <li class="nav-item"><a href="#" class="nav-link text-white" data-page="settings">Ayarlar</a></li>
        </ul>
        <button class="btn btn-outline-light mt-3 w-100" id="logoutBtn">Çıkış Yap</button>
    </nav>
    <main class="admin-main p-4">
        <header class="admin-topbar d-lg-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h4 mb-1">NoaSoft Yönetim</h1>
                <p class="text-muted mb-0">Restoran başvurularını ve sistemi buradan yönetin.</p>
            </div>
        </header>
        <div id="adminContent" class="admin-content"></div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/public/js/admin.js"></script>
</body>
</html>
