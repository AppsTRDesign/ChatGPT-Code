<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\IyzicoService;
use App\PackageManager;
use App\Payment;
use App\Subscription;

Auth::requireRole('client');
$user = Auth::user();
$packages = PackageManager::allActive();
$payment = Payment::settings();
$db = Helpers::db();
$stmtUser = $db->prepare('SELECT username, email FROM users WHERE id = :id');
$stmtUser->execute(['id' => $user['id']]);
$userProfile = $stmtUser->fetch() ?: $user;
$activePackage = Subscription::activeForUser((int) $user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Geçersiz oturum anahtarı.');
        redirect('/client/purchase');
    }

    $packageId = (int) $_POST['package_id'];
    $package = PackageManager::find($packageId);
    if (!$package) {
        Helpers::flash('message', 'Paket bulunamadı.');
        redirect('/client/purchase');
    }

    $allowedMethods = [];
    if ($payment['iyzico_enabled']) {
        $allowedMethods[] = 'iyzico';
    }
    if ($payment['bank_enabled']) {
        $allowedMethods[] = 'bank';
    }

    $method = $payment['iyzico_enabled'] ? ($_POST['payment_method'] ?? 'iyzico') : 'bank';
    if (!in_array($method, $allowedMethods, true)) {
        $method = $allowedMethods ? reset($allowedMethods) : 'bank';
    }

    $note = trim($_POST['note'] ?? '');
    $purchaseId = Subscription::requestPurchase((int) $user['id'], $packageId, $method, $note);

    if ($method === 'bank') {
        Helpers::flash('message', 'Satın alma talebiniz alındı. Ödeme bildiriminizi ilettiğinizde admin tarafından onaylanacaktır.');
        redirect('/client/purchase');
    }

    if ($purchaseId > 0) {
        $checkout = IyzicoService::initializeCheckout([
            'id' => $user['id'],
            'email' => $userProfile['email'] ?? $user['email'],
            'username' => $userProfile['username'] ?? $user['username'],
        ], $package, $purchaseId);

        if ($checkout['success']) {
            $_SESSION['iyzico_checkout'] = [
                'purchase_id' => $purchaseId,
                'content' => $checkout['content'] ?? null,
                'payment_url' => $checkout['paymentPageUrl'] ?? null,
            ];
            redirect('/client/iyzico-pay?purchase=' . $purchaseId);
        }

        Subscription::markFailed($purchaseId, 'İyzico: ' . ($checkout['message'] ?? 'Bilinmeyen hata'));
        Helpers::flash('message', 'İyzico ödeme başlatılamadı: ' . ($checkout['message'] ?? 'Bilinmeyen hata'));
    } else {
        Helpers::flash('message', 'Satın alma talebi oluşturulamadı.');
    }

    redirect('/client/purchase');
}

$stmt = $db->prepare('SELECT up.*, p.name FROM user_packages up JOIN packages p ON p.id = up.package_id WHERE user_id = :user_id ORDER BY up.created_at DESC');
$stmt->execute(['user_id' => $user['id']]);
$history = $stmt->fetchAll();

