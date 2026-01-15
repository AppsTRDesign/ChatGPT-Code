<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$cart = $_SESSION['cart'] ?? [];
$products = [];
$subtotal = 0.0;
if ($cart) {
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = db()->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($cart));
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as $product) {
        $quantity = (int) ($cart[$product['id']] ?? 1);
        $subtotal += $quantity * (float) $product['price'];
    }
}
$vatRate = (float) settings('vat_rate', '0');
$shippingFee = (float) settings('shipping_fee', '0');
$hasFreeShipping = false;
foreach ($products as $product) {
    if (!empty($product['free_shipping'])) {
        $hasFreeShipping = true;
        break;
    }
}
$shippingFeeApplied = $hasFreeShipping ? 0.0 : $shippingFee;
$vatAmount = $subtotal * ($vatRate / 100);
$grandTotal = $subtotal + $vatAmount + $shippingFeeApplied;

render_header('Sepetim');
?>
<main class="container">
    <h1>Sepetim</h1>
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <?php if (!$products): ?>
        <p>Sepetiniz boş.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Ürün</th>
                    <th>Adet</th>
                    <th>Fiyat</th>
                    <th>Toplam</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <?php $quantity = (int) ($cart[$product['id']] ?? 1); ?>
                    <?php $lineTotal = $quantity * (float) $product['price']; ?>
                    <tr>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= $quantity ?></td>
                        <td><?= currency((float) $product['price']) ?></td>
                        <td><?= currency($lineTotal) ?></td>
                        <td>
                            <button class="btn danger" data-cart-remove="<?= (int) $product['id'] ?>">Sil</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="order-summary">
            <span>Ürünler Toplamı: <?= currency($subtotal) ?></span>
            <span>KDV (%<?= number_format($vatRate, 2, ',', '.') ?>): <?= currency($vatAmount) ?></span>
            <span>Teslimat Ücreti: <?= currency($shippingFeeApplied) ?></span>
            <strong>Genel Toplam: <?= currency($grandTotal) ?></strong>
        </div>
        <div class="button-row">
            <a class="btn primary" href="/checkout.php?cart=1">Siparişi Tamamla</a>
        </div>
    <?php endif; ?>
</main>
<?php
render_footer();
?>
