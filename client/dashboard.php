<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\PackageManager;
use App\Subscription;
use App\UsageLogger;

Auth::requireRole('client');
$user = Auth::user();
$packages = PackageManager::allActive();
$active = Subscription::activeForUser((int) $user['id']);
$usageStats = UsageLogger::statsForUser((int) $user['id']);

require __DIR__ . '/../templates/header.php';
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">Hoş geldiniz, <?= Helpers::e($user['username']) ?></h2>
                <a href="/client/qr-builder" class="btn btn-primary">QR Oluştur</a>
            </div>
            <p class="text-white-50">Aktif paketiniz ile dakikalar içerisinde sınırsız QR kodlar oluşturun. API kullanım raporlarını aşağıdan inceleyebilirsiniz.</p>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Toplam İstek</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($usageStats): ?>
                            <?php foreach ($usageStats as $stat): ?>
                                <tr>
                                    <td><?= Helpers::e($stat['date']) ?></td>
                                    <td><?= Helpers::e($stat['total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-white-50">Henüz API isteği bulunmuyor.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
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
<?php require __DIR__ . '/../templates/footer.php'; ?>
