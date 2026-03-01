<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/account_nav.php';

$user = current_user();
$favorites = [];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;
$totalPages = 1;
if ($user) {
    $countStmt = db()->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = :id');
    $countStmt->execute(['id' => $user['id']]);
    $total = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    $favStmt = db()->prepare('SELECT products.* FROM favorites INNER JOIN products ON products.id = favorites.product_id WHERE favorites.user_id = :id ORDER BY favorites.created_at DESC LIMIT :limit OFFSET :offset');
    $favStmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
    $favStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $favStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $favStmt->execute();
    $favorites = $favStmt->fetchAll(PDO::FETCH_ASSOC);
}

render_header('Favoriler');
?>
<main class="container">
    <h1>Favoriler</h1>
    <?php if (!$user): ?>
        <p>Favori ürünlerinizi görüntülemek için giriş yapın.</p>
        <div class="button-row">
            <a class="btn" href="/login">Giriş Yap</a>
            <a class="btn primary" href="/register">Üye Ol</a>
        </div>
    <?php else: ?>
        <?php render_account_nav('favoriler'); ?>
        <section class="section">
            <h2>Favorilerim</h2>
            <?php if (!$favorites): ?>
                <div class="empty-state">Henüz favoriniz yok.</div>
            <?php else: ?>
                <div class="favorites-list">
                    <?php foreach ($favorites as $product): ?>
                        <article class="favorite-item" data-favorite-item>
                            <div class="favorite-media">
                                <img loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" title="<?= htmlspecialchars($product['name']) ?>">
                            </div>
                            <div class="favorite-content">
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <div class="favorite-actions">
                                    <a class="btn" href="<?= product_url($product) ?>" title="<?= htmlspecialchars($product['name']) ?>">Ürünü İncele</a>
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
            <?php endif; ?>
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/favorites?page=<?= $i ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<?php
render_footer();
?>
