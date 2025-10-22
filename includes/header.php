<?php
require_once __DIR__ . '/functions.php';

$active = $active ?? '';
$brand = ns_config('site.brand', ns_config('site.name', 'NoaSoft Converter'));
$brandHtml = ns_config('site.brand_html');
$navItems = [
    'home' => ['label' => 'Ana Sayfa', 'href' => ns_link('home', '/')],
    'audio' => ['label' => 'Ses Dönüştürücü', 'href' => ns_link('audio', '/audio-convert')],
    'video' => ['label' => 'Video Dönüştürücü', 'href' => ns_link('video', '/video-convert')],
    'image' => ['label' => 'Görsel Dönüştürücü', 'href' => ns_link('image', '/image-convert')],
    'faq' => ['label' => 'SSS', 'href' => ns_link('faq', '/faq')],
    'contact' => ['label' => 'İletişim', 'href' => ns_link('contact', '/contact')],
    'copyright' => ['label' => 'Telif', 'href' => ns_link('copyright', '/copyright')],
];
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
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.7/cdn.min.js"></script>
</head>
<body>
<header class="ns-header">
    <div class="ns-container">
        <div class="ns-brand">
            <a class="ns-logo" href="<?= htmlspecialchars($navItems['home']['href']) ?>"><?= $brandHtml ? $brandHtml : htmlspecialchars($brand) ?></a>
        </div>
        <button class="ns-nav-toggle" id="navToggle">Menü</button>
        <nav class="ns-nav" id="navMenu">
            <?php foreach ($navItems as $key => $item): ?>
                <a href="<?= htmlspecialchars($item['href']) ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= htmlspecialchars($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
<main class="ns-main">
    <div class="ns-container">
