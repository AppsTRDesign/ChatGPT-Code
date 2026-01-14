<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$cart = $_SESSION['cart'] ?? [];
$products = [];
if ($cart) {
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = db()->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($cart));
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= (int) ($cart[$product['id']] ?? 1) ?></td>
                        <td><?= currency((float) $product['price']) ?></td>
                        <td>
                            <a class="btn" href="/checkout.php?slug=<?= urlencode($product['slug']) ?>">Siparişe Devam</a>
                            <button class="btn danger" data-cart-remove="<?= (int) $product['id'] ?>">Sil</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php
render_footer();
?>
