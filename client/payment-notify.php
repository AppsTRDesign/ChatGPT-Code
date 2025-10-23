<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Helpers;
use App\Subscription;
Auth::requireRole('client');
$user = Auth::user();
$db = Helpers::db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Geçersiz oturum anahtarı.');
        redirect('/client/payment-notify');
    }

    $packageId = (int) $_POST['user_package_id'];
    $amount = trim($_POST['amount'] ?? '');
    $note = trim($_POST['note'] ?? '');

    $stmt = $db->prepare('INSERT INTO payment_notifications (user_id, user_package_id, amount, note) VALUES (:user_id, :user_package_id, :amount, :note)');
    $stmt->execute([
        'user_id' => $user['id'],
        'user_package_id' => $packageId,
        'amount' => $amount,
        'note' => $note,
    ]);

    Helpers::flash('message', 'Ödeme bildiriminiz alınmıştır. En kısa sürede değerlendirilecektir.');
    redirect('/client/payment-notify');
}

$stmt = $db->prepare('SELECT up.*, p.name FROM user_packages up JOIN packages p ON p.id = up.package_id WHERE up.user_id = :user_id AND up.payment_method = "bank" AND up.status IN ("awaiting_payment","payment_missing") ORDER BY up.created_at DESC');
$stmt->execute(['user_id' => $user['id']]);
$bankPackages = $stmt->fetchAll();

require __DIR__ . '/../templates/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card p-4">
            <h2 class="h4">Banka Havalesi Bildirimi</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <div class="mb-3">
                    <label class="form-label">Paket</label>
                    <select class="form-select" name="user_package_id" required <?= empty($bankPackages) ? 'disabled' : '' ?>>
                        <?php foreach ($bankPackages as $pack): ?>
                            <?php $label = Subscription::statusLabel((string) $pack['status']); ?>
                            <option value="<?= Helpers::e($pack['id']) ?>"><?= Helpers::e($pack['name']) ?> - <?= Helpers::e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($bankPackages)): ?>
                        <small class="text-white-50">Banka havalesi bekleyen bir satın alma talebiniz bulunmuyor.</small>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tutar</label>
                    <input type="text" name="amount" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Not</label>
                    <textarea class="form-control" name="note" rows="3" placeholder="Dekont bilgisi"></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100" <?= empty($bankPackages) ? 'disabled' : '' ?>>Bildirim Gönder</button>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
