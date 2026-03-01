<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$orderId = (int) ($_GET['id'] ?? 0);
if (!$orderId) {
    header('Location: /admin/orders.php');
    exit;
}
$orderStmt = db()->prepare('SELECT * FROM orders WHERE id = :id');
$orderStmt->execute(['id' => $orderId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    header('Location: /admin/orders.php');
    exit;
}
$shippers = db()->query('SELECT * FROM shippers ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);

admin_header('Kargo Durumu Düzenle');
?>
<section class="panel">
    <h2>Sipariş #<?= (int) $order['id'] ?> Kargo Bilgisi</h2>
    <form class="admin-form" data-ajax="order-shipping" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
        <label>Kargo Firması
            <select name="shipper_id" required>
                <option value="">Seçin</option>
                <?php foreach ($shippers as $shipper): ?>
                    <option value="<?= (int) $shipper['id'] ?>" <?= (int) $order['shipper_id'] === (int) $shipper['id'] ? 'selected' : '' ?>><?= htmlspecialchars($shipper['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Takip Numarası<input type="text" name="tracking_number" value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>" required></label>
        <label>Durum
            <select name="status">
                <option value="shipping" <?= $order['status'] === 'shipping' ? 'selected' : '' ?>>Yola Çıktı</option>
                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Teslim Edildi</option>
                <option value="preparing" <?= $order['status'] === 'preparing' ? 'selected' : '' ?>>Hazırlanıyor</option>
            </select>
        </label>
        <div class="button-row">
            <button class="btn primary" type="submit">Kaydet</button>
            <a class="btn" href="/admin/orders.php?view=<?= (int) $order['id'] ?>">Siparişe Dön</a>
        </div>
    </form>
</section>
<?php admin_footer(); ?>
