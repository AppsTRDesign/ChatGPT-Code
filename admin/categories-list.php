<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['q'] ?? '');
$perPage = 10;
$offset = ($page - 1) * $perPage;
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE (:search = "" OR name LIKE :like_search)');
$countStmt->execute([
    'search' => $search,
    'like_search' => '%' . $search . '%',
]);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$categoriesStmt = $pdo->prepare('SELECT * FROM categories WHERE (:search = "" OR name LIKE :like_search) ORDER BY name ASC LIMIT :limit OFFSET :offset');
$categoriesStmt->bindValue(':search', $search);
$categoriesStmt->bindValue(':like_search', '%' . $search . '%');
$categoriesStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$categoriesStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

admin_header('Kategoriler');
?>
<section class="panel">
    <div class="panel-header">
        <h2>Kategoriler</h2>
        <div class="button-row">
            <form method="get">
                <input type="text" name="q" placeholder="Kategori ara" value="<?= htmlspecialchars($search) ?>">
                <button class="btn" type="submit">Ara</button>
            </form>
            <a class="btn primary" href="/admin/categories.php">Kategori Ekle</a>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Kategori</th>
                <th>Slug</th>
                <th>Üst Kategori</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= htmlspecialchars($category['name']) ?></td>
                    <td><?= htmlspecialchars($category['slug']) ?></td>
                    <td><?= htmlspecialchars($category['parent_id'] ?: '-') ?></td>
                    <td>
                        <a class="btn" href="/admin/categories.php?edit=<?= (int) $category['id'] ?>">Düzenle</a>
                        <button class="btn danger" data-delete-category="<?= (int) $category['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/categories-list.php?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php
admin_footer();
?>
