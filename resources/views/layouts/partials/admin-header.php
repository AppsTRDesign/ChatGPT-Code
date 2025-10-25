<?php include __DIR__ . '/head.php'; ?>
<body class="admin-shell">
<div class="container-fluid py-4">
    <div class="mobile-admin-toggle d-lg-none mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1">Admin Panel</h5>
                <small>Hoş geldin, <?= htmlspecialchars($user['name'] ?? 'Admin') ?></small>
            </div>
            <button class="btn btn-theme" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-3">
            <aside id="adminSidebar" class="sidebar offcanvas-lg offcanvas-start h-100" tabindex="-1" aria-labelledby="adminSidebarLabel" data-bs-scroll="true">
                <div class="offcanvas-header d-lg-none">
                    <h5 class="offcanvas-title" id="adminSidebarLabel">Admin Panel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <div class="p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="me-3">
                                <span class="badge bg-info-subtle text-info-emphasis rounded-circle p-3"><i class="bi bi-shield-lock"></i></span>
                            </div>
                            <div>
                                <h5 class="mb-0">Admin Panel</h5>
                                <small class="opacity-75">Hoş geldin, <?= htmlspecialchars($user['name'] ?? 'Admin') ?></small>
                            </div>
                        </div>
                        <nav class="nav flex-column gap-2">
                            <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/dashboard') ? ' active' : '' ?>" href="<?= base_url('admin/dashboard') ?>" data-bs-dismiss="offcanvas"><i class="bi bi-graph-up"></i> Özet</a>
                            <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/members') ? ' active' : '' ?>" href="<?= base_url('admin/members') ?>" data-bs-dismiss="offcanvas"><i class="bi bi-people"></i> Üyeler</a>
                            <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/packages') ? ' active' : '' ?>" href="<?= base_url('admin/packages') ?>" data-bs-dismiss="offcanvas"><i class="bi bi-box"></i> Paketler</a>
                            <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/purchases') ? ' active' : '' ?>" href="<?= base_url('admin/purchases') ?>" data-bs-dismiss="offcanvas"><i class="bi bi-credit-card"></i> Satın Alımlar</a>
                            <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/api-usage') ? ' active' : '' ?>" href="<?= base_url('admin/api-usage') ?>" data-bs-dismiss="offcanvas"><i class="bi bi-activity"></i> API Raporları</a>
                            <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/notifications') ? ' active' : '' ?>" href="<?= base_url('admin/notifications') ?>" data-bs-dismiss="offcanvas"><i class="bi bi-bell"></i> Bildirim Şablonları</a>
                            <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/settings') ? ' active' : '' ?>" href="<?= base_url('admin/settings') ?>" data-bs-dismiss="offcanvas"><i class="bi bi-gear"></i> Ayarlar</a>
                            <a class="nav-link" href="<?= base_url('logout') ?>" data-bs-dismiss="offcanvas"><i class="bi bi-box-arrow-left"></i> Çıkış</a>
                        </nav>
                    </div>
                </div>
            </aside>
        </div>
        <div class="col-lg-9">
            <?php include __DIR__ . '/flash.php'; ?>
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h3 class="mb-0"><?= htmlspecialchars($title ?? 'Yönetim') ?></h3>
                    <small class="text-muted">Platform içgörülerini takip edin.</small>
                </div>
                <div class="badge-soft">Tarayıcı: <?= htmlspecialchars($device['browser'] ?? 'Bilinmiyor') ?> · Platform: <?= htmlspecialchars($device['platform'] ?? 'Bilinmiyor') ?></div>
            </div>
