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

$sort = $_GET['review_sort'] ?? 'top';
$sortSql = $sort === 'new' ? 'created_at DESC' : 'likes DESC, created_at DESC';
$perPage = (int) settings('reviews_per_page', '5');
$page = max(1, (int) ($_GET['review_page'] ?? 1));
$offset = ($page - 1) * $perPage;

$reviewCountStmt = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE product_id = :product_id AND approved = 1');
$reviewCountStmt->execute(['product_id' => $product['id']]);
$reviewTotal = (int) $reviewCountStmt->fetchColumn();
$reviewTotalPages = max(1, (int) ceil($reviewTotal / $perPage));

$reviewStmt = $pdo->prepare("SELECT reviews.*, users.avatar FROM reviews LEFT JOIN users ON users.id = reviews.user_id WHERE reviews.product_id = :product_id AND reviews.approved = 1 ORDER BY {$sortSql} LIMIT :limit OFFSET :offset");
$reviewStmt->bindValue(':product_id', $product['id'], PDO::PARAM_INT);
$reviewStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$reviewStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$reviewStmt->execute();
$reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
$avgStmt = $pdo->prepare('SELECT AVG(rating) FROM reviews WHERE product_id = :product_id AND approved = 1');
$avgStmt->execute(['product_id' => $product['id']]);
$ratingAvg = (float) $avgStmt->fetchColumn();

$ratingCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$ratingStmt = $pdo->prepare('SELECT rating, COUNT(*) as count FROM reviews WHERE product_id = :product_id AND approved = 1 GROUP BY rating');
$ratingStmt->execute(['product_id' => $product['id']]);
foreach ($ratingStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $ratingCounts[(int) $row['rating']] = (int) $row['count'];
}

$user = current_user();
$isFavorited = false;
if ($user) {
    $favStmt = $pdo->prepare('SELECT id FROM favorites WHERE user_id = :user_id AND product_id = :product_id');
    $favStmt->execute(['user_id' => $user['id'], 'product_id' => $product['id']]);
    $isFavorited = (bool) $favStmt->fetchColumn();
}

$similarProducts = [];
if ($product['category_id']) {
    $similarStmt = $pdo->prepare('SELECT * FROM products WHERE category_id = :category_id AND id != :id ORDER BY RAND() LIMIT 6');
    $similarStmt->execute(['category_id' => $product['category_id'], 'id' => $product['id']]);
    $similarProducts = $similarStmt->fetchAll(PDO::FETCH_ASSOC);
}

