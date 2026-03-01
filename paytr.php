<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/includes/paytr_gateway.php';

$orderId = (int) ($_GET['order_id'] ?? 0);
$order = null;
if ($orderId) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = :id');
    $stmt->execute(['id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

render_header('PayTR Ödeme');
?>
<main class="container">
    <h1>PayTR Ödeme</h1>
    <?php if (!$order): ?>
        <p>Sipariş bulunamadı.</p>
    <?php else: ?>
        <div class="panel">
            <p>Sipariş No: #<?= (int) $order['id'] ?></p>
            <p>Toplam Tutar: <?= currency((float) $order['total_amount']) ?></p>
            <?= render_paytr_iframe($order) ?>
        </div>
    <?php endif; ?>
</main>
<?php
render_footer();
?>
