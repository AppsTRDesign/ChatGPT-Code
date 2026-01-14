<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
$categories = $pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
$editId = (int) ($_GET['edit'] ?? 0);
$categoryData = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $categoryData = $stmt->fetch(PDO::FETCH_ASSOC);
}

admin_header('Kategori Yönetimi');
?>
<section class="panel">
    <h2><?= $categoryData ? 'Kategori Düzenle' : 'Yeni Kategori' ?></h2>
    <form class="admin-form" data-ajax="category" enctype="multipart/form-data" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) ($categoryData['id'] ?? 0) ?>">
        <label>Kategori Adı<input type="text" name="name" value="<?= htmlspecialchars($categoryData['name'] ?? '') ?>" required></label>
        <label>Slug<input type="text" name="slug" value="<?= htmlspecialchars($categoryData['slug'] ?? '') ?>" required></label>
        <label>Üst Kategori
            <select name="parent_id">
                <option value="">Ana Kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= ($categoryData && (int) $categoryData['parent_id'] === (int) $category['id']) ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Icon URL<input type="text" name="icon" value="<?= htmlspecialchars($categoryData['icon'] ?? '') ?>"></label>
        <label>Resim<input type="file" name="image"></label>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<section class="panel">
    <h2>Kategoriler</h2>
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
</section>
<?php
admin_footer();
?>
