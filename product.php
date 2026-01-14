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

$featureStmt = $pdo->prepare('SELECT * FROM product_features WHERE product_id = :product_id');
$featureStmt->execute(['product_id' => $product['id']]);
$features = $featureStmt->fetchAll(PDO::FETCH_ASSOC);

$reviewStmt = $pdo->prepare('SELECT * FROM reviews WHERE product_id = :product_id ORDER BY created_at DESC');
$reviewStmt->execute(['product_id' => $product['id']]);
$reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
$ratingAvg = 0;
if ($reviews) {
    $ratingAvg = array_sum(array_column($reviews, 'rating')) / count($reviews);
}

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
        <p><?= $product['description'] ?></p>
        <p class="price"><?= currency((float) $product['price']) ?></p>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="button-row">
            <button class="btn" type="button" data-favorite="<?= (int) $product['id'] ?>">Favoriye Ekle</button>
            <button class="btn" type="button" data-cart-add="<?= (int) $product['id'] ?>">Sepete Ekle</button>
            <a class="btn primary" href="/checkout.php?slug=<?= urlencode($product['slug']) ?>">Siparişe Devam Et</a>
        </div>
        <div class="rating-row">
            <span class="stars"><?= str_repeat('★', (int) round($ratingAvg)) ?></span>
            <span><?= count($reviews) ?> değerlendirme</span>
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
<section class="section">
    <div class="container">
        <h2>Ürün Özellikleri</h2>
        <ul class="feature-list">
            <?php foreach ($features as $feature): ?>
                <li><strong><?= htmlspecialchars($feature['feature_name']) ?>:</strong> <?= htmlspecialchars($feature['feature_value']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<section class="section alt">
    <div class="container">
        <h2>Yorumlar (<?= count($reviews) ?>)</h2>
        <div class="reviews">
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <strong><?= htmlspecialchars($review['reviewer_name']) ?></strong>
                        <span class="stars"><?= str_repeat('★', (int) $review['rating']) ?></span>
                    </div>
                    <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <form class="review-form" data-ajax="review" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="text" name="reviewer_name" placeholder="Ad Soyad" required>
            <select name="rating">
                <option value="5">5 Yıldız</option>
                <option value="4">4 Yıldız</option>
                <option value="3">3 Yıldız</option>
                <option value="2">2 Yıldız</option>
                <option value="1">1 Yıldız</option>
            </select>
            <textarea name="comment" rows="4" placeholder="Yorumunuz"></textarea>
            <button class="btn primary" type="submit">Yorum Gönder</button>
        </form>
    </div>
</section>
<script type="application/ld+json">
<?php
$reviewSchema = array_map(static function ($review) {
    return [
        '@type' => 'Review',
        'author' => $review['reviewer_name'],
        'reviewBody' => $review['comment'],
        'reviewRating' => [
            '@type' => 'Rating',
            'ratingValue' => (int) $review['rating'],
        ],
        'datePublished' => $review['created_at'],
    ];
}, $reviews);
$imageSchema = array_merge([$product['main_image']], array_column($gallery, 'image_path'));
?>
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'description' => $product['description'],
    'image' => $imageSchema,
    'url' => base_url('urun/' . $product['slug']),
    'review' => $reviewSchema,
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => $ratingAvg ?: 5,
        'reviewCount' => count($reviews),
    ],
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
