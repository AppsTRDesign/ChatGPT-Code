<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$total = (int) db()->query('SELECT COUNT(*) FROM bank_transfer_notifications')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$stmt = db()->prepare('SELECT bank_transfer_notifications.*, orders.total_amount, orders.full_name AS order_name FROM bank_transfer_notifications INNER JOIN orders ON orders.id = bank_transfer_notifications.order_id ORDER BY bank_transfer_notifications.created_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_header('Havale Bildirimleri');
?>
<section class="panel">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Sipariş</th>
                <th>Müşteri</th>
                <th>Banka</th>
                <th>Dekont</th>
                <th>Durum</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notifications as $notification): ?>
                <tr>
                    <td><?= (int) $notification['id'] ?></td>
                    <td>#<?= (int) $notification['order_id'] ?> (<?= currency((float) $notification['total_amount']) ?>)</td>
                    <td><?= htmlspecialchars($notification['full_name'] ?: $notification['order_name']) ?></td>
                    <td><?= htmlspecialchars($notification['bank_name']) ?></td>
                    <td><a href="<?= htmlspecialchars($notification['receipt_path']) ?>" target="_blank" rel="noopener">Dekontu Gör</a></td>
                    <td>
                        <span class="badge badge-<?= htmlspecialchars($notification['status']) ?>">
                            <?= htmlspecialchars(order_status_label($notification['status'])) ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn primary" type="button" data-bank-transfer-approve="<?= (int) $notification['id'] ?>">Siparişi Onayla</button>
                        <button class="btn danger" type="button" data-bank-transfer-delete="<?= (int) $notification['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/bank-transfer-notifications.php?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php
admin_footer();
?>
