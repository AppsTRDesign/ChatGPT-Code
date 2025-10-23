<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\PackageManager;
use App\Subscription;

Auth::requireRole('client');
$user = Auth::user();
$packages = PackageManager::allActive();
$active = Subscription::activeForUser((int) $user['id']);
require __DIR__ . '/../templates/header.php';
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">Hoş geldiniz, <?= Helpers::e($user['username']) ?></h2>
                <a href="/client/qr-builder" class="btn btn-primary">QR Oluştur</a>
            </div>
            <p class="text-white-50">Aktif paketiniz ile dakikalar içerisinde QR kodlar oluşturun. API kullanım trendlerinizi grafikten takip edin, detaylı loglara tabloda göz atın.</p>
            <div class="chart-container mb-4">
                <canvas id="clientUsageChart"></canvas>
            </div>
            <div class="table-responsive">
                <table
                    id="clientUsageTable"
                    class="table table-dark table-hover align-middle"
                    data-toggle="table"
                    data-url="/client/data/usage"
                    data-pagination="true"
                    data-page-size="7"
                    data-search="false"
                    data-mobile-responsive="true"
                    data-card-view="false"
                    data-unique-id="date"
                    data-locale="tr-TR"
                    data-response-handler="window.appHandlers.clientUsageResponseHandler"
                >
                    <thead>
                        <tr>
                            <th data-field="date" data-formatter="window.appHandlers.clientUsageDateFormatter" data-sortable="true">Tarih</th>
                            <th data-field="total" data-align="right" data-sortable="true">Toplam İstek</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-4 mb-4">
            <h3 class="h5">Aktif Paket</h3>
            <?php if ($active): ?>
                <?php $limitValue = (int) ($active['limit_snapshot'] ?? $active['monthly_limit']); ?>
                <p class="mb-1">Paket: <strong><?= Helpers::e($active['name']) ?></strong></p>
                <p class="mb-1">Limit: <?= Helpers::e($limitValue > 0 ? $limitValue : 'Sınırsız') ?></p>
                <?php
                $expiryLabel = 'Süre tanımlı değil';
                if (!empty($active['expires_at']) && ($timestamp = strtotime((string) $active['expires_at'])) !== false) {
                    $expiryLabel = date('d.m.Y', $timestamp);
                }
                $remaining = Subscription::usageLeft((int) $user['id']);
                ?>
                <p class="mb-1">Bitiş Tarihi: <?= Helpers::e($expiryLabel) ?></p>
                <p class="mb-0">Kalan Kullanım: <?= Helpers::e($remaining === null ? 'Sınırsız' : $remaining) ?></p>
            <?php else: ?>
                <p class="text-white-50">Aktif bir paketiniz bulunmuyor.</p>
                <a href="/client/purchase" class="btn btn-outline-primary w-100">Paket Satın Al</a>
            <?php endif; ?>
        </div>
        <div class="card p-4 mb-4">
            <h3 class="h5">API Kullanım Özeti</h3>
            <ul class="list-unstyled mb-0" id="clientUsageSummary">
                <li class="text-white-50">Veriler yükleniyor...</li>
            </ul>
        </div>
        <div class="card p-4">
            <h3 class="h5">Popüler Paketler</h3>
            <ul class="list-unstyled mb-0">
                <?php foreach ($packages as $package): ?>
                    <li class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span><?= Helpers::e($package['name']) ?></span>
                            <span><?= Helpers::e(number_format((float) $package['price'], 2)) ?> ₺</span>
                        </div>
                        <small class="text-white-50">Limit: <?= Helpers::e($package['monthly_limit']) ?> / ay</small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<script>
    window.clientUsageConfig = {
        endpoint: '/client/data/usage-metrics'
    };
</script>
<?php require __DIR__ . '/../templates/footer.php'; ?>
