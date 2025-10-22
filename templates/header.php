<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';
use App\Auth;
use App\Helpers;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e(APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@5/dark.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
    <link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/index.php">NoaSoft QR Menu</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Menüyü Aç">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/index.php">Anasayfa</a></li>
                <li class="nav-item"><a class="nav-link" href="/client/dashboard.php">Panel</a></li>
                <li class="nav-item"><a class="nav-link" href="/docs/api.html">API</a></li>
                <?php if (Auth::user()): ?>
                    <li class="nav-item"><a class="nav-link" href="/client/qr-builder.php">QR Oluştur</a></li>
                    <li class="nav-item"><a class="nav-link" href="/client/tokens.php">Tokenlar</a></li>
                    <li class="nav-item"><a class="nav-link" href="/client/purchase.php">Paketler</a></li>
                    <li class="nav-item"><a class="nav-link" href="/client/profile.php">Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="/logout.php">Çıkış</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login.php">Giriş</a></li>
                    <li class="nav-item"><a class="nav-link" href="/register.php">Kayıt Ol</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-5">
<?php if ($flash = App\Helpers::flash('message')): ?>
    <div data-flash-message data-type="success" data-message="<?= Helpers::e($flash) ?>"></div>
<?php endif; ?>
