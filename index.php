<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$pdo = db();

$layout = settings('homepage_layout', 'grid');
$sectionContainerEnabled = settings('section_container_enabled', '1') === '1';
$listExcerptLimit = 50;
$summaryFor = static function (array $product) use ($layout, $listExcerptLimit): string {
    if ($layout === 'grid') {
        $short = trim((string) ($product['short_description'] ?? ''));
        return $short !== '' ? $short : excerpt_words((string) ($product['description'] ?? ''), 60);
    }
    return excerpt_words((string) ($product['description'] ?? ''), $listExcerptLimit);
};
$orderBy = 'created_at DESC';
$latestLimit = (int) settings('homepage_latest_limit', '8');
$orderedLimit = (int) settings('homepage_ordered_limit', '8');
$visitedLimit = (int) settings('homepage_visited_limit', '8');
$favoritedLimit = (int) settings('homepage_favorited_limit', '8');
$discountedLimit = (int) settings('homepage_discounted_limit', '8');
$latestProducts = $pdo->query("SELECT products.*, (SELECT AVG(rating) FROM reviews WHERE reviews.product_id = products.id) AS avg_rating, (SELECT COUNT(*) FROM reviews WHERE reviews.product_id = products.id) AS review_count FROM products ORDER BY {$orderBy} LIMIT {$latestLimit}")->fetchAll(PDO::FETCH_ASSOC);
$topOrdered = $pdo->query("SELECT products.*, COUNT(order_items.id) as order_count FROM products LEFT JOIN order_items ON order_items.product_id = products.id GROUP BY products.id ORDER BY order_count DESC LIMIT {$orderedLimit}")->fetchAll(PDO::FETCH_ASSOC);
$topVisited = $pdo->query("SELECT products.*, (SELECT AVG(rating) FROM reviews WHERE reviews.product_id = products.id) AS avg_rating, (SELECT COUNT(*) FROM reviews WHERE reviews.product_id = products.id) AS review_count FROM products ORDER BY visit_count DESC LIMIT {$visitedLimit}")->fetchAll(PDO::FETCH_ASSOC);
$topFavorited = $pdo->query("SELECT products.*, COUNT(favorites.id) as favorite_count FROM products LEFT JOIN favorites ON favorites.product_id = products.id GROUP BY products.id ORDER BY favorite_count DESC LIMIT {$favoritedLimit}")->fetchAll(PDO::FETCH_ASSOC);
$discountedProducts = $pdo->query("SELECT products.* FROM products WHERE discount_value > 0 AND discount_type IS NOT NULL ORDER BY created_at DESC LIMIT {$discountedLimit}")->fetchAll(PDO::FETCH_ASSOC);
$sliders = $pdo->query('SELECT * FROM sliders WHERE is_active = 1 ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$user = current_user();
$favoriteMap = [];
if ($user) {
        $favoriteIds = array_unique(array_merge(
            array_column($latestProducts, 'id'),
            array_column($topOrdered, 'id'),
            array_column($topVisited, 'id'),
            array_column($topFavorited, 'id'),
            array_column($discountedProducts, 'id')
        ));
    if ($favoriteIds) {
        $placeholders = implode(',', array_fill(0, count($favoriteIds), '?'));
        $favStmt = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = ? AND product_id IN ({$placeholders})");
        $favStmt->execute(array_merge([$user['id']], $favoriteIds));
        $favoriteMap = array_fill_keys($favStmt->fetchAll(PDO::FETCH_COLUMN), true);
    }
}

render_header('Ana Sayfa');
?>
<main>
    <?php if ($sliders): ?>
        <section class="hero">
            <?php if ($sectionContainerEnabled): ?>
                <div class="container">
            <?php endif; ?>
                <div class="splide" id="homeSlider">
                    <div class="splide__track">
                        <ul class="splide__list">
                            <?php foreach ($sliders as $slider): ?>
                                <?php
                                $gradientStart = $slider['gradient_start'] ?: '#ffe1e7';
                                $gradientEnd = $slider['gradient_end'] ?: '#fff8fa';
                                $titleColor = $slider['title_color'] ?: '#2c2c2c';
                                $descriptionColor = $slider['description_color'] ?: '#5b5b5b';
                                $imagePosition = ($slider['image_position'] ?? 'right') === 'left' ? 'image-left' : 'image-right';
                                $slideEffect = $slider['slide_effect'] ?? 'fade-up';
                                ?>
                                <li
                                    class="splide__slide hero-slide <?= $imagePosition ?>"
                                    data-effect="<?= htmlspecialchars($slideEffect) ?>"
                                    style="--gradient-start: <?= htmlspecialchars($gradientStart) ?>; --gradient-end: <?= htmlspecialchars($gradientEnd) ?>; --title-color: <?= htmlspecialchars($titleColor) ?>; --description-color: <?= htmlspecialchars($descriptionColor) ?>;"
                                >
                                    <div class="hero-content">
                                        <h1><?= htmlspecialchars($slider['title']) ?></h1>
                                        <p><?= htmlspecialchars($slider['description']) ?></p>
                                        <?php if ($slider['button_url']): ?>
                                            <a class="btn primary" href="<?= htmlspecialchars($slider['button_url']) ?>" title="<?= htmlspecialchars($slider['button_text'] ?: 'Detaylar') ?>"><?= htmlspecialchars($slider['button_text'] ?: 'Detaylar') ?></a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="hero-image">
                                        <img loading="lazy" src="<?= htmlspecialchars($slider['image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($slider['title']) ?>" title="<?= htmlspecialchars($slider['title']) ?>">
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php if ($sectionContainerEnabled): ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($discountedProducts): ?>
        <section class="section framed-section">
            <?php if ($sectionContainerEnabled): ?>
                <div class="container">
            <?php endif; ?>
                <div class="section-header">
                    <h2>İndirimli Ürünler</h2>
                    <hr class="section-divider">
                </div>
                <div class="<?= $layout === 'list' ? 'list-grid' : 'grid' ?>">
                    <?php foreach ($discountedProducts as $product): ?>
                        <?php
                        $isFavorited = isset($favoriteMap[$product['id']]);
                        $hasDiscount = product_has_discount($product);
                        $finalPrice = product_discounted_price($product);
                        ?>
                        <article class="card">
                            <div class="card-media">
                                <img class="product-image" loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" title="<?= htmlspecialchars($product['name']) ?>">
                                <?php if (!empty($product['badge_text'])): ?>
                                    <span class="card-badge"><?= htmlspecialchars($product['badge_text']) ?></span>
                                <?php endif; ?>
                                <?php if ($hasDiscount): ?>
                                    <span class="card-badge discount-badge">İndirimli</span>
                                <?php endif; ?>
                                <?php if ($user): ?>
                                    <button
                                        class="card-fav<?= $isFavorited ? ' is-active' : '' ?>"
                                        type="button"
                                        data-favorite="<?= (int) $product['id'] ?>"
                                        data-favorite-filled="♥"
                                        data-favorite-empty="♡"
                                        aria-pressed="<?= $isFavorited ? 'true' : 'false' ?>"
                                    >
                                        <?= $isFavorited ? '♥' : '♡' ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p><?= htmlspecialchars($summaryFor($product)) ?></p>
                                <p class="price">
                                    <?php if ($hasDiscount): ?>
                                        <span class="price-old"><?= currency((float) $product['price']) ?></span>
                                    <?php endif; ?>
                                    <span class="price-new"><?= currency($finalPrice) ?></span>
                                </p>
                                <a class="btn" href="<?= product_url($product) ?>" title="<?= htmlspecialchars($product['name']) ?>">Ürünü İncele</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php if ($sectionContainerEnabled): ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="section framed-section">
        <?php if ($sectionContainerEnabled): ?>
            <div class="container">
        <?php endif; ?>
            <div class="section-header">
                <h2>Öne Çıkan Ürünler</h2>
                <hr class="section-divider">
            </div>
            <div class="<?= $layout === 'list' ? 'list-grid' : 'grid' ?>">
                <?php foreach ($latestProducts as $product): ?>
                    <?php
                    $isFavorited = isset($favoriteMap[$product['id']]);
                    $hasDiscount = product_has_discount($product);
                    $finalPrice = product_discounted_price($product);
                    ?>
                    <article class="card">
                        <div class="card-media">
                            <img class="product-image" loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" title="<?= htmlspecialchars($product['name']) ?>">
                            <?php if (!empty($product['badge_text'])): ?>
                                <span class="card-badge"><?= htmlspecialchars($product['badge_text']) ?></span>
                            <?php endif; ?>
                            <?php if ($hasDiscount): ?>
                                <span class="card-badge discount-badge">İndirimli</span>
                            <?php endif; ?>
                            <?php if ($user): ?>
                                <button
                                    class="card-fav<?= $isFavorited ? ' is-active' : '' ?>"
                                    type="button"
                                    data-favorite="<?= (int) $product['id'] ?>"
                                    data-favorite-filled="♥"
                                    data-favorite-empty="♡"
                                    aria-pressed="<?= $isFavorited ? 'true' : 'false' ?>"
                                >
                                    <?= $isFavorited ? '♥' : '♡' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($summaryFor($product)) ?></p>
                            <div class="rating-row">
                                <span class="stars"><?= str_repeat('★', (int) round($product['avg_rating'] ?? 0)) ?></span>
                                <span>(<?= (int) ($product['review_count'] ?? 0) ?>)</span>
                            </div>
                            <p class="price">
                                <?php if ($hasDiscount): ?>
                                    <span class="price-old"><?= currency((float) $product['price']) ?></span>
                                <?php endif; ?>
                                <span class="price-new"><?= currency($finalPrice) ?></span>
                            </p>
                            <a class="btn" href="<?= product_url($product) ?>" title="<?= htmlspecialchars($product['name']) ?>">Ürünü İncele</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php if ($sectionContainerEnabled): ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section alt framed-section">
        <?php if ($sectionContainerEnabled): ?>
            <div class="container">
        <?php endif; ?>
            <div class="section-header">
                <h2>En Çok Sipariş Edilenler</h2>
                <hr class="section-divider">
            </div>
            <div class="<?= $layout === 'list' ? 'list-grid' : 'grid' ?>">
                <?php foreach ($topOrdered as $product): ?>
                    <?php
                    $isFavorited = isset($favoriteMap[$product['id']]);
                    $hasDiscount = product_has_discount($product);
                    $finalPrice = product_discounted_price($product);
                    ?>
                    <article class="card">
                        <div class="card-media">
                            <img class="product-image" loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" title="<?= htmlspecialchars($product['name']) ?>">
                            <span class="card-badge"><?= htmlspecialchars($product['badge_text'] ?: 'Çok Satan') ?></span>
                            <?php if ($hasDiscount): ?>
                                <span class="card-badge discount-badge">İndirimli</span>
                            <?php endif; ?>
                            <?php if ($user): ?>
                                <button
                                    class="card-fav<?= $isFavorited ? ' is-active' : '' ?>"
                                    type="button"
                                    data-favorite="<?= (int) $product['id'] ?>"
                                    data-favorite-filled="♥"
                                    data-favorite-empty="♡"
                                    aria-pressed="<?= $isFavorited ? 'true' : 'false' ?>"
                                >
                                    <?= $isFavorited ? '♥' : '♡' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($summaryFor($product)) ?></p>
                            <p class="price">
                                <?php if ($hasDiscount): ?>
                                    <span class="price-old"><?= currency((float) $product['price']) ?></span>
                                <?php endif; ?>
                                <span class="price-new"><?= currency($finalPrice) ?></span>
                            </p>
                            <a class="btn" href="<?= product_url($product) ?>" title="<?= htmlspecialchars($product['name']) ?>">Ürünü İncele</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php if ($sectionContainerEnabled): ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section framed-section">
        <?php if ($sectionContainerEnabled): ?>
            <div class="container">
        <?php endif; ?>
            <div class="section-header">
                <h2>En Çok Ziyaret Edilenler</h2>
                <hr class="section-divider">
            </div>
            <div class="<?= $layout === 'list' ? 'list-grid' : 'grid' ?>">
                <?php foreach ($topVisited as $product): ?>
                    <?php
                    $isFavorited = isset($favoriteMap[$product['id']]);
                    $hasDiscount = product_has_discount($product);
                    $finalPrice = product_discounted_price($product);
                    ?>
                    <article class="card">
                        <div class="card-media">
                            <img class="product-image" loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" title="<?= htmlspecialchars($product['name']) ?>">
                            <span class="card-badge"><?= htmlspecialchars($product['badge_text'] ?: 'Popüler') ?></span>
                            <?php if ($hasDiscount): ?>
                                <span class="card-badge discount-badge">İndirimli</span>
                            <?php endif; ?>
                            <?php if ($user): ?>
                                <button
                                    class="card-fav<?= $isFavorited ? ' is-active' : '' ?>"
                                    type="button"
                                    data-favorite="<?= (int) $product['id'] ?>"
                                    data-favorite-filled="♥"
                                    data-favorite-empty="♡"
                                    aria-pressed="<?= $isFavorited ? 'true' : 'false' ?>"
                                >
                                    <?= $isFavorited ? '♥' : '♡' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($summaryFor($product)) ?></p>
                            <p class="price">
                                <?php if ($hasDiscount): ?>
                                    <span class="price-old"><?= currency((float) $product['price']) ?></span>
                                <?php endif; ?>
                                <span class="price-new"><?= currency($finalPrice) ?></span>
                            </p>
                            <a class="btn" href="<?= product_url($product) ?>" title="<?= htmlspecialchars($product['name']) ?>">Ürünü İncele</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php if ($sectionContainerEnabled): ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<section class="section alt framed-section">
    <?php if ($sectionContainerEnabled): ?>
        <div class="container">
    <?php endif; ?>
        <div class="section-header">
            <h2>En Çok Favoriye Eklenenler</h2>
            <hr class="section-divider">
        </div>
        <div class="<?= $layout === 'list' ? 'list-grid' : 'grid' ?>">
            <?php foreach ($topFavorited as $product): ?>
                <?php
                $isFavorited = isset($favoriteMap[$product['id']]);
                $hasDiscount = product_has_discount($product);
                $finalPrice = product_discounted_price($product);
                ?>
                <article class="card">
                    <div class="card-media">
                        <img class="product-image" loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" title="<?= htmlspecialchars($product['name']) ?>">
                        <span class="card-badge"><?= htmlspecialchars($product['badge_text'] ?: 'Favori') ?></span>
                        <?php if ($hasDiscount): ?>
                            <span class="card-badge discount-badge">İndirimli</span>
                        <?php endif; ?>
                        <?php if ($user): ?>
                            <button
                                class="card-fav<?= $isFavorited ? ' is-active' : '' ?>"
                                type="button"
                                data-favorite="<?= (int) $product['id'] ?>"
                                data-favorite-filled="♥"
                                data-favorite-empty="♡"
                                aria-pressed="<?= $isFavorited ? 'true' : 'false' ?>"
                            >
                                <?= $isFavorited ? '♥' : '♡' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p><?= htmlspecialchars($summaryFor($product)) ?></p>
                        <p class="price">
                            <?php if ($hasDiscount): ?>
                                <span class="price-old"><?= currency((float) $product['price']) ?></span>
                            <?php endif; ?>
                            <span class="price-new"><?= currency($finalPrice) ?></span>
                        </p>
                        <a class="btn" href="<?= product_url($product) ?>" title="<?= htmlspecialchars($product['name']) ?>">Ürünü İncele</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php if ($sectionContainerEnabled): ?>
        </div>
    <?php endif; ?>
</section>
<?php
render_footer();
?>
