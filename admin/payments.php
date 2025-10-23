<?php
require __DIR__ . '/header.php';

use App\Helpers;
use App\Subscription;

$db = Helpers::db();

if (isset($_GET['approve'])) {
    $id = (int) $_GET['approve'];
    $stmt = $db->prepare('UPDATE payment_notifications SET status = "approved", reviewed_at = NOW() WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $stmt = $db->prepare('SELECT user_package_id FROM payment_notifications WHERE id = :id');
    $stmt->execute(['id' => $id]);
    if ($packageId = $stmt->fetchColumn()) {
        Subscription::activate((int) $packageId);
    }
    Helpers::flash('message', 'Ödeme onaylandı ve paket aktifleştirildi.');
    redirect('/admin/payments');
}

if (isset($_GET['reject'])) {
    $id = (int) $_GET['reject'];
    $db->prepare('UPDATE payment_notifications SET status = "rejected", reviewed_at = NOW() WHERE id = :id')->execute(['id' => $id]);
    Helpers::flash('message', 'Ödeme reddedildi.');
    redirect('/admin/payments');
}

$notifications = $db->query('SELECT pn.*, u.username, p.name as package_name FROM payment_notifications pn JOIN users u ON u.id = pn.user_id LEFT JOIN user_packages up ON up.id = pn.user_package_id LEFT JOIN packages p ON p.id = up.package_id ORDER BY pn.created_at DESC')->fetchAll();
?>
<h1 class="h3 mb-4">Ödeme Bildirimleri</h1>
<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>Paket</th>
                    <th>Tutar</th>
                    <th>Not</th>
                    <th>Durum</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notifications as $notification): ?>
                    <tr>
                        <td><?= Helpers::e($notification['username']) ?></td>
                        <td><?= Helpers::e($notification['package_name'] ?? 'Belirsiz') ?></td>
                        <td><?= Helpers::e($notification['amount']) ?></td>
                        <td><?= Helpers::e($notification['note']) ?></td>
                        <td><?= Helpers::e(ucfirst($notification['status'])) ?></td>
                        <td>
                            <?php if ($notification['status'] === 'pending'): ?>
                                <a class="btn btn-sm btn-success" href="?approve=<?= Helpers::e($notification['id']) ?>">Onayla</a>
                                <a class="btn btn-sm btn-danger" href="?reject=<?= Helpers::e($notification['id']) ?>">Reddet</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
