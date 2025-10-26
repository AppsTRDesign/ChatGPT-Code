<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
?>
<div class="container mt-4">
    <ul class="nav nav-pills panel-nav mb-4">
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/client">Kontrol Paneli</a></li>
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'files.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/client/files.php">Dosyalarım</a></li>
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'profile.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/client/profile.php">Profil</a></li>
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['SCRIPT_NAME']) === 'packages.php' ? ' active' : '' ?>" href="<?= BASE_URL ?>/client/packages.php">Paketler</a></li>
    </ul>
</div>
