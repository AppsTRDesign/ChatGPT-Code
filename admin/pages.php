<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pages = db()->query('SELECT * FROM pages ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

admin_header('Dinamik Sayfalar');
?>
<section class="panel">
    <h2>Yeni Sayfa</h2>
    <form class="admin-form" data-ajax="page" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <label>Başlık<input type="text" name="title" required></label>
        <label>Slug<input type="text" name="slug" required></label>
        <label>Özet<textarea name="summary" rows="3"></textarea></label>
        <label>İçerik<textarea name="content" rows="6"></textarea></label>
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
                        <button class="btn" data-edit-page="<?= (int) $page['id'] ?>">Düzenle</button>
                        <button class="btn danger" data-delete-page="<?= (int) $page['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php
admin_footer();
?>
