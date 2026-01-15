<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$total = (int) $pdo->query('SELECT COUNT(*) FROM pages')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$pagesStmt = $pdo->prepare('SELECT * FROM pages ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$pagesStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$pagesStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$pagesStmt->execute();
$pages = $pagesStmt->fetchAll(PDO::FETCH_ASSOC);

admin_header('Sayfalar');
?>
<section class="panel">
    <div class="panel-header">
        <h2>Sayfalar</h2>
        <a class="btn primary" href="/admin/pages.php">Sayfa Ekle</a>
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
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/pages-list.php?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php
admin_footer();
?>
