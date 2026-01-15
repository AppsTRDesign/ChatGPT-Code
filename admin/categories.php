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
        <label>Kategori Açıklaması<textarea name="description" rows="4"><?= htmlspecialchars($categoryData['description'] ?? '') ?></textarea></label>
        <label>Üst Kategori
            <select name="parent_id">
                <option value="">Ana Kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= ($categoryData && (int) $categoryData['parent_id'] === (int) $category['id']) ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="icon-picker">
            <input type="hidden" name="icon" id="categoryIconInput" value="<?= htmlspecialchars($categoryData['icon'] ?? '') ?>">
            <div class="icon-preview" id="categoryIconPreview">
                <?php if (!empty($categoryData['icon'])): ?>
                    <i class="<?= htmlspecialchars($categoryData['icon']) ?>"></i>
                <?php else: ?>
                    <i class="fa-regular fa-circle"></i>
                <?php endif; ?>
            </div>
            <button class="btn" type="button" data-icon-picker>Icon Seç</button>
        </div>
        <label>Resim<input type="file" name="image"></label>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<div class="admin-modal" id="iconPickerModal" aria-hidden="true">
    <div class="admin-modal-content">
        <div class="panel-header">
            <h3>Icon Seç</h3>
            <button class="btn" type="button" data-icon-close>Kapat</button>
        </div>
        <div class="icon-grid">
            <?php
            $icons = [
                'fa-solid fa-heart',
                'fa-solid fa-gift',
                'fa-solid fa-leaf',
                'fa-solid fa-star',
                'fa-solid fa-basket-shopping',
                'fa-solid fa-cake-candles',
                'fa-solid fa-wand-magic-sparkles',
                'fa-solid fa-champagne-glasses',
                'fa-solid fa-seedling',
                'fa-solid fa-palette',
                'fa-solid fa-wine-glass',
                'fa-solid fa-sun',
            ];
            foreach ($icons as $iconClass):
            ?>
                <button class="icon-option" type="button" data-icon-value="<?= htmlspecialchars($iconClass) ?>">
                    <i class="<?= htmlspecialchars($iconClass) ?>"></i>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php
admin_footer();
?>
