<?php include __DIR__ . '/../../partials/head.php'; ?>
<?php $currentPath = $_SERVER['REQUEST_URI'] ?? '/admin'; ?>
<nav class="navbar navbar-expand-lg shadow-sm" style="background: linear-gradient(135deg, #0a4d68, #00b8a9);">
    <div class="container-fluid">
        <a class="navbar-brand text-white fw-bold" href="/admin">NoaSoft Web Push</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php
                $links = [
                    '/admin' => 'Dashboard',
                    '/admin/clients' => 'Müşteriler',
                    '/admin/packages' => 'Paketler',
                    '/admin/purchases' => 'Satın Alımlar',
                    '/admin/api-keys' => 'API',
                    '/admin/notifications' => 'Bildirimler',
                    '/admin/templates' => 'Şablonlar',
                    '/admin/subscriptions' => 'Abonelikler',
                    '/admin/payments' => 'Ödemeler',
                    '/admin/reports' => 'Raporlar',
                    '/admin/settings' => 'Ayarlar'
                ];

                foreach ($links as $url => $label):
                    $active = str_starts_with($currentPath, $url) ? 'active fw-semibold' : '';
                ?>
                    <li class="nav-item"><a class="nav-link text-white <?= $active ?>" href="<?= $url ?>"><?= $label ?></a></li>
                <?php endforeach; ?>
                <li class="nav-item ms-lg-3"><button class="btn btn-outline-light" data-action="logout">Çıkış</button></li>
            </ul>
        </div>
    </div>
</nav>
<main class="container-fluid py-4">
