<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
$settings = fetch_settings($pdo);
?>
<div class="container mt-4">
    <ul class="nav nav-pills panel-nav mb-4">
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/admin">Genel Bakış</a></li>
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'files.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/admin/files.php">Dosyalar</a></li>
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'users.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/admin/users.php">Üyeler</a></li>
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'packages.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/admin/packages.php">Paketler</a></li>
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'settings.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/admin/settings.php">Genel Ayarlar</a></li>
    </ul>
    <?php if (!empty($settings['ad_dashboard_html'])): ?>
        <div class="card card-glass p-3 mb-4">
            <?= $settings['ad_dashboard_html'] ?>
        </div>
    <?php endif; ?>
</div>
