<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$pdo = db();

$latestProducts = $pdo->query('SELECT * FROM products ORDER BY created_at DESC LIMIT 6')->fetchAll(PDO::FETCH_ASSOC);
$topOrdered = $pdo->query('SELECT products.*, COUNT(order_items.id) as order_count FROM products LEFT JOIN order_items ON order_items.product_id = products.id GROUP BY products.id ORDER BY order_count DESC LIMIT 6')->fetchAll(PDO::FETCH_ASSOC);
$topVisited = $pdo->query('SELECT * FROM products ORDER BY visit_count DESC LIMIT 6')->fetchAll(PDO::FETCH_ASSOC);
$topFavorited = $pdo->query('SELECT products.*, COUNT(favorites.id) as favorite_count FROM products LEFT JOIN favorites ON favorites.product_id = products.id GROUP BY products.id ORDER BY favorite_count DESC LIMIT 6')->fetchAll(PDO::FETCH_ASSOC);

render_header('Ana Sayfa');
?>
<main>
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Her Anınıza Uygun Taze Çiçekler</h1>
                <p>Günlük teslimat, kişiselleştirilebilir notlar ve güvenli ödeme seçenekleriyle sevdiklerinizi mutlu edin.</p>
                <a class="btn primary" href="/content.php">Koleksiyonu Keşfet</a>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>En Yeni Ürünler</h2>
            <div class="grid">
                <?php foreach ($latestProducts as $product): ?>
                    <article class="card">
                        <img loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="card-body">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['description']) ?></p>
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
            <div class="grid">
                <?php foreach ($topOrdered as $product): ?>
                    <article class="card">
                        <img loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="card-body">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['description']) ?></p>
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
            <div class="grid">
                <?php foreach ($topVisited as $product): ?>
                    <article class="card">
                        <img loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="card-body">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['description']) ?></p>
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
        <div class="grid">
            <?php foreach ($topFavorited as $product): ?>
                <article class="card">
                    <img loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="card-body">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p><?= htmlspecialchars($product['description']) ?></p>
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
