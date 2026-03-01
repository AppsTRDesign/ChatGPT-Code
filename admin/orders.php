<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$total = (int) db()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$ordersStmt = db()->prepare('SELECT * FROM orders ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$ordersStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$ordersStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$ordersStmt->execute();
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
$orderId = (int) ($_GET['view'] ?? 0);
$orderDetail = null;
$orderItems = [];
if ($orderId) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = :id');
    $stmt->execute(['id' => $orderId]);
    $orderDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    $itemsStmt = db()->prepare('SELECT products.name, order_items.unit_price, order_items.quantity FROM order_items INNER JOIN products ON products.id = order_items.product_id WHERE order_items.order_id = :order_id');
    $itemsStmt->execute(['order_id' => $orderId]);
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
}

admin_header('Sipariş Yönetimi');
?>
<?php if ($orderDetail): ?>
<section class="panel">
    <h2>Sipariş Detayı #<?= (int) $orderDetail['id'] ?></h2>
    <p><strong>Ad Soyad:</strong> <?= htmlspecialchars($orderDetail['full_name']) ?></p>
    <p><strong>E-posta:</strong> <?= htmlspecialchars($orderDetail['email']) ?></p>
    <p><strong>Telefon:</strong> <?= htmlspecialchars($orderDetail['phone']) ?></p>
    <p><strong>Adres:</strong> <?= htmlspecialchars($orderDetail['address']) ?></p>
    <p><strong>Sipariş Notu:</strong> <?= htmlspecialchars($orderDetail['order_note'] ?? '-') ?></p>
    <p><strong>Durum:</strong> <span class="badge badge-<?= htmlspecialchars($orderDetail['status']) ?>"><?= order_status_label($orderDetail['status']) ?></span></p>
    <h3>Ürünler</h3>
    <table>
        <thead>
            <tr>
                <th>Ürün</th>
                <th>Adet</th>
                <th>Birim Fiyat</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td><?= currency((float) $item['unit_price']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/orders.php?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>
<section class="panel">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Müşteri</th>
                <th>E-posta</th>
                <th>Telefon</th>
                <th>Kanal</th>
                <th>Durum</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= (int) $order['id'] ?></td>
                    <td><?= htmlspecialchars($order['full_name']) ?></td>
                    <td><?= htmlspecialchars($order['email']) ?></td>
                    <td><?= htmlspecialchars($order['phone']) ?></td>
                    <td><?= htmlspecialchars($order['channel']) ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($order['status']) ?>"><?= order_status_label($order['status']) ?></span></td>
                    <td>
                        <select data-order-status="<?= (int) $order['id'] ?>">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Bekleniyor</option>
                            <option value="approved" <?= $order['status'] === 'approved' ? 'selected' : '' ?>>Onaylandı</option>
                            <option value="preparing" <?= $order['status'] === 'preparing' ? 'selected' : '' ?>>Hazırlanıyor</option>
                            <option value="shipping" <?= $order['status'] === 'shipping' ? 'selected' : '' ?>>Yola Çıktı</option>
                            <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Teslim Edildi</option>
                            <option value="rejected" <?= $order['status'] === 'rejected' ? 'selected' : '' ?>>Reddedildi</option>
                        </select>
                        <a class="btn" href="/admin/orders.php?view=<?= (int) $order['id'] ?>">Detay</a>
                        <button class="btn danger" data-delete-order="<?= (int) $order['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/orders.php?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php
admin_footer();
?>
