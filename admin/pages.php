<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
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
        <label>Özet<textarea name="summary" rows="3"><?= htmlspecialchars($pageData['summary'] ?? '') ?></textarea></label>
        <label>İçerik<textarea class="tinymce" name="content" rows="6"><?= htmlspecialchars($pageData['content'] ?? '') ?></textarea></label>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script>
    tinymce.init({ selector: '.tinymce', height: 280, menubar: false });
</script>
<?php
admin_footer();
?>
