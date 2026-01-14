<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$pdo = db();
$slug = $_GET['slug'] ?? '';
$id = (int) ($_GET['id'] ?? 0);
if ($slug) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
}
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    render_header('Ürün Bulunamadı');
    echo '<main class="container"><p>Ürün bulunamadı.</p></main>';
    render_footer();
    exit;
}

$pdo->prepare('UPDATE products SET visit_count = visit_count + 1 WHERE id = :id')->execute(['id' => $product['id']]);

$images = $pdo->prepare('SELECT * FROM product_images WHERE product_id = :id');
$images->execute(['id' => $product['id']]);
$gallery = $images->fetchAll(PDO::FETCH_ASSOC);

$similarProducts = [];
if ($product['category_id']) {
    $similarStmt = $pdo->prepare('SELECT * FROM products WHERE category_id = :category_id AND id != :id ORDER BY RAND() LIMIT 6');
    $similarStmt->execute(['category_id' => $product['category_id'], 'id' => $product['id']]);
    $similarProducts = $similarStmt->fetchAll(PDO::FETCH_ASSOC);
}

render_header($product['name']);
?>
<main class="container product-detail">
    <div class="product-gallery">
        <div class="splide product-gallery-slider">
            <div class="splide__track">
                <ul class="splide__list">
                    <li class="splide__slide">
                        <a class="lightbox" href="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" data-lightbox="product">
                            <img loading="lazy" class="main-image" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </a>
                    </li>
                    <?php foreach ($gallery as $image): ?>
                        <li class="splide__slide">
                            <a class="lightbox" href="<?= htmlspecialchars($image['image_path']) ?>" data-lightbox="product">
                                <img loading="lazy" src="<?= htmlspecialchars($image['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="product-info">
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        <p class="price"><?= currency((float) $product['price']) ?></p>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="button-row">
            <button class="btn" type="button" data-favorite="<?= (int) $product['id'] ?>">Favoriye Ekle</button>
            <button class="btn" type="button" data-cart-add="<?= (int) $product['id'] ?>">Sepete Ekle</button>
            <a class="btn primary" href="/checkout.php?slug=<?= urlencode($product['slug']) ?>">Siparişe Devam Et</a>
        </div>
        <p class="order-note">WhatsApp siparişleri beklemede düşer, PayTR siparişleri ödeme onayı sonrası onaylanır.</p>
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
<?php if ($similarProducts): ?>
    <section class="section alt">
        <div class="container">
            <h2>Benzer Ürünler</h2>
            <div class="splide" id="similarSlider">
                <div class="splide__track">
                    <ul class="splide__list">
                        <?php foreach ($similarProducts as $similar): ?>
                            <li class="splide__slide">
                                <article class="card">
                                    <img loading="lazy" src="<?= htmlspecialchars($similar['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($similar['name']) ?>">
                                    <div class="card-body">
                                        <h3><?= htmlspecialchars($similar['name']) ?></h3>
                                        <a class="btn" href="<?= product_url($similar) ?>">Ürünü İncele</a>
                                    </div>
                                </article>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'description' => $product['description'],
    'image' => [$product['main_image']],
    'url' => base_url('urun/' . $product['slug']),
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
