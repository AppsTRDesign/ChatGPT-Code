<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/account_nav.php';

$user = current_user();
$favorites = [];
if ($user) {
    $favStmt = db()->prepare('SELECT products.* FROM favorites INNER JOIN products ON products.id = favorites.product_id WHERE favorites.user_id = :id ORDER BY favorites.created_at DESC');
    $favStmt->execute(['id' => $user['id']]);
    $favorites = $favStmt->fetchAll(PDO::FETCH_ASSOC);
}

render_header('Favoriler');
?>
<main class="container">
    <h1>Favoriler</h1>
    <?php if (!$user): ?>
        <p>Favori ürünlerinizi görüntülemek için giriş yapın.</p>
        <div class="button-row">
            <a class="btn" href="/giris">Giriş Yap</a>
            <a class="btn primary" href="/kayit">Üye Ol</a>
        </div>
    <?php else: ?>
        <?php render_account_nav('favoriler'); ?>
        <section class="section">
            <h2>Favorilerim</h2>
            <div class="favorites-list">
                <?php foreach ($favorites as $product): ?>
                    <article class="favorite-item" data-favorite-item>
                        <div class="favorite-media">
                            <img loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <div class="favorite-content">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="favorite-actions">
                                <a class="btn" href="<?= product_url($product) ?>">Ürünü İncele</a>
                                <button
                                    class="btn danger"
                                    type="button"
                                    data-favorite="<?= (int) $product['id'] ?>"
                                    data-favorite-label-add="Favoriye Ekle"
                                    data-favorite-label-remove="Favoriden Çıkar"
                                >
                                    Favoriden Çıkar
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php
render_footer();
?>
