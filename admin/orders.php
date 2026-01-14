<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$orders = db()->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$orderId = (int) ($_GET['view'] ?? 0);
$orderDetail = null;
$orderItems = [];
if ($orderId) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = :id');
    $stmt->execute(['id' => $orderId]);
    $orderDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    $itemsStmt = db()->prepare('SELECT products.name, order_items.quantity, order_items.unit_price FROM order_items INNER JOIN products ON products.id = order_items.product_id WHERE order_items.order_id = :order_id');
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
                        </select>
                        <a class="btn" href="/admin/orders.php?view=<?= (int) $order['id'] ?>">Detay</a>
                        <button class="btn danger" data-delete-order="<?= (int) $order['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php
admin_footer();
?>
