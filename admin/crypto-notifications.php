<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$total = (int) db()->query('SELECT COUNT(*) FROM crypto_notifications')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$stmt = db()->prepare('SELECT crypto_notifications.*, orders.total_amount FROM crypto_notifications INNER JOIN orders ON orders.id = crypto_notifications.order_id ORDER BY crypto_notifications.created_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_header('Kripto Bildirimleri');
?>
<section class="panel">
    <table>
        <thead><tr><th>#</th><th>Sipariş</th><th>Ad Soyad</th><th>Tx No</th><th>Cüzdan</th><th>Durum</th><th>İşlem</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= (int) $item['id'] ?></td>
                <td>#<?= (int) $item['order_id'] ?> (<?= currency((float) $item['total_amount']) ?>)</td>
                <td><?= htmlspecialchars($item['full_name']) ?></td>
                <td><?= htmlspecialchars($item['transaction_no']) ?></td>
                <td><?= htmlspecialchars($item['wallet_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars(order_status_label($item['status'])) ?></td>
                <td>
                    <button class="btn primary" data-crypto-approve="<?= (int) $item['id'] ?>">Onayla</button>
                    <button class="btn" data-crypto-reject="<?= (int) $item['id'] ?>">Reddet</button>
                    <button class="btn danger" data-crypto-delete="<?= (int) $item['id'] ?>">Sil</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php admin_footer(); ?>
