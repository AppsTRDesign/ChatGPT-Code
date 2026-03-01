<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;
$total = (int) db()->query('SELECT COUNT(*) FROM reviews')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$stmt = db()->prepare('SELECT reviews.*, products.name AS product_name FROM reviews LEFT JOIN products ON products.id = reviews.product_id ORDER BY reviews.created_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_header('Yorum Onayı');
?>
<section class="panel">
    <h2>Yorumlar</h2>
    <table>
        <thead>
            <tr>
                <th>Ürün</th>
                <th>Kullanıcı</th>
                <th>Puan</th>
                <th>Yorum</th>
                <th>Durum</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reviews as $review): ?>
                <tr>
                    <td><?= htmlspecialchars($review['product_name'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($review['reviewer_name']) ?></td>
                    <td><?= (int) $review['rating'] ?>/5</td>
                    <td><?= htmlspecialchars(mb_strimwidth((string) $review['comment'], 0, 120, '...')) ?></td>
                    <td>
                        <span class="badge <?= (int) $review['approved'] === 1 ? 'badge-approved' : 'badge-pending' ?>">
                            <?= (int) $review['approved'] === 1 ? 'Onaylı' : 'Bekliyor' ?>
                        </span>
                    </td>
                    <td>
                        <a class="btn" href="/admin/review-edit.php?id=<?= (int) $review['id'] ?>">Düzenle</a>
                        <?php if ((int) $review['approved'] !== 1): ?>
                            <button class="btn primary" data-review-approve="<?= (int) $review['id'] ?>">Onayla</button>
                        <?php endif; ?>
                        <button class="btn danger" data-review-delete-admin="<?= (int) $review['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/reviews.php?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>
