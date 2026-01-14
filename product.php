<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute(['id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    render_header('Ürün Bulunamadı');
    echo '<main class="container"><p>Ürün bulunamadı.</p></main>';
    render_footer();
    exit;
}

$pdo->prepare('UPDATE products SET visit_count = visit_count + 1 WHERE id = :id')->execute(['id' => $id]);

$images = $pdo->prepare('SELECT * FROM product_images WHERE product_id = :id');
$images->execute(['id' => $id]);
$gallery = $images->fetchAll(PDO::FETCH_ASSOC);

render_header($product['name']);
?>
<main class="container product-detail">
    <div class="product-gallery">
        <img class="main-image" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        <div class="thumbnail-row">
            <?php foreach ($gallery as $image): ?>
                <img src="<?= htmlspecialchars($image['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php endforeach; ?>
        </div>
    </div>
    <div class="product-info">
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        <p class="price"><?= currency((float) $product['price']) ?></p>
        <form class="order-form" data-ajax="order" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <div class="form-grid">
                <input type="text" name="full_name" placeholder="Ad Soyad" required>
                <input type="email" name="email" placeholder="E-posta" required>
                <input type="tel" name="phone" placeholder="Telefon" required>
                <textarea name="address" placeholder="Teslimat Adresi" rows="3"></textarea>
                <input type="number" name="quantity" min="1" value="1" required>
            </div>
            <button class="btn primary" type="submit">Sipariş Ver</button>
            <p class="order-note">WhatsApp siparişleri beklemede düşer, PayTR siparişleri otomatik onaylanır.</p>
        </form>
        <?php if ($product['order_channel'] === 'whatsapp'): ?>
            <?php
            $whatsapp = $product['order_link'] ?: settings('whatsapp_number');
            $message = urlencode($product['name'] . ' için sipariş vermek istiyorum.');
            ?>
            <a class="btn" href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsapp) ?>?text=<?= $message ?>" target="_blank" rel="noopener">WhatsApp ile Sipariş</a>
        <?php endif; ?>
        <?php if (settings('paytr_active') === '1'): ?>
            <div class="paytr-panel">
                <h3>PayTR ile Güvenli Ödeme</h3>
                <p>PayTR entegrasyon dosyaları includes/ klasöründen alınacak şekilde hazırdır.</p>
                <a class="btn" href="/paytr.php?product_id=<?= (int) $product['id'] ?>">PayTR ile Öde</a>
            </div>
        <?php endif; ?>
    </div>
</main>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'description' => $product['description'],
    'image' => [$product['main_image']],
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'TRY',
        'price' => (float) $product['price'],
        'availability' => 'https://schema.org/InStock',
    ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
</script>
<?php
render_footer();
?>
