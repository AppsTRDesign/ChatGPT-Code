<?php include __DIR__ . '/head.php'; ?>
<body class="admin-shell">
<div class="container-fluid py-4">
    <div class="row g-4">
        <div class="col-lg-3">
            <aside class="sidebar p-4 h-100">
                <div class="d-flex align-items-center mb-4">
                    <div class="me-3">
                        <span class="badge bg-info-subtle text-info-emphasis rounded-circle p-3"><i class="bi bi-shield-lock"></i></span>
                    </div>
                    <div>
                        <h5 class="mb-0">Admin Panel</h5>
                        <small class="text-muted">Hoş geldin, <?= htmlspecialchars($user['name'] ?? 'Admin') ?></small>
                    </div>
                </div>
                <nav class="nav flex-column gap-2">
                    <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/dashboard') ? ' active' : '' ?>" href="<?= base_url('admin/dashboard') ?>"><i class="bi bi-graph-up"></i> Özet</a>
                    <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/members') ? ' active' : '' ?>" href="<?= base_url('admin/members') ?>"><i class="bi bi-people"></i> Üyeler</a>
                    <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/packages') ? ' active' : '' ?>" href="<?= base_url('admin/packages') ?>"><i class="bi bi-box"></i> Paketler</a>
                    <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/purchases') ? ' active' : '' ?>" href="<?= base_url('admin/purchases') ?>"><i class="bi bi-credit-card"></i> Satın Alımlar</a>
                    <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/api-usage') ? ' active' : '' ?>" href="<?= base_url('admin/api-usage') ?>"><i class="bi bi-activity"></i> API Raporları</a>
                    <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/notifications') ? ' active' : '' ?>" href="<?= base_url('admin/notifications') ?>"><i class="bi bi-bell"></i> Bildirim Şablonları</a>
                    <a class="nav-link<?= str_contains($_SERVER['REQUEST_URI'], '/admin/settings') ? ' active' : '' ?>" href="<?= base_url('admin/settings') ?>"><i class="bi bi-gear"></i> Ayarlar</a>
                    <a class="nav-link" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-left"></i> Çıkış</a>
                </nav>
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
