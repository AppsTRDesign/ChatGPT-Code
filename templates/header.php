<?php
require_once __DIR__ . '/../config.php';
$settings = fetch_settings($pdo);
$currentUser = current_user();
$title = $settings['meta_title'] ?? 'NoaSoft Dosya Deposu';
$description = $settings['meta_description'] ?? 'Dosyalarınızı güvenle saklayın ve paylaşın.';
$keywords = trim((string) ($settings['meta_keywords'] ?? ''));
$logoPath = !empty($settings['logo']) ? BASE_URL . '/uploads/' . ltrim($settings['logo'], '/') : BASE_URL . '/assets/img/logo.svg';
$faviconPath = !empty($settings['favicon']) ? BASE_URL . '/uploads/' . ltrim($settings['favicon'], '/') : BASE_URL . '/assets/img/favicon.svg';
$bannerPath = !empty($settings['brand_banner']) ? BASE_URL . '/uploads/' . ltrim($settings['brand_banner'], '/') : null;
$socialImagePath = !empty($settings['social_image']) ? BASE_URL . '/uploads/' . ltrim($settings['social_image'], '/') : ($bannerPath ?? $logoPath);
$socialTitle = trim((string) ($settings['social_title'] ?? '')) ?: $title;
$socialDescription = trim((string) ($settings['social_description'] ?? '')) ?: $description;
$twitterHandle = ltrim((string) ($settings['twitter_handle'] ?? ''), '@');
$currentUrl = BASE_URL . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'url' => BASE_URL,
    'name' => $socialTitle,
    'description' => $socialDescription,
    'image' => $socialImagePath,
    'publisher' => [
        '@type' => 'Organization',
        'name' => $socialTitle,
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $logoPath,
        ],
    ],
];
$structuredJson = json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= sanitize($description) ?>">
    <?php if ($keywords !== ''): ?>
        <meta name="keywords" content="<?= sanitize($keywords) ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:url" content="<?= sanitize($currentUrl) ?>">
    <meta property="og:title" content="<?= sanitize($socialTitle) ?>">
    <meta property="og:description" content="<?= sanitize($socialDescription) ?>">
    <meta property="og:site_name" content="<?= sanitize($socialTitle) ?>">
    <meta property="og:image" content="<?= sanitize($socialImagePath) ?>">
    <meta property="og:logo" content="<?= sanitize($logoPath) ?>">
    <meta name="twitter:card" content="<?= $bannerPath ? 'summary_large_image' : 'summary' ?>">
    <meta name="twitter:title" content="<?= sanitize($socialTitle) ?>">
    <meta name="twitter:description" content="<?= sanitize($socialDescription) ?>">
    <meta name="twitter:image" content="<?= sanitize($socialImagePath) ?>">
    <meta name="twitter:image:alt" content="<?= sanitize($socialTitle) ?>">
    <?php if ($twitterHandle !== ''): ?>
        <meta name="twitter:site" content="@<?= sanitize($twitterHandle) ?>">
        <meta name="twitter:creator" content="@<?= sanitize($twitterHandle) ?>">
    <?php endif; ?>
    <script type="application/ld+json">
        <?= $structuredJson ?>
    </script>
    <title><?= sanitize($title) ?></title>
    <link rel="icon" href="<?= sanitize($faviconPath) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=1.2.0">
    <?php if (!empty($settings['analytics_enabled']) && !empty($settings['analytics_code'])): ?>
        <?= $settings['analytics_code'] ?>
    <?php endif; ?>
    <?= $settings['header_html'] ?? '' ?>
    <script>
        window.APP_CONFIG = Object.assign({}, window.APP_CONFIG || {}, {
            baseUrl: '<?= BASE_URL ?>',
            csrfToken: '<?= csrf_token() ?>',
            paymentProviders: <?= json_encode([
                'iyzico' => !empty($settings['iyzico_enabled']),
                'stripe' => !empty($settings['stripe_enabled']),
                'bank_transfer' => !empty($settings['bank_transfer_enabled']),
            ], JSON_UNESCAPED_SLASHES) ?>,
            bankInstructions: <?= json_encode($settings['bank_transfer_instructions'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
            realtime: <?= json_encode([
                'enabled' => !empty($settings['realtime_updates_enabled']),
                'wsUrl' => $settings['realtime_ws_url'] ?? null,
            ], JSON_UNESCAPED_SLASHES) ?>,
            userId: <?= $currentUser ? (int) $currentUser['id'] : 'null' ?>
        });
    </script>
</head>
<body>
<?php
$path = $_SERVER['SCRIPT_NAME'] ?? '';
$isAdminArea = str_contains($path, '/admin/');
$isClientArea = str_contains($path, '/client/');
$panelLinks = [];
if ($currentUser) {
    if (is_admin()) {
        $panelLinks = [
            ['label' => 'Genel Bakış', 'href' => BASE_URL . '/admin', 'active' => str_ends_with($path, '/admin/index.php')],
            ['label' => 'Dosyalar', 'href' => BASE_URL . '/admin/files.php', 'active' => str_ends_with($path, '/admin/files.php')],
            ['label' => 'Üyeler', 'href' => BASE_URL . '/admin/users.php', 'active' => str_ends_with($path, '/admin/users.php')],
            ['label' => 'Paketler', 'href' => BASE_URL . '/admin/packages.php', 'active' => str_ends_with($path, '/admin/packages.php')],
            ['label' => 'Satın Alımlar', 'href' => BASE_URL . '/admin/purchases.php', 'active' => str_ends_with($path, '/admin/purchases.php')],
            ['label' => 'Ödeme Bildirimleri', 'href' => BASE_URL . '/admin/payment-notifications.php', 'active' => str_ends_with($path, '/admin/payment-notifications.php')],
            ['label' => 'Genel Ayarlar', 'href' => BASE_URL . '/admin/settings.php', 'active' => str_ends_with($path, '/admin/settings.php')],
            ['label' => 'Entegrasyonlar', 'href' => BASE_URL . '/admin/integrations.php', 'active' => str_ends_with($path, '/admin/integrations.php')],
        ];
    } else {
        $panelLinks = [
            ['label' => 'Kontrol Paneli', 'href' => BASE_URL . '/client', 'active' => str_ends_with($path, '/client/index.php')],
            ['label' => 'Dosyalarım', 'href' => BASE_URL . '/client/files.php', 'active' => str_ends_with($path, '/client/files.php')],
            ['label' => 'Profil', 'href' => BASE_URL . '/client/profile.php', 'active' => str_ends_with($path, '/client/profile.php')],
            ['label' => 'Paketler', 'href' => BASE_URL . '/client/packages.php', 'active' => str_ends_with($path, '/client/packages.php')],
            ['label' => 'Paylaşım Analitiği', 'href' => BASE_URL . '/client/share-stats.php', 'active' => str_ends_with($path, '/client/share-stats.php')],
        ];
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>">
            <img src="<?= sanitize($logoPath) ?>" alt="Logo" width="36" height="36" class="rounded-circle bg-white p-1">
            <span class="fw-bold">NoaSoft Depo</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <?php if (!$isAdminArea): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>#ozellikler">Özellikler</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/contact">İletişim</a></li>
                <?php endif; ?>
                <?php foreach ($panelLinks as $link): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= !empty($link['active']) ? ' active' : '' ?>" href="<?= sanitize($link['href']) ?>"><?= sanitize($link['label']) ?></a>
                    </li>
                <?php endforeach; ?>
                <?php if (!current_user()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/login">Giriş Yap</a></li>
                    <li class="nav-item"><a class="btn btn-primary ms-lg-2" href="<?= BASE_URL ?>/register">Üye Ol</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="btn btn-outline-light ms-lg-2" href="<?= BASE_URL ?>/logout">Çıkış Yap</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="page-wrapper">
