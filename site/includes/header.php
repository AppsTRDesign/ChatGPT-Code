<?php
$active = $active ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'NoaSoft Converter Suite') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.7/cdn.min.js" integrity="sha512-pOjFkMrDXLI4YAlnXrhIRbkIuAeGHNirMRHkRkNvztNFVQVw1Gc7YCOUMIqFZ3VAb9YSEuxsjjXNMTvEihQ/Hg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body>
<header class="ns-header">
    <div class="ns-container">
        <div class="ns-brand">
            <a class="ns-logo" href="/index.php">Noa<span>Soft</span> Converter</a>
        </div>
        <button class="ns-nav-toggle" id="navToggle">Menü</button>
        <nav class="ns-nav" id="navMenu">
            <a href="/index.php" class="<?= $active === 'home' ? 'active' : '' ?>">Ana Sayfa</a>
            <a href="/audio.php" class="<?= $active === 'audio' ? 'active' : '' ?>">Ses Dönüştürücü</a>
            <a href="/video.php" class="<?= $active === 'video' ? 'active' : '' ?>">Video Dönüştürücü</a>
            <a href="/image.php" class="<?= $active === 'image' ? 'active' : '' ?>">Görsel Dönüştürücü</a>
            <a href="/faq.php" class="<?= $active === 'faq' ? 'active' : '' ?>">SSS</a>
            <a href="/contact.php" class="<?= $active === 'contact' ? 'active' : '' ?>">İletişim</a>
            <a href="/copyright.php" class="<?= $active === 'copyright' ? 'active' : '' ?>">Telif</a>
        </nav>
    </div>
</header>
<main class="ns-main">
    <div class="ns-container">
