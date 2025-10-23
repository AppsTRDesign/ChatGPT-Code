<?php
require_once __DIR__ . '/config/config.php';

use App\Auth;
use App\Helpers;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Oturum doğrulaması başarısız.');
        redirect('/verify-resend');
    }

    $email = trim($_POST['email'] ?? '');
    $user = Auth::findByEmail($email);
    if ($user && (int) ($user['email_verified'] ?? 0) === 0) {
        Auth::createVerification((int) $user['id'], $email);
        Helpers::flash('message', 'Doğrulama bağlantısı e-postanıza gönderildi.');
        redirect('/login');
    }

    Helpers::flash('message', 'Girilen e-posta için doğrulanmamış hesap bulunamadı.');
    redirect('/verify-resend');
}

require __DIR__ . '/templates/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card p-4">
            <h1 class="h4 mb-3">Doğrulama Mailini Yeniden Gönder</h1>
            <p class="text-white-50">Kayıtlı e-posta adresinizi girerek yeni bir doğrulama bağlantısı alabilirsiniz.</p>
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
