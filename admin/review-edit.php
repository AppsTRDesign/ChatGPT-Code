<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$reviewId = (int) ($_GET['id'] ?? 0);
if (!$reviewId) {
    header('Location: /admin/reviews.php');
    exit;
}
$stmt = db()->prepare('SELECT reviews.*, products.name AS product_name FROM reviews LEFT JOIN products ON products.id = reviews.product_id WHERE reviews.id = :id');
$stmt->execute(['id' => $reviewId]);
$review = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$review) {
    header('Location: /admin/reviews.php');
    exit;
}

admin_header('Yorum Düzenle');
?>
<section class="panel">
    <h2>Yorum Düzenle</h2>
    <form class="admin-form" data-ajax="review-admin-update" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>">
        <label>Ürün
            <input type="text" value="<?= htmlspecialchars($review['product_name'] ?: '-') ?>" disabled>
        </label>
        <label>Yorum Sahibi
            <input type="text" name="reviewer_name" value="<?= htmlspecialchars($review['reviewer_name']) ?>" required>
        </label>
        <label>Puan
            <select name="rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>" <?= (int) $review['rating'] === $i ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </label>
        <label>Yorum
            <textarea name="comment" rows="6" required><?= htmlspecialchars($review['comment']) ?></textarea>
        </label>
        <label>
            <input type="checkbox" name="approved" value="1" <?= (int) $review['approved'] === 1 ? 'checked' : '' ?>> Onaylı
        </label>
        <div class="button-row">
            <button class="btn primary" type="submit">Güncelle</button>
            <a class="btn" href="/admin/reviews.php">Listeye Dön</a>
        </div>
    </form>
</section>
<?php admin_footer(); ?>
