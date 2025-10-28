<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
$settings = fetch_settings($pdo);
?>
<div class="container mt-4">
    <ul class="nav nav-pills panel-nav mb-4">
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/client">Kontrol Paneli</a></li>
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'files.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/client/files.php">Dosyalarım</a></li>
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'profile.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/client/profile.php">Profil</a></li>
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'packages.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/client/packages.php">Paketler</a></li>
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'share-stats.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/client/share-stats.php">Paylaşım Analitiği</a></li>
    </ul>
    <?php if (!empty($settings['ad_dashboard_html'])): ?>
        <div class="card card-glass p-3 mb-4">
            <?= $settings['ad_dashboard_html'] ?>
        </div>
    <?php endif; ?>
</div>
