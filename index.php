<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$pdo = db();

$latestProducts = $pdo->query('SELECT * FROM products ORDER BY created_at DESC LIMIT 6')->fetchAll(PDO::FETCH_ASSOC);
$topOrdered = $pdo->query('SELECT products.*, COUNT(order_items.id) as order_count FROM products LEFT JOIN order_items ON order_items.product_id = products.id GROUP BY products.id ORDER BY order_count DESC LIMIT 6')->fetchAll(PDO::FETCH_ASSOC);
$topVisited = $pdo->query('SELECT * FROM products ORDER BY visit_count DESC LIMIT 6')->fetchAll(PDO::FETCH_ASSOC);

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
                        <img src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="card-body">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['description']) ?></p>
                            <a class="btn" href="/product.php?id=<?= (int) $product['id'] ?>">Ürünü İncele</a>
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
                        <img src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="card-body">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['description']) ?></p>
                            <a class="btn" href="/product.php?id=<?= (int) $product['id'] ?>">Ürünü İncele</a>
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
                        <img src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="card-body">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['description']) ?></p>
                            <a class="btn" href="/product.php?id=<?= (int) $product['id'] ?>">Ürünü İncele</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
<?php
render_footer();
?>
