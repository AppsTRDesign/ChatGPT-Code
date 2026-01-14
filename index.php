<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$pdo = db();

$layout = settings('homepage_layout', 'grid');
$sort = $_GET['sort'] ?? 'recommended';
$sortMap = [
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC',
    'popular' => 'visit_count DESC',
    'new' => 'created_at DESC',
];
$orderBy = $sortMap[$sort] ?? 'created_at DESC';
$latestProducts = $pdo->query("SELECT products.*, (SELECT AVG(rating) FROM reviews WHERE reviews.product_id = products.id) AS avg_rating, (SELECT COUNT(*) FROM reviews WHERE reviews.product_id = products.id) AS review_count FROM products ORDER BY {$orderBy} LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
$topOrdered = $pdo->query('SELECT products.*, COUNT(order_items.id) as order_count FROM products LEFT JOIN order_items ON order_items.product_id = products.id GROUP BY products.id ORDER BY order_count DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);
$topVisited = $pdo->query('SELECT products.*, (SELECT AVG(rating) FROM reviews WHERE reviews.product_id = products.id) AS avg_rating, (SELECT COUNT(*) FROM reviews WHERE reviews.product_id = products.id) AS review_count FROM products ORDER BY visit_count DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);
$topFavorited = $pdo->query('SELECT products.*, COUNT(favorites.id) as favorite_count FROM products LEFT JOIN favorites ON favorites.product_id = products.id GROUP BY products.id ORDER BY favorite_count DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);
$sliders = $pdo->query('SELECT * FROM sliders WHERE is_active = 1 ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

render_header('Ana Sayfa');
?>
<main>
    <?php if ($sliders): ?>
        <section class="hero">
            <div class="container">
                <div class="splide" id="homeSlider">
                    <div class="splide__track">
                        <ul class="splide__list">
                            <?php foreach ($sliders as $slider): ?>
                                <li class="splide__slide hero-slide">
                                    <div class="hero-content">
                                        <h1><?= htmlspecialchars($slider['title']) ?></h1>
                                        <p><?= htmlspecialchars($slider['description']) ?></p>
                                        <?php if ($slider['button_url']): ?>
                                            <a class="btn primary" href="<?= htmlspecialchars($slider['button_url']) ?>"><?= htmlspecialchars($slider['button_text'] ?: 'Detaylar') ?></a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="hero-image">
                                        <img loading="lazy" src="<?= htmlspecialchars($slider['image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($slider['title']) ?>">
                                    </div>
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
            <div class="section-header">
                <h2>Öne Çıkan Ürünler</h2>
                <form class="filter-bar" method="get">
                    <select name="sort">
                        <option value="recommended" <?= $sort === 'recommended' ? 'selected' : '' ?>>Önerilen</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Ucuzdan Pahalıya</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Pahalıdan Ucuza</option>
                        <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>En Yeni</option>
                        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>En Popüler</option>
                    </select>
                    <button class="btn" type="submit">Sırala</button>
                </form>
            </div>
            <div class="<?= $layout === 'list' ? 'list-grid' : 'grid' ?>">
                <?php foreach ($latestProducts as $product): ?>
                    <article class="card">
                        <div class="card-media">
                            <img loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            <span class="card-badge">Ücretsiz Teslimat</span>
                            <button class="card-fav" type="button" data-favorite="<?= (int) $product['id'] ?>">♥</button>
                        </div>
                        <div class="card-body">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['description']) ?></p>
                            <div class="rating-row">
                                <span class="stars"><?= str_repeat('★', (int) round($product['avg_rating'] ?? 0)) ?></span>
                                <span>(<?= (int) ($product['review_count'] ?? 0) ?>)</span>
                            </div>
                            <p class="price"><?= currency((float) $product['price']) ?></p>
                            <a class="btn" href="<?= product_url($product) ?>">Ürünü İncele</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section alt">
        <div class="container">
            <h2>En Çok Sipariş Edilenler</h2>
            <div class="<?= $layout === 'list' ? 'list-grid' : 'grid' ?>">
                <?php foreach ($topOrdered as $product): ?>
                    <article class="card">
                        <div class="card-media">
                            <img loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            <span class="card-badge">Çok Satan</span>
                        </div>
                        <div class="card-body">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['description']) ?></p>
                            <p class="price"><?= currency((float) $product['price']) ?></p>
                            <a class="btn" href="<?= product_url($product) ?>">Ürünü İncele</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>En Çok Ziyaret Edilenler</h2>
            <div class="<?= $layout === 'list' ? 'list-grid' : 'grid' ?>">
                <?php foreach ($topVisited as $product): ?>
                    <article class="card">
                        <div class="card-media">
                            <img loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            <span class="card-badge">Popüler</span>
                        </div>
                        <div class="card-body">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['description']) ?></p>
                            <p class="price"><?= currency((float) $product['price']) ?></p>
                            <a class="btn" href="<?= product_url($product) ?>">Ürünü İncele</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
<section class="section alt">
    <div class="container">
        <h2>En Çok Favoriye Eklenenler</h2>
        <div class="<?= $layout === 'list' ? 'list-grid' : 'grid' ?>">
            <?php foreach ($topFavorited as $product): ?>
                <article class="card">
                    <div class="card-media">
                        <img loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <span class="card-badge">Favori</span>
                    </div>
                    <div class="card-body">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p><?= htmlspecialchars($product['description']) ?></p>
                        <p class="price"><?= currency((float) $product['price']) ?></p>
                        <a class="btn" href="<?= product_url($product) ?>">Ürünü İncele</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
render_footer();
?>
