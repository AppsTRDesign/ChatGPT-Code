<?php
require __DIR__ . '/header.php';

use App\Helpers;

$db = Helpers::db();
$totalUsers = $db->query('SELECT COUNT(*) FROM users WHERE role = "client"')->fetchColumn();
$totalTokens = $db->query('SELECT COUNT(*) FROM api_tokens')->fetchColumn();
$totalUsage = $db->query('SELECT COUNT(*) FROM api_usage_logs')->fetchColumn();
$pendingPurchases = $db->query('SELECT COUNT(*) FROM user_packages WHERE status IN ("pending","awaiting_payment","payment_missing")')->fetchColumn();
$approvedAmount = $db->query('SELECT SUM(p.price) FROM user_packages up JOIN packages p ON p.id = up.package_id WHERE up.status = "active"')->fetchColumn() ?: 0;
$approvedCount = $db->query('SELECT COUNT(*) FROM user_packages WHERE status = "active"')->fetchColumn();
?>
<div class="row g-4">
    <div class="col-md-3">
        <div class="card p-4 text-center">
            <h3 class="h5">Toplam Üye</h3>
            <p class="display-6 fw-bold"><?= $totalUsers ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 text-center">
            <h3 class="h5">Token Sayısı</h3>
            <p class="display-6 fw-bold"><?= $totalTokens ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 text-center">
            <h3 class="h5">API Çağrısı</h3>
            <p class="display-6 fw-bold"><?= $totalUsage ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 text-center">
            <h3 class="h5">Bekleyen Satın Alım</h3>
            <p class="display-6 fw-bold"><?= (int) $pendingPurchases ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 text-center">
            <h3 class="h5">Onaylı Ödemeler</h3>
            <p class="display-6 fw-bold"><?= number_format((float) $approvedAmount, 2) ?> ₺</p>
            <small class="text-white-50">Aktif paket: <?= (int) $approvedCount ?></small>
        </div>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
