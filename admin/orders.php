<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$orders = db()->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

admin_header('Sipariş Yönetimi');
?>
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
                    <td><?= htmlspecialchars($order['status']) ?></td>
                    <td>
                        <select data-order-status="<?= (int) $order['id'] ?>">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Bekleniyor</option>
                            <option value="approved" <?= $order['status'] === 'approved' ? 'selected' : '' ?>>Onaylandı</option>
                            <option value="preparing" <?= $order['status'] === 'preparing' ? 'selected' : '' ?>>Hazırlanıyor</option>
                            <option value="shipping" <?= $order['status'] === 'shipping' ? 'selected' : '' ?>>Yola Çıktı</option>
                            <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Teslim Edildi</option>
                        </select>
                        <button class="btn" data-view-order="<?= (int) $order['id'] ?>">Detay</button>
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
