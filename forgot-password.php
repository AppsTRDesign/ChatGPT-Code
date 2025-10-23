<?php
require_once __DIR__ . '/config/config.php';

use App\Auth;
use App\Helpers;

if (Auth::check()) {
    $destination = Auth::user()['role'] === 'admin' ? '/admin/dashboard' : '/client/dashboard';
    redirect($destination);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Oturum doğrulaması başarısız.');
        redirect('/forgot-password');
    }

    $email = trim($_POST['email'] ?? '');
    if ($email !== '') {
        Auth::createPasswordReset($email);
    }

    Helpers::flash('message', 'Eğer e-posta adresi kayıtlıysa şifre sıfırlama bağlantısı gönderildi.');
    redirect('/login');
}

require __DIR__ . '/templates/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card p-4">
            <h1 class="h4 mb-3">Şifre Sıfırlama</h1>
            <p class="text-white-50">Hesabınıza bağlı e-posta adresinizi girin. Şifre sıfırlama bağlantısı gönderilecektir.</p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">E-posta</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Bağlantı Gönder</button>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>
