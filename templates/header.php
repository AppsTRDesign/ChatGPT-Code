<?php
require_once __DIR__ . '/../config/config.php';
use App\Activity;
use App\Auth;
use App\Helpers;
use App\Settings;
$siteName = Settings::siteName();
$siteTagline = Settings::siteTagline();
$metaDescription = Settings::metaDescription();
$metaKeywords = Settings::metaKeywords();
$logoUrl = Settings::logoUrl();
$faviconUrl = Settings::faviconUrl();
$currentUser = Auth::user();
Activity::track($currentUser && $currentUser['role'] === 'admin' ? 'admin' : ($currentUser ? 'client' : 'public'));
$appConfig = [
    'baseUrl' => rtrim(BASE_URL, '/'),
    'firebase' => [
        'enabled' => Settings::firebaseEnabled(),
        'config' => Settings::firebaseConfig(),
        'endpoint' => '/firebase-auth',
    ],
    'user' => $currentUser ? [
        'id' => (int) $currentUser['id'],
        'role' => $currentUser['role'],
    ] : null,
    'csrf' => Helpers::csrfToken(),
];
if (empty($appConfig['firebase']['config'])) {
    $appConfig['firebase']['config'] = null;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($siteName) ?><?= $siteTagline ? ' | ' . Helpers::e($siteTagline) : '' ?></title>
    <meta name="description" content="<?= Helpers::e($metaDescription) ?>">
    <meta name="keywords" content="<?= Helpers::e($metaKeywords) ?>">
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
    <?php if (Settings::googleAnalyticsEnabled() && ($gaId = Settings::googleAnalyticsId())): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= Helpers::e($gaId) ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?= Helpers::e($gaId) ?>');
        </script>
    <?php endif; ?>
    <?= Settings::headerHtml() ?>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="/">
            <?php if ($logoUrl): ?>
                <img src="<?= Helpers::e($logoUrl) ?>" alt="<?= Helpers::e($siteName) ?>" class="navbar-logo">
            <?php else: ?>
                <?= Helpers::e($siteName) ?>
            <?php endif; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Menüyü Aç">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/">Anasayfa</a></li>
                <li class="nav-item"><a class="nav-link" href="/client/dashboard">Panel</a></li>
                <li class="nav-item"><a class="nav-link" href="/api-docs">API</a></li>
                <?php if ($currentUser): ?>
                    <li class="nav-item"><a class="nav-link" href="/client/qr-builder">QR Oluştur</a></li>
                    <li class="nav-item"><a class="nav-link" href="/client/qr-history">QR Geçmişi</a></li>
                    <li class="nav-item"><a class="nav-link" href="/client/tokens">Tokenlar</a></li>
                    <li class="nav-item"><a class="nav-link" href="/client/purchase">Paketler</a></li>
                    <li class="nav-item"><a class="nav-link" href="/client/profile">Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="/logout">Çıkış</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login">Giriş</a></li>
                    <li class="nav-item"><a class="nav-link" href="/register">Kayıt Ol</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-5">
<?php if ($flash = App\Helpers::flash('message')): ?>
    <div data-flash-message data-type="success" data-message="<?= Helpers::e($flash) ?>"></div>
<?php endif; ?>
