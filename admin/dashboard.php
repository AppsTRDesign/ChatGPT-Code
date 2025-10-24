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
$failedPurchases = $db->query('SELECT COUNT(*) FROM user_packages WHERE status = "failed"')->fetchColumn();
?>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-6 g-4">
    <div class="col">
        <div class="card p-4 text-center h-100">
            <h3 class="h6 text-white-50">Toplam Üye</h3>
            <p class="display-6 fw-bold mb-1"><?= $totalUsers ?></p>
            <small class="text-white-50">Aktif müşteriler</small>
        </div>
    </div>
    <div class="col">
        <div class="card p-4 text-center h-100">
            <h3 class="h6 text-white-50">Token Sayısı</h3>
            <p class="display-6 fw-bold mb-1"><?= $totalTokens ?></p>
            <small class="text-white-50">Üretilen API anahtarları</small>
        </div>
    </div>
    <div class="col">
        <div class="card p-4 text-center h-100">
            <h3 class="h6 text-white-50">API Çağrısı</h3>
            <p class="display-6 fw-bold mb-1"><?= $totalUsage ?></p>
            <small class="text-white-50">Toplam kayıtlı istek</small>
        </div>
    </div>
    <div class="col">
        <div class="card p-4 text-center h-100">
            <h3 class="h6 text-white-50">Bekleyen Satın Alım</h3>
            <p class="display-6 fw-bold mb-1"><?= (int) $pendingPurchases ?></p>
            <small class="text-white-50">Onay bekleyen talepler</small>
        </div>
    </div>
    <div class="col">
        <div class="card p-4 text-center h-100">
            <h3 class="h6 text-white-50">Onaylı Ödemeler</h3>
            <p class="display-6 fw-bold mb-1"><?= number_format((float) $approvedAmount, 2) ?> ₺</p>
            <small class="text-white-50">Aktif paket: <?= (int) $approvedCount ?></small>
        </div>
    </div>
    <div class="col">
        <div class="card p-4 text-center h-100">
            <h3 class="h6 text-white-50">Başarısız İşlemler</h3>
            <p class="display-6 fw-bold mb-1"><?= (int) $failedPurchases ?></p>
            <small class="text-white-50">Son durumu bekleyen hatalar</small>
        </div>
    </div>
</div>
<div class="row g-4 mt-1">
    <div class="col-lg-8">
        <div class="card p-4 h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h2 class="h5 mb-0">Trafik ve Üyelik</h2>
                <div class="chart-toolbar">
                    <select class="form-select form-select-sm w-auto" id="trafficRange" aria-label="Trafik aralığı">
                        <option value="daily" selected>Günlük</option>
                        <option value="weekly">Haftalık</option>
                        <option value="monthly">Aylık</option>
                        <option value="yearly">Yıllık</option>
                    </select>
                    <button type="button" class="btn btn-outline-light btn-sm" data-chart="traffic" data-chart-export="pdf">PDF</button>
                    <button type="button" class="btn btn-outline-light btn-sm" data-chart="traffic" data-chart-export="excel">Excel</button>
                </div>
            </div>
            <div class="chart-container" style="min-height:260px;">
                <canvas id="dashboardTrafficChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-4 h-100">
            <h2 class="h5">Özet</h2>
            <ul class="list-unstyled mb-0" id="dashboardSummary">
                <li class="text-white-50">Veriler yükleniyor...</li>
            </ul>
        </div>
    </div>
</div>
<div class="card p-4 mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <h2 class="h5 mb-0">Gelir Analizi</h2>
        <div class="chart-toolbar">
            <select class="form-select form-select-sm w-auto" id="revenueRange" aria-label="Gelir aralığı">
                <option value="daily" selected>Günlük</option>
                <option value="weekly">Haftalık</option>
                <option value="monthly">Aylık</option>
                <option value="yearly">Yıllık</option>
            </select>
            <button type="button" class="btn btn-outline-light btn-sm" data-chart="revenue" data-chart-export="pdf">PDF</button>
            <button type="button" class="btn btn-outline-light btn-sm" data-chart="revenue" data-chart-export="excel">Excel</button>
        </div>
    </div>
    <div class="chart-container" style="min-height:260px;">
        <canvas id="dashboardRevenueChart"></canvas>
    </div>
</div>
<script>
    window.dashboardConfig = { endpoint: '/admin/data/dashboard-metrics' };
</script>
<?php require __DIR__ . '/footer.php'; ?>
