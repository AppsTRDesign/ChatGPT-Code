<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Helpers;
use App\PackageManager;
use App\Payment;
use App\Subscription;

Auth::requireRole('client');
$user = Auth::user();
$packages = PackageManager::allActive();
$payment = Payment::settings();
$db = Helpers::db();

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

    $method = $payment['iyzico_enabled'] ? ($_POST['payment_method'] ?? 'iyzico') : 'bank';
    Subscription::requestPurchase((int) $user['id'], $packageId, $method, trim($_POST['note'] ?? ''));

    if ($method === 'bank') {
        Helpers::flash('message', 'Banka havalesi bildiriminizi ödeme bildirim sayfasından iletebilirsiniz.');
    } else {
        Helpers::flash('message', 'İyzico ile ödeme için yönlendirme yapılacaktır. (Test ortamı)');
    }

    redirect('/client/purchase');
}

$stmt = $db->prepare('SELECT up.*, p.name FROM user_packages up JOIN packages p ON p.id = up.package_id WHERE user_id = :user_id ORDER BY up.created_at DESC');
$stmt->execute(['user_id' => $user['id']]);
$history = $stmt->fetchAll();

require __DIR__ . '/../templates/header.php';
?>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card p-4 h-100">
            <h2 class="h4">Paket Seçimi</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <div class="mb-3">
                    <label class="form-label">Paket</label>
                    <select name="package_id" class="form-select" required>
                        <?php foreach ($packages as $package): ?>
                            <option value="<?= Helpers::e($package['id']) ?>">
                                <?= Helpers::e($package['name']) ?> - <?= Helpers::e(number_format((float) $package['price'], 2)) ?> ₺ / ay
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ödeme Yöntemi</label>
                    <select name="payment_method" class="form-select" <?= $payment['iyzico_enabled'] ? '' : 'disabled' ?>>
                        <?php if ($payment['iyzico_enabled']): ?>
                            <option value="iyzico">Kredi Kartı (İyzico)</option>
                        <?php endif; ?>
                        <?php if ($payment['bank_enabled']): ?>
                            <option value="bank">Banka Havalesi</option>
                        <?php endif; ?>
                    </select>
                    <?php if (!$payment['iyzico_enabled'] && $payment['bank_enabled']): ?>
                        <input type="hidden" name="payment_method" value="bank">
                    <?php endif; ?>
                    <?php if (!$payment['iyzico_enabled']): ?>
                        <small class="text-white-50">Kredi kartı ayarları yapılmadığı için banka havalesi ile ödeme aktiftir.</small>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Not</label>
                    <textarea name="note" class="form-control" rows="3" placeholder="Ödeme notu"></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Satın Alma Talebi Oluştur</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
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
                            <tr>
                                <td><?= Helpers::e($row['name']) ?></td>
                                <td><?= Helpers::e(ucfirst($row['status'])) ?></td>
                                <td><?= Helpers::e($row['payment_method']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
