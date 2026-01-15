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
$vatAmount = $subtotal * ($vatRate / 100);
$grandTotal = $subtotal + $vatAmount + $shippingFee;

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
                            <a class="btn" href="/checkout.php?slug=<?= urlencode($product['slug']) ?>&qty=<?= $quantity ?>" title="Siparişe Devam">Siparişe Devam</a>
                            <button class="btn danger" data-cart-remove="<?= (int) $product['id'] ?>">Sil</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="order-summary">
            <span>Ürünler Toplamı: <?= currency($subtotal) ?></span>
            <span>KDV (%<?= number_format($vatRate, 2, ',', '.') ?>): <?= currency($vatAmount) ?></span>
            <span>Teslimat Ücreti: <?= currency($shippingFee) ?></span>
            <strong>Genel Toplam: <?= currency($grandTotal) ?></strong>
        </div>
    <?php endif; ?>
</main>
<?php
render_footer();
?>
