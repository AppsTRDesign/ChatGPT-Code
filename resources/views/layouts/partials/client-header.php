<?php include __DIR__ . '/head.php'; ?>
<body>
<header class="navbar navbar-expand-lg navbar-dark bg-glass shadow-sm py-3">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= base_url() ?>">
            <i class="bi bi-broadcast"></i> <?= htmlspecialchars($app['name']) ?> <span>Client</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#clientMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="clientMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?= base_url('app/dashboard') ?>">Panel</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('app/notifications') ?>">Bildirimler</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('app/tokens') ?>">Token</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('app/sites') ?>">Siteler</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('app/api-guide') ?>">API Kılavuz</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('app/support') ?>">Destek</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('logout') ?>">Çıkış</a></li>
            </ul>
        </div>
    </div>
</header>
<main class="container">
    <?php include __DIR__ . '/flash.php'; ?>
