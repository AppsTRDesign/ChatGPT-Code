<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/account_nav.php';

$user = current_user();
$orders = [];
$orderItems = [];
if ($user) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE user_id = :id OR email = :email ORDER BY created_at DESC');
    $stmt->execute(['id' => $user['id'], 'email' => $user['email']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($orders) {
        $orderIds = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $itemsStmt = db()->prepare("SELECT order_items.order_id, products.name, order_items.quantity FROM order_items INNER JOIN products ON products.id = order_items.product_id WHERE order_items.order_id IN ({$placeholders})");
        $itemsStmt->execute($orderIds);
        foreach ($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $orderItems[(int) $item['order_id']][] = $item['name'] . ' x' . (int) $item['quantity'];
        }
    }
}

render_header('Siparişler');
?>
<main class="container">
    <h1>Siparişler</h1>
    <?php if (!$user): ?>
        <p>Siparişlerinizi görüntülemek için giriş yapın.</p>
        <div class="button-row">
            <a class="btn" href="/giris">Giriş Yap</a>
            <a class="btn primary" href="/kayit">Üye Ol</a>
        </div>
    <?php else: ?>
        <?php render_account_nav('siparisler'); ?>
        <section class="section">
            <h2>Sipariş Geçmişi</h2>
            <?php if (!$orders): ?>
                <div class="empty-state">Henüz siparişiniz yok.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Sipariş</th>
                            <th>Ürünler</th>
                            <th>Durum</th>
                            <th>Kanal</th>
                            <th>Tutar</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= (int) $order['id'] ?></td>
                                <td><?= htmlspecialchars(implode(', ', $orderItems[(int) $order['id']] ?? [])) ?></td>
                                <td><span class="badge badge-<?= htmlspecialchars($order['status']) ?>"><?= order_status_label($order['status']) ?></span></td>
                                <td>
                                    <span class="badge badge-channel"><?= htmlspecialchars(order_channel_label($order['channel'])) ?></span>
                                    <?php if ($order['channel'] === 'bank_transfer'): ?>
                                        <button class="link-button" type="button" data-bank-transfer-open data-order-id="<?= (int) $order['id'] ?>">Havale Bilgisi</button>
                                    <?php endif; ?>
                                </td>
                                <td><?= currency((float) $order['total_amount']) ?></td>
                                <td><?= htmlspecialchars($order['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<?php if ($user): ?>
    <div class="modal" id="bankTransferModal" aria-hidden="true">
        <div class="modal-content">
            <button class="modal-close" type="button" data-modal-close aria-label="Kapat">×</button>
            <h3>Havale Bilgileri</h3>
            <p><strong>Sipariş No:</strong> <span data-order-label>—</span></p>
            <div class="bank-info">
                <p><strong>Banka:</strong> <?= htmlspecialchars(settings('bank_name')) ?></p>
                <p><strong>IBAN:</strong> <?= htmlspecialchars(settings('bank_iban')) ?></p>
                <p><strong>Alıcı:</strong> <?= htmlspecialchars(settings('bank_account_name')) ?></p>
            </div>
            <form class="bank-transfer-form" data-ajax="bank-transfer-notify" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="order_id" value="">
                <label>Ad Soyad
                    <input type="text" name="full_name" required>
                </label>
                <label>İşlem Yapılan Banka
                    <input type="text" name="bank_name" required>
                </label>
                <label>Dekont (PDF / Görsel)
                    <input type="file" name="receipt" accept=".pdf,image/*" required>
                </label>
                <button class="btn primary" type="submit">Havaleyi Bildir</button>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php
render_footer();
?>
