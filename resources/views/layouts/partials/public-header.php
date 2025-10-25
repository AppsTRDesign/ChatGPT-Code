<?php include __DIR__ . '/head.php'; ?>
<body>
<header class="navbar navbar-expand-lg navbar-dark bg-glass py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="<?= base_url() ?>">
            <i class="bi bi-broadcast-pin"></i> <?= htmlspecialchars($app['name']) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="publicMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?= base_url('login') ?>">Giriş</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('register') ?>">Üye Ol</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('api-guide') ?>">API</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('contact') ?>">İletişim</a></li>
            </ul>
        </div>
    </div>
</header>
<main class="container">
    <?php include __DIR__ . '/flash.php'; ?>
