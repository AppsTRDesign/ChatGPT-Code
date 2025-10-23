<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | <?= Helpers::e(APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@5/dark.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
    <link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <aside class="sidebar p-4 vh-100 position-sticky" style="min-width:260px;">
        <h2 class="h4">Admin Paneli</h2>
        <p class="text-white-50 mb-4"><?= Helpers::e($user['username']) ?></p>
        <nav class="nav flex-column gap-2">
            <a class="nav-link" href="/admin/dashboard">Gösterge Paneli</a>
            <a class="nav-link" href="/admin/users">Üyeler</a>
            <a class="nav-link" href="/admin/packages">Paketler</a>
            <a class="nav-link" href="/admin/payments">Ödeme Bildirimleri</a>
            <a class="nav-link" href="/admin/settings">Ayarlar</a>
            <a class="nav-link" href="/admin/usage">API Raporları</a>
            <a class="nav-link" href="/logout">Çıkış</a>
        </nav>
    </aside>
    <main class="flex-grow-1 p-5">
        <?php if ($flash = App\Helpers::flash('message')): ?>
            <div data-flash-message data-type="success" data-message="<?= Helpers::e($flash) ?>"></div>
        <?php endif; ?>
