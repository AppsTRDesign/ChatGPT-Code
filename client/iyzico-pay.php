<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\Subscription;

Auth::requireRole('client');
$user = Auth::user();

$purchaseId = (int) ($_GET['purchase'] ?? 0);
$checkout = $_SESSION['iyzico_checkout'] ?? null;
if (!$purchaseId || !$checkout || (int) $checkout['purchase_id'] !== $purchaseId) {
    Helpers::flash('message', 'Aktif ödeme oturumu bulunamadı.');
    redirect('/client/purchase');
}

require __DIR__ . '/../templates/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card p-4">
            <h1 class="h4 mb-3">İyzico ile Ödeme</h1>
            <p class="text-white-50">Ödeme işleminizi güvenli İyzico altyapısı üzerinden tamamlayın. Ödeme tamamlandığında paketiniz otomatik olarak aktifleştirilecektir.</p>
            <div class="iyzico-frame bg-white rounded-4 p-3">
                <?php if (!empty($checkout['content'])): ?>
                    <?= $checkout['content'] ?>
                <?php elseif (!empty($checkout['payment_url'])): ?>
                    <iframe src="<?= Helpers::e($checkout['payment_url']) ?>" width="100%" height="600" frameborder="0" allow="payment"></iframe>
                <?php else: ?>
                    <div class="alert alert-warning">Ödeme formu yüklenemedi. Lütfen <a href="/client/purchase" class="alert-link">paketler sayfasına</a> dönerek yeniden deneyin.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
<?php unset($_SESSION['iyzico_checkout']); ?>
