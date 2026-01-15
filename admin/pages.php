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
$editId = (int) ($_GET['edit'] ?? 0);
$pageData = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM pages WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $pageData = $stmt->fetch(PDO::FETCH_ASSOC);
}

admin_header('Dinamik Sayfalar');
?>
<section class="panel">
    <h2><?= $pageData ? 'Sayfa Düzenle' : 'Yeni Sayfa' ?></h2>
    <form class="admin-form" data-ajax="page" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) ($pageData['id'] ?? 0) ?>">
        <label>Başlık<input type="text" name="title" value="<?= htmlspecialchars($pageData['title'] ?? '') ?>" required></label>
        <label>Slug<input type="text" name="slug" value="<?= htmlspecialchars($pageData['slug'] ?? '') ?>" required></label>
        <label>Özet<textarea name="summary" rows="3"><?= htmlspecialchars($pageData['summary'] ?? '') ?></textarea></label>
        <label>İçerik<textarea class="tinymce" name="content" rows="6"><?= htmlspecialchars($pageData['content'] ?? '') ?></textarea></label>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<section class="panel">
    <h2>Sayfalar</h2>
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
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/pages.php?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script>
    tinymce.init({ selector: '.tinymce', height: 280, menubar: false });
</script>
<?php
admin_footer();
?>
