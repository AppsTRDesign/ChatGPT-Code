<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/account_nav.php';

$user = current_user();
$reviews = [];
$reviewTotal = 0;
$perPage = (int) settings('reviews_per_page', '5');
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

if ($user) {
    $countStmt = db()->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = :id');
    $countStmt->execute(['id' => $user['id']]);
    $reviewTotal = (int) $countStmt->fetchColumn();

    $reviewStmt = db()->prepare('SELECT reviews.*, products.slug, products.name AS product_name FROM reviews INNER JOIN products ON products.id = reviews.product_id WHERE reviews.user_id = :id ORDER BY reviews.created_at DESC LIMIT :limit OFFSET :offset');
    $reviewStmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
    $reviewStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $reviewStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $reviewStmt->execute();
    $reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalPages = max(1, (int) ceil($reviewTotal / $perPage));

render_header('Yorumlar');
?>
<main class="container">
    <h1>Yorumlar</h1>
    <?php if (!$user): ?>
        <p>Yorumlarınızı görüntülemek için giriş yapın.</p>
        <div class="button-row">
            <a class="btn" href="/giris">Giriş Yap</a>
            <a class="btn primary" href="/kayit">Üye Ol</a>
        </div>
    <?php else: ?>
        <?php render_account_nav('yorumlar'); ?>
        <section class="section">
            <h2>Yorumlarım</h2>
            <div class="reviews">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <strong><a href="/urun/<?= urlencode($review['slug']) ?>"><?= htmlspecialchars($review['product_name']) ?></a></strong>
                            <span class="stars"><?= render_stars((int) $review['rating']) ?></span>
                        </div>
                        <form class="review-edit-form" data-ajax="review-update" method="post">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>">
                            <label>Puan
                                <select name="rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <option value="<?= $i ?>" <?= (int) $review['rating'] === $i ? 'selected' : '' ?>><?= $i ?> Yıldız</option>
                                    <?php endfor; ?>
                                </select>
                            </label>
                            <label>Yorum
                                <textarea name="comment" rows="3"><?= htmlspecialchars($review['comment']) ?></textarea>
                            </label>
                            <div class="button-row">
                                <button class="btn primary" type="submit">Kaydet</button>
                                <button class="btn danger" type="button" data-review-delete="<?= (int) $review['id'] ?>">Sil</button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/yorumlar?page=<?= $i ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<?php
render_footer();
?>
