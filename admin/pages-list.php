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
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE (:search = "" OR title LIKE :like_search)');
$countStmt->execute([
    'search' => $search,
    'like_search' => '%' . $search . '%',
]);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$pagesStmt = $pdo->prepare('SELECT * FROM pages WHERE (:search = "" OR title LIKE :like_search) ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$pagesStmt->bindValue(':search', $search);
$pagesStmt->bindValue(':like_search', '%' . $search . '%');
$pagesStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$pagesStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$pagesStmt->execute();
$pages = $pagesStmt->fetchAll(PDO::FETCH_ASSOC);

admin_header('Sayfalar');
?>
<section class="panel">
    <div class="panel-header">
        <h2>Sayfalar</h2>
        <div class="button-row">
            <form method="get">
                <input type="text" name="q" placeholder="Sayfa ara" value="<?= htmlspecialchars($search) ?>">
                <button class="btn" type="submit">Ara</button>
            </form>
            <a class="btn primary" href="/admin/pages.php">Sayfa Ekle</a>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Başlık</th>
                <th>Slug</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pages as $page): ?>
                <tr>
                    <td><?= htmlspecialchars($page['title']) ?></td>
                    <td><?= htmlspecialchars($page['slug']) ?></td>
                    <td>
                        <a class="btn" href="/admin/pages.php?edit=<?= (int) $page['id'] ?>">Düzenle</a>
                        <button class="btn danger" data-delete-page="<?= (int) $page['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/pages-list.php?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php
admin_footer();
?>
