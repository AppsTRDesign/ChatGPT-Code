<?php
require_once __DIR__ . '/config/config.php';

use App\Auth;
use App\Helpers;

$token = $_GET['token'] ?? '';
$success = false;
if ($token !== '') {
    $success = Auth::verifyEmail($token);
}

require __DIR__ . '/templates/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card p-4 text-center">
            <?php if ($success): ?>
                <h1 class="h4 mb-3">E-posta doğrulandı</h1>
                <p class="text-white-50">Hesabınız aktifleştirildi. Şimdi giriş yapabilirsiniz.</p>
                <a href="/login" class="btn btn-primary mt-3">Giriş Yap</a>
            <?php else: ?>
                <h1 class="h4 mb-3">Bağlantı geçersiz</h1>
                <p class="text-white-50">Doğrulama bağlantınız geçersiz veya süresi dolmuş olabilir. Yeni bir bağlantı talep edin.</p>
                <a href="/verify-resend" class="btn btn-outline-light mt-3">Doğrulama talep et</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>
