<?php
require_once __DIR__ . '/bootstrap.php';

use App\Services\AuthService;
use App\Services\SettingsService;
use Helpers\Language;

$authService = new AuthService();
if ($authService->user()) {
    header('Location: index.php');
    exit;
}

$baseUrl = rtrim(BASE_URL, '/');
$asset = static fn(string $path): string => $baseUrl . '/' . ltrim($path, '/');

$settingsService = new SettingsService();
$settings = $settingsService->all();
$defaultLanguage = $settings['restaurant']['language'] ?? 'tr';
Language::load($defaultLanguage);
$authStrings = [
    'messages.error_generic' => Language::get('messages.error_generic', 'İşlem gerçekleştirilemedi.'),
    'auth.login_failed' => Language::get('auth.login_failed', 'Giriş başarısız.'),
    'auth.reset.title' => Language::get('auth.reset.title', 'Şifre Sıfırlama'),
    'auth.reset.prompt' => Language::get('auth.reset.prompt', 'E-posta adresinizi girin'),
    'auth.reset.submit' => Language::get('auth.reset.submit', 'Gönder'),
    'auth.reset.cancel' => Language::get('auth.reset.cancel', 'Vazgeç'),
    'auth.reset.placeholder' => Language::get('auth.reset.placeholder', 'admin@noasoft.com'),
    'auth.reset.failed' => Language::get('auth.reset.failed', 'Şifre sıfırlanamadı.'),
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($defaultLanguage) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restoran Girişi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-borderless/borderless.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/style.css')) ?>">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="login-page">
<div class="login-card">
    <div class="login-card__header">
        <h1 class="h4 mb-0">NoaSoft QR Menü</h1>
        <p class="text-muted mb-0">Restoran yönetim paneline giriş yapın.</p>
    </div>
    <div class="login-card__body">
        <form id="loginForm" class="needs-validation" novalidate>
            <div class="mb-3">
                <label class="form-label">E-posta</label>
                <input type="email" name="email" class="form-control" required placeholder="admin@noasoft.com" value="admin@noasoft.com">
            </div>
            <div class="mb-3">
                <label class="form-label">Şifre</label>
                <input type="password" name="password" class="form-control" required placeholder="Şifreniz" value="admin">
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">Beni Hatırla</label>
                </div>
                <button class="btn btn-link p-0" type="button" id="forgotPassword">Şifremi Unuttum</button>
            </div>
            <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
        </form>
    </div>
    <div class="login-card__footer text-muted">
        QR menü panelini kullanmak için demo hesabı: admin@noasoft.com / admin
    </div>
</div>
<script>
    window.APP_BASE_URL = <?= json_encode($baseUrl, JSON_UNESCAPED_UNICODE) ?>;
    window.APP_I18N = <?= json_encode($authStrings, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= htmlspecialchars($asset('assets/js/auth.js')) ?>"></script>
</body>
</html>
