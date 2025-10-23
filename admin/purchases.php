<?php
require __DIR__ . '/header.php';

use App\Helpers;
use App\Subscription;

$db = Helpers::db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Geçersiz oturum anahtarı.');
        redirect('/admin/purchases');
    }

    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        Helpers::flash('message', 'Satın alma kaydı bulunamadı.');
        redirect('/admin/purchases');
    }

    switch ($action) {
        case 'activate':
            if (Subscription::activate($id)) {
                Helpers::flash('message', 'Paket başarıyla aktifleştirildi.');
            } else {
                Helpers::flash('message', 'Paket aktifleştirilemedi.');
            }
            break;
        case 'awaiting':
            if (Subscription::updateStatus($id, 'awaiting_payment')) {
                Helpers::flash('message', 'Paket durumu ödeme bekliyor olarak güncellendi.');
            }
            break;
        case 'missing':
            if (Subscription::updateStatus($id, 'payment_missing')) {
                Helpers::flash('message', 'Paket durumu eksik ödeme olarak işaretlendi.');
            }
            break;
        case 'reject':
            if (Subscription::updateStatus($id, 'rejected')) {
                Helpers::flash('message', 'Paket talebi reddedildi.');
            }
            break;
        case 'cancel':
            if (Subscription::updateStatus($id, 'cancelled')) {
                Helpers::flash('message', 'Paket talebi iptal edildi.');
            }
            break;
        default:
            Helpers::flash('message', 'Geçersiz işlem.');
            break;
    }

    redirect('/admin/purchases');
}

$purchases = $db->query('SELECT up.*, u.username, u.email, p.name AS package_name FROM user_packages up JOIN users u ON u.id = up.user_id JOIN packages p ON p.id = up.package_id ORDER BY up.created_at DESC')->fetchAll();
?>
<h1 class="h3 mb-4">Satın Alımlar</h1>
<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>Paket</th>
                    <th>Ödeme</th>
                    <th>Durum</th>
                    <th>Not</th>
                    <th>Oluşturma</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$purchases): ?>
                    <tr>
                        <td colspan="7" class="text-center text-white-50">Henüz satın alma talebi bulunmuyor.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($purchases as $purchase): ?>
                        <?php
                        $statusLabel = Subscription::statusLabel((string) $purchase['status']);
                        $statusClass = [
                            'active' => 'bg-success',
                            'awaiting_payment' => 'bg-warning text-dark',
                            'payment_missing' => 'bg-danger',
                            'rejected' => 'bg-danger',
                            'pending' => 'bg-secondary',
                            'cancelled' => 'bg-secondary',
                        ][$purchase['status']] ?? 'bg-secondary';
                        $methodLabel = $purchase['payment_method'] === 'iyzico' ? 'Kredi Kartı (İyzico)' : 'Banka Havalesi';
                        ?>
                        <tr>
                            <td>
                                <strong><?= Helpers::e($purchase['username']) ?></strong><br>
                                <small class="text-white-50"><?= Helpers::e($purchase['email']) ?></small>
                            </td>
                            <td>
                                <?= Helpers::e($purchase['package_name']) ?><br>
                                <?php if (!empty($purchase['expires_at'])): ?>
                                    <small class="text-white-50">Bitiş: <?= Helpers::e($purchase['expires_at']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= Helpers::e($methodLabel) ?></td>
                            <td><span class="badge <?= $statusClass ?>"><?= Helpers::e($statusLabel) ?></span></td>
                            <td><?= $purchase['note'] ? '<small>' . Helpers::e($purchase['note']) . '</small>' : '<span class="text-white-50">-</span>' ?></td>
                            <td>
                                <small class="text-white-50">Talep: <?= Helpers::e($purchase['created_at']) ?></small><br>
                                <?php if (!empty($purchase['activated_at'])): ?>
                                    <small class="text-white-50">Aktif: <?= Helpers::e($purchase['activated_at']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-column flex-lg-row gap-2">
                                    <?php if ($purchase['status'] !== 'active'): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= Helpers::e($purchase['id']) ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="btn btn-sm btn-primary">Onayla</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($purchase['payment_method'] === 'bank' && $purchase['status'] !== 'active'): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= Helpers::e($purchase['id']) ?>">
                                            <input type="hidden" name="action" value="awaiting">
                                            <button type="submit" class="btn btn-sm btn-outline-light">Ödeme Bekleniyor</button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= Helpers::e($purchase['id']) ?>">
                                            <input type="hidden" name="action" value="missing">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">Eksik Ödeme</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($purchase['status'] !== 'rejected' && $purchase['status'] !== 'cancelled' && $purchase['status'] !== 'active'): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= Helpers::e($purchase['id']) ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Bu satın alma reddedilecek. Onaylıyor musunuz?">Reddet</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($purchase['status'] === 'active'): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= Helpers::e($purchase['id']) ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="btn btn-sm btn-outline-light" data-confirm="Aktif paket iptal edilecek. Emin misiniz?">İptal Et</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