$mainImage = $product['main_image'] ?: '/assets/images/placeholder.svg';
$metaImage = absolute_url($mainImage);
render_header($product['name'], ['image' => $metaImage]);
?>
<main class="container product-detail">
    <div class="product-gallery">
        <div class="splide product-gallery-slider">
            <div class="splide__track">
                <ul class="splide__list">
                    <li class="splide__slide">
                        <a class="lightbox" href="<?= htmlspecialchars($mainImage) ?>" data-lightbox="product">
                            <img loading="lazy" class="main-image product-image" src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </a>
                    </li>
                    <?php foreach ($gallery as $image): ?>
                        <li class="splide__slide">
                            <a class="lightbox" href="<?= htmlspecialchars($image['image_path']) ?>" data-lightbox="product">
                                <img loading="lazy" class="product-image" src="<?= htmlspecialchars($image['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="product-info">
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <p class="price"><?= currency((float) $product['price']) ?></p>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="button-row">
            <?php if ($user): ?>
                <button
                    class="btn<?= $isFavorited ? ' primary' : '' ?>"
                    type="button"
                    data-favorite="<?= (int) $product['id'] ?>"
                    data-favorite-label-add="Favoriye Ekle"
                    data-favorite-label-remove="Favorilerden Çıkar"
                    aria-pressed="<?= $isFavorited ? 'true' : 'false' ?>"
                >
                    <?= $isFavorited ? 'Favorilerden Çıkar' : 'Favoriye Ekle' ?>
                </button>
            <?php endif; ?>
            <button class="btn" type="button" data-cart-add="<?= (int) $product['id'] ?>">Sepete Ekle</button>
            <a class="btn primary" href="/checkout.php?slug=<?= urlencode($product['slug']) ?>">Siparişe Devam Et</a>
        </div>
        <div class="rating-row">
            <span class="stars"><?= render_stars((int) round($ratingAvg)) ?></span>
            <span><?= $reviewTotal ?> değerlendirme</span>
        </div>
        <div class="product-meta">
            <?php if (!empty($product['sku'])): ?>
                <p><strong>Ürün Kodu:</strong> <?= htmlspecialchars($product['sku']) ?></p>
            <?php endif; ?>
            <p><strong>Stok:</strong> <?= (int) $product['stock'] ?></p>
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
<section class="section">
    <div class="container">
        <div class="product-tabs" data-tabs>
            <div class="tab-list" role="tablist">
                <button class="tab-button is-active" type="button" role="tab" aria-selected="true" data-tab-target="#tab-description">Açıklama</button>
                <button class="tab-button" type="button" role="tab" aria-selected="false" data-tab-target="#tab-features">Ürün Özellikleri</button>
                <button class="tab-button" type="button" role="tab" aria-selected="false" data-tab-target="#tab-reviews">Yorumlar</button>
            </div>
            <div class="tab-panel is-active" id="tab-description" role="tabpanel">
                <?= $product['description'] ?>
            </div>
            <div class="tab-panel" id="tab-features" role="tabpanel">
                <ul class="feature-list">
                    <?php foreach ($features as $feature): ?>
                        <li><strong><?= htmlspecialchars($feature['feature_name']) ?>:</strong> <?= htmlspecialchars($feature['feature_value']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="tab-panel" id="tab-reviews" role="tabpanel">
                <div class="review-summary">
                    <div>
                        <h2>Yorumlar (<?= $reviewTotal ?>)</h2>
                        <p class="rating-big"><?= number_format($ratingAvg, 1, ',', '.') ?></p>
                        <div class="stars"><?= render_stars((int) round($ratingAvg)) ?></div>
                    </div>
                    <div class="rating-bars">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <?php
                            $count = $ratingCounts[$i] ?? 0;
                            $percentage = $reviewTotal ? ($count / $reviewTotal) * 100 : 0;
                            ?>
                            <div class="rating-bar">
                                <span><?= $i ?></span>
                                <div class="bar"><span style="width: <?= $percentage ?>%"></span></div>
                                <span><?= $count ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="review-filter">
                    <form method="get">
                        <input type="hidden" name="slug" value="<?= htmlspecialchars($product['slug']) ?>">
                        <select name="review_sort" onchange="this.form.submit()">
                            <option value="top" <?= $sort === 'top' ? 'selected' : '' ?>>En Faydalı</option>
                            <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>En Yeni</option>
                        </select>
                    </form>
                </div>
                <div class="reviews" id="reviewsContainer" data-product-id="<?= (int) $product['id'] ?>" data-review-sort="<?= htmlspecialchars($sort) ?>">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="review-user">
                                    <div class="avatar">
                                        <?php if (!empty($review['avatar'])): ?>
                                            <img src="<?= htmlspecialchars($review['avatar']) ?>" alt="<?= htmlspecialchars($review['reviewer_name']) ?>">
                                        <?php else: ?>
                                            <?= strtoupper(mb_substr($review['reviewer_name'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($review['reviewer_name']) ?></strong>
                                        <span class="review-date"><?= htmlspecialchars($review['created_at']) ?></span>
                                    </div>
                                </div>
                                <span class="stars"><?= render_stars((int) $review['rating']) ?></span>
                            </div>
                            <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                            <button class="btn" type="button" data-review-like="<?= (int) $review['id'] ?>" data-review-likes="<?= (int) $review['likes'] ?>">Faydalı (<?= (int) $review['likes'] ?>)</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="pagination" id="reviewsPagination">
                    <?php for ($i = 1; $i <= $reviewTotalPages; $i++): ?>
                        <button class="btn <?= $i === $page ? 'primary' : '' ?>" type="button" data-review-page="<?= $i ?>"><?= $i ?></button>
                    <?php endfor; ?>
                </div>
                <form class="review-form" data-ajax="review" method="post">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
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
        </div>
    </div>
</section>
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
                                    <img class="product-image" loading="lazy" src="<?= htmlspecialchars($similar['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($similar['name']) ?>">
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
<?php
$imageSchema = array_merge([$mainImage], array_column($gallery, 'image_path'));
$imageSchema = array_values(array_filter($imageSchema, static fn($image) => $image !== ''));
$imageSchema = array_map('absolute_url', $imageSchema);
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'description' => excerpt_words($product['description'], 160),
    'image' => $imageSchema,
    'url' => base_url('urun/' . $product['slug']),
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'TRY',
        'price' => (float) $product['price'],
        'availability' => 'https://schema.org/InStock',
    ],
];
if ($reviewTotal > 0) {
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
    $schema['review'] = $reviewSchema;
    $schema['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => $ratingAvg ?: 5,
        'reviewCount' => $reviewTotal,
    ];
} else {
    $schema['review'] = [];
    $schema['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => 5,
        'reviewCount' => 0,
    ];
}
?>
<?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
</script>
<?php
render_footer();
?>
