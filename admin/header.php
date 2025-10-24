<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\Settings;

Auth::requireRole('admin');
$user = Auth::user();
$siteName = Settings::siteName();
$logoUrl = Settings::logoUrl();
$faviconUrl = Settings::faviconUrl();
$oneSignalEnabled = Settings::onesignalEnabled();
$oneSignalAppId = Settings::onesignalAppId();
$appConfig = [
    'baseUrl' => rtrim(BASE_URL, '/'),
    'onesignal' => [
        'enabled' => $oneSignalEnabled,
        'appId' => $oneSignalAppId,
        'registerEndpoint' => '/client/onesignal-register',
        'workerPath' => '/OneSignalSDKWorker.js',
    ],
    'user' => [
        'id' => (int) $user['id'],
        'role' => $user['role'],
    ],
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | <?= Helpers::e($siteName) ?></title>
    <?php if ($faviconUrl): ?>
        <link rel="icon" href="<?= Helpers::e($faviconUrl) ?>" type="image/png">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@5/dark.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.22.1/dist/bootstrap-table.min.css" rel="stylesheet">
    <link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet">
    <script>window.APP_CONFIG = <?= json_encode($appConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
</head>
<body data-onesignal-enabled="<?= $oneSignalEnabled ? '1' : '0' ?>">
<div class="admin-shell d-flex">
    <aside class="sidebar offcanvas offcanvas-lg offcanvas-start text-white p-0 d-flex flex-column" tabindex="-1" id="adminSidebar" data-bs-scroll="true" data-bs-backdrop="true">
        <div class="offcanvas-header d-lg-none">
            <h2 class="h5 mb-0">Menü</h2>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
        </div>
        <div class="offcanvas-body p-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <?php if ($logoUrl): ?>
                    <img src="<?= Helpers::e($logoUrl) ?>" alt="<?= Helpers::e($siteName) ?>" class="navbar-logo">
                <?php else: ?>
                    <span class="fw-semibold h5 mb-0"><?= Helpers::e($siteName) ?></span>
                <?php endif; ?>
                <div>
                    <div class="fw-semibold">Admin Paneli</div>
                    <div class="small text-white-50"><?= Helpers::e($user['username']) ?></div>
                </div>
            </div>
            <nav class="nav flex-column gap-2">
                <a class="nav-link" href="/admin/dashboard">Gösterge Paneli</a>
                <a class="nav-link" href="/admin/users">Üyeler</a>
                <a class="nav-link" href="/admin/packages">Paketler</a>
                <a class="nav-link" href="/admin/purchases">Satın Alımlar</a>
                <a class="nav-link" href="/admin/payments">Ödeme Bildirimleri</a>
                <a class="nav-link" href="/admin/settings">Ayarlar</a>
                <a class="nav-link" href="/admin/usage">API Raporları</a>
                <a class="nav-link" href="/admin/qr-history">QR Kayıtları</a>
                <a class="nav-link" href="/admin/push">Push Bildirimleri</a>
                <a class="nav-link" href="/logout">Çıkış</a>
            </nav>
        </div>
    </aside>
    <main class="admin-main flex-grow-1 p-4 p-lg-5">
        <div class="d-lg-none mb-4">
            <button class="btn btn-outline-light w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">
                Menü
            </button>
        </div>
        <?php if ($flash = App\Helpers::flash('message')): ?>
            <div data-flash-message data-type="success" data-message="<?= Helpers::e($flash) ?>"></div>
        <?php endif; ?>
