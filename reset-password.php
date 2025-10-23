<?php
require_once __DIR__ . '/config/config.php';

use App\Auth;
use App\Helpers;

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
if ($token === '') {
    Helpers::flash('message', 'Şifre sıfırlama bağlantısı geçersiz.');
    redirect('/login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Oturum doğrulaması başarısız.');
        redirect('/reset-password?token=' . urlencode($token));
    }

    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['password_confirmation'] ?? '');
    if ($password === '' || $password !== $confirm) {
        Helpers::flash('message', 'Şifreler uyuşmuyor.');
        redirect('/reset-password?token=' . urlencode($token));
    }

    if (Auth::resetPassword($token, $password)) {
        Helpers::flash('message', 'Şifreniz güncellendi. Giriş yapabilirsiniz.');
        redirect('/login');
    }

    Helpers::flash('message', 'Şifre sıfırlama bağlantınız geçersiz veya süresi dolmuş.');
    redirect('/forgot-password');
}

require __DIR__ . '/templates/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card p-4">
            <h1 class="h4 mb-3">Yeni Şifre Belirle</h1>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <input type="hidden" name="token" value="<?= Helpers::e($token) ?>">
                <div class="mb-3">
                    <label for="password" class="form-label">Yeni Şifre</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Yeni Şifre (Tekrar)</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Şifreyi Güncelle</button>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>