require __DIR__ . '/../templates/header.php';
?>
<div class="row g-4">
    <div class="col-12">
        <div class="card p-4">
            <h2 class="h4 mb-3">Aktif Paketiniz</h2>
            <?php if ($activePackage): ?>
                <?php
                $activeLimit = (int) ($activePackage['limit_snapshot'] ?? $activePackage['monthly_limit']);
                $activeLimitLabel = $activeLimit > 0 ? $activeLimit : 'Sınırsız';
                $activeExpiry = 'Belirlenmedi';
                if (!empty($activePackage['expires_at']) && ($ts = strtotime((string) $activePackage['expires_at'])) !== false) {
                    $activeExpiry = date('d.m.Y', $ts);
                }
                $remainingActive = Subscription::usageLeft((int) $user['id']);
                ?>
                <div class="d-flex flex-column flex-md-row justify-content-between">
                    <div>
                        <p class="mb-1">Paket: <strong><?= Helpers::e($activePackage['name']) ?></strong></p>
                        <p class="mb-1">Limit: <?= Helpers::e($activeLimitLabel) ?></p>
                        <p class="mb-0">Bitiş Tarihi: <?= Helpers::e($activeExpiry) ?></p>
                    </div>
                    <div class="mt-3 mt-md-0 text-md-end">
                        <span class="badge bg-primary fs-6">Kalan Kullanım: <?= Helpers::e($remainingActive === null ? 'Sınırsız' : $remainingActive) ?></span>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-white-50 mb-0">Henüz aktif bir paketiniz bulunmuyor. Aşağıdan uygun paketi seçerek talep oluşturabilirsiniz.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card p-4 h-100">
            <h2 class="h4 mb-4">Paketler</h2>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <?php if (!$packages): ?>
                    <div class="col">
                        <div class="card h-100 d-flex align-items-center justify-content-center">
                            <p class="text-white-50 mb-0">Henüz satın alınabilir paket bulunmuyor.</p>
                        </div>
                    </div>
                <?php endif; ?>
                <?php foreach ($packages as $package): ?>
                    <?php
                    $featuresList = array_filter(preg_split('/\r?\n/', (string) ($package['features'] ?? '')));
                    $isActiveCard = $activePackage && (int) $activePackage['package_id'] === (int) $package['id'];
                    ?>
                    <div class="col">
                        <div class="card h-100 package-card<?= $isActiveCard ? ' border-primary' : '' ?>">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h3 class="h5 mb-1"><?= Helpers::e($package['name']) ?></h3>
                                        <p class="text-white-50 mb-0"><?= Helpers::e($package['description'] ?? '') ?></p>
                                    </div>
                                    <div class="text-end">
                                        <?php if ($isActiveCard): ?>
                                            <span class="badge bg-success mb-2">Aktif Paket</span>
                                        <?php endif; ?>
                                        <span class="badge bg-gradient price-badge d-block"><?= Helpers::e(number_format((float) $package['price'], 2)) ?> ₺</span>
                                    </div>
                                </div>
                                <ul class="list-unstyled flex-grow-1">
                                    <li class="mb-2"><strong>Limit:</strong> <?= Helpers::e($package['monthly_limit']) ?> istek</li>
                                    <li class="mb-3"><strong>Süre:</strong> <?= Helpers::e($package['duration_days']) ?> gün</li>
                                    <?php foreach ($featuresList as $feature): ?>
                                        <li class="mb-1 text-white-50">• <?= Helpers::e($feature) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <form method="post" class="mt-3" data-purchase-form>
                                    <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                                    <input type="hidden" name="package_id" value="<?= Helpers::e($package['id']) ?>">
                                    <div class="mb-2">
                                        <?php $defaultMethod = $payment['iyzico_enabled'] ? 'iyzico' : 'bank'; ?>
                                        <div class="form-floating text-dark bg-white rounded-3">
                                            <select name="payment_method" class="form-select" id="payment_method_<?= Helpers::e($package['id']) ?>" <?= $payment['iyzico_enabled'] ? '' : 'disabled' ?> data-payment-select data-default="<?= Helpers::e($defaultMethod) ?>">
                                                <?php if ($payment['iyzico_enabled']): ?>
                                                    <option value="iyzico"<?= $defaultMethod === 'iyzico' ? ' selected' : '' ?>>Kredi Kartı (İyzico)</option>
                                                <?php endif; ?>
                                                <?php if ($payment['bank_enabled']): ?>
                                                    <option value="bank"<?= $defaultMethod === 'bank' ? ' selected' : '' ?>>Banka Havalesi</option>
                                                <?php endif; ?>
                                            </select>
                                            <label for="payment_method_<?= Helpers::e($package['id']) ?>" class="text-dark">Ödeme Yöntemi</label>
                                        </div>
                                        <?php if (!$payment['iyzico_enabled'] && $payment['bank_enabled']): ?>
                                            <input type="hidden" name="payment_method" value="bank">
                                        <?php endif; ?>
                                        <?php if (!$payment['iyzico_enabled']): ?>
                                            <small class="d-block mt-2 text-white-50">Kredi kartı ayarları yapılmadığı için banka havalesi ile ödeme aktiftir.</small>
                                        <?php endif; ?>
                                        <?php if ($payment['bank_enabled'] && trim((string) $payment['bank_account']) !== ''): ?>
                                            <div class="bank-info mt-3" data-bank-info <?= $defaultMethod === 'bank' ? '' : 'hidden' ?>>
                                                <div class="fw-semibold mb-2">Banka Havalesi Bilgileri</div>
                                                <div class="bank-account text-break small">
                                                    <?= nl2br(Helpers::e($payment['bank_account'])) ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <textarea name="note" class="form-control" rows="2" placeholder="Ödeme notu (opsiyonel)"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Satın Al</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-4 h-100">
            <h2 class="h4">Geçmiş Talepler</h2>
            <p class="text-white-50">Banka havalesi yaptıysanız <a href="/client/payment-notify" class="link-light">ödeme bildirim formu</a> üzerinden dekontu iletebilirsiniz.</p>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Paket</th>
                            <th>Durum</th>
                            <th>Ödeme</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $row): ?>
                            <?php
                            $statusLabel = Subscription::statusLabel((string) $row['status']);
                            $methodLabel = $row['payment_method'] === 'iyzico' ? 'Kredi Kartı (İyzico)' : 'Banka Havalesi';
                            ?>
                            <tr>
                                <td><?= Helpers::e($row['name']) ?></td>
                                <td><?= Helpers::e($statusLabel) ?></td>
                                <td><?= Helpers::e($methodLabel) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
