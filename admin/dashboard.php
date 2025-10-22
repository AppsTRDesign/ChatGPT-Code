<?php
require __DIR__ . '/header.php';

use App\Helpers;

$db = Helpers::db();
$totalUsers = $db->query('SELECT COUNT(*) FROM users WHERE role = "client"')->fetchColumn();
$totalTokens = $db->query('SELECT COUNT(*) FROM api_tokens')->fetchColumn();
$totalUsage = $db->query('SELECT COUNT(*) FROM api_usage_logs')->fetchColumn();
$totalRevenue = $db->query('SELECT SUM(amount) FROM payment_notifications WHERE status = "approved"')->fetchColumn() ?: 0;
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
            <h3 class="h5">Onaylı Ödeme</h3>
            <p class="display-6 fw-bold"><?= number_format((float) $totalRevenue, 2) ?> ₺</p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
