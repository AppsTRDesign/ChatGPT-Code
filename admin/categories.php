<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
$categories = $pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
$categoryMap = [];
$categoryChildren = [];
foreach ($categories as $category) {
    $categoryMap[$category['id']] = $category;
    $parentId = $category['parent_id'] ? (int) $category['parent_id'] : 0;
    $categoryChildren[$parentId][] = $category;
}
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
                <?php if (!empty($categoryChildren[0])): ?>
                    <?php foreach ($categoryChildren[0] as $parent): ?>
                        <?php if ($categoryData && (int) $categoryData['id'] === (int) $parent['id']) { continue; } ?>
                        <option value="<?= (int) $parent['id'] ?>" <?= ($categoryData && (int) $categoryData['parent_id'] === (int) $parent['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($parent['name']) ?>
                        </option>
                        <?php foreach ($categoryChildren[(int) $parent['id']] ?? [] as $child): ?>
                            <?php if ($categoryData && (int) $categoryData['id'] === (int) $child['id']) { continue; } ?>
                            <option value="<?= (int) $child['id'] ?>" <?= ($categoryData && (int) $categoryData['parent_id'] === (int) $child['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($parent['name'] . ' › ' . $child['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
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
        <input type="text" class="icon-search" id="iconSearchInput" placeholder="Icon ara...">
        <div class="icon-grid">
            <?php
            $featuredIcons = [
                'fa-solid fa-seedling',
                'fa-solid fa-leaf',
                'fa-solid fa-tree',
                'fa-solid fa-spa',
                'fa-solid fa-plant-wilt',
                'fa-solid fa-heart',
                'fa-solid fa-gift',
                'fa-solid fa-cake-candles',
                'fa-solid fa-champagne-glasses',
                'fa-solid fa-ring',
                'fa-solid fa-hand-holding-heart',
                'fa-solid fa-basket-shopping',
                'fa-solid fa-bag-shopping',
                'fa-solid fa-truck-fast',
                'fa-solid fa-location-dot',
                'fa-solid fa-star',
                'fa-solid fa-wand-magic-sparkles',
                'fa-solid fa-apple-whole',
                'fa-solid fa-lemon',
                'fa-solid fa-mug-hot',
                'fa-solid fa-sun',
                'fa-solid fa-cloud',
                'fa-solid fa-umbrella',
                'fa-solid fa-heart-circle-bolt',
            ];
            $icons = $featuredIcons;
            $iconData = @file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/metadata/icons.json');
            if ($iconData) {
                $decoded = json_decode($iconData, true);
                if (is_array($decoded)) {
                    $allIcons = [];
                    foreach ($decoded as $name => $meta) {
                        $styles = $meta['styles'] ?? [];
                        if (in_array('solid', $styles, true)) {
                            $allIcons[] = 'fa-solid fa-' . $name;
                            continue;
                        }
                        if (in_array('regular', $styles, true)) {
                            $allIcons[] = 'fa-regular fa-' . $name;
                            continue;
                        }
                        if (in_array('brands', $styles, true)) {
                            $allIcons[] = 'fa-brands fa-' . $name;
                        }
                    }
                    sort($allIcons);
                    $icons = array_values(array_unique(array_merge($featuredIcons, $allIcons)));
                }
            }
            foreach ($icons as $iconClass):
            ?>
                <button class="icon-option" type="button" data-icon-value="<?= htmlspecialchars($iconClass) ?>" data-icon-name="<?= htmlspecialchars($iconClass) ?>">
                    <i class="<?= htmlspecialchars($iconClass) ?>"></i>
                    <span><?= htmlspecialchars($iconClass) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php
admin_footer();
?>
