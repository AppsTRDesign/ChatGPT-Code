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
                'fa-solid fa-mug-hot',
                'fa-solid fa-ice-cream',
                'fa-solid fa-cookie',
                'fa-solid fa-apple-whole',
                'fa-solid fa-lemon',
                'fa-solid fa-pepper-hot',
                'fa-solid fa-bell',
                'fa-solid fa-bolt',
                'fa-solid fa-fire',
                'fa-solid fa-snowflake',
                'fa-solid fa-cloud',
                'fa-solid fa-cloud-sun',
                'fa-solid fa-cloud-moon',
                'fa-solid fa-moon',
                'fa-solid fa-star-of-life',
                'fa-solid fa-crown',
                'fa-solid fa-rocket',
                'fa-solid fa-globe',
                'fa-solid fa-location-dot',
                'fa-solid fa-tree',
                'fa-solid fa-plant-wilt',
                'fa-solid fa-feather',
                'fa-solid fa-paw',
                'fa-solid fa-fish',
                'fa-solid fa-bug',
                'fa-solid fa-dragon',
                'fa-solid fa-car',
                'fa-solid fa-truck-fast',
                'fa-solid fa-truck',
                'fa-solid fa-bicycle',
                'fa-solid fa-plane',
                'fa-solid fa-ship',
                'fa-solid fa-bus',
                'fa-solid fa-house',
                'fa-solid fa-building',
                'fa-solid fa-shop',
                'fa-solid fa-store',
                'fa-solid fa-shirt',
                'fa-solid fa-shoe-prints',
                'fa-solid fa-hat-cowboy',
                'fa-solid fa-glasses',
                'fa-solid fa-music',
                'fa-solid fa-guitar',
                'fa-solid fa-headphones',
                'fa-solid fa-camera',
                'fa-solid fa-image',
                'fa-solid fa-film',
                'fa-solid fa-book',
                'fa-solid fa-book-open',
                'fa-solid fa-pen',
                'fa-solid fa-pencil',
                'fa-solid fa-paintbrush',
                'fa-solid fa-palette',
                'fa-solid fa-microphone',
                'fa-solid fa-gamepad',
                'fa-solid fa-dice',
                'fa-solid fa-dice-d20',
                'fa-solid fa-football',
                'fa-solid fa-basketball',
                'fa-solid fa-volleyball',
                'fa-solid fa-baseball',
                'fa-solid fa-medal',
                'fa-solid fa-trophy',
                'fa-solid fa-gem',
                'fa-solid fa-ring',
                'fa-solid fa-user',
                'fa-solid fa-user-group',
                'fa-solid fa-user-tie',
                'fa-solid fa-people-group',
                'fa-solid fa-briefcase',
                'fa-solid fa-calendar',
                'fa-solid fa-calendar-days',
                'fa-solid fa-clock',
                'fa-solid fa-envelope',
                'fa-solid fa-comment',
                'fa-solid fa-comments',
                'fa-solid fa-phone',
                'fa-solid fa-mobile-screen',
                'fa-solid fa-lock',
                'fa-solid fa-key',
                'fa-solid fa-shield',
                'fa-solid fa-circle-check',
                'fa-solid fa-circle-xmark',
                'fa-solid fa-triangle-exclamation',
                'fa-solid fa-magnifying-glass',
                'fa-solid fa-filter',
                'fa-solid fa-sliders',
                'fa-solid fa-tag',
                'fa-solid fa-tags',
                'fa-solid fa-percent',
                'fa-solid fa-gift',
                'fa-solid fa-bag-shopping',
                'fa-solid fa-cart-shopping',
                'fa-solid fa-wallet',
                'fa-solid fa-credit-card',
                'fa-solid fa-receipt',
                'fa-solid fa-money-bill-wave',
                'fa-solid fa-hand-holding-heart',
                'fa-solid fa-handshake',
                'fa-solid fa-heart-circle-bolt',
            ];
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
