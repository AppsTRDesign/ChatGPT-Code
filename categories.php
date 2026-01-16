<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$categories = db()->query('SELECT categories.*, COUNT(products.id) AS product_count FROM categories LEFT JOIN products ON products.category_id = categories.id WHERE categories.parent_id IS NOT NULL GROUP BY categories.id ORDER BY categories.name ASC')->fetchAll(PDO::FETCH_ASSOC);
if (!$categories) {
    $categories = db()->query('SELECT categories.*, COUNT(products.id) AS product_count FROM categories LEFT JOIN products ON products.category_id = categories.id GROUP BY categories.id ORDER BY categories.name ASC')->fetchAll(PDO::FETCH_ASSOC);
}
$sectionContainerEnabled = settings('section_container_enabled', '1') === '1';
$containerClass = $sectionContainerEnabled ? 'container' : '';

$description = 'Tüm kategorileri keşfedin ve ürün sayılarını inceleyin.';
render_header('Kategoriler', [
    'title' => 'Kategoriler',
    'description' => $description,
]);
?>
<main class="<?= $containerClass ?>">
    <h1>Kategoriler</h1>
    <div class="category-grid">
        <?php foreach ($categories as $category): ?>
                <a class="category-card" href="<?= category_url($category) ?>" title="<?= htmlspecialchars($category['name']) ?>">
                <?php if (!empty($category['image'])): ?>
                    <img loading="lazy" src="<?= htmlspecialchars($category['image']) ?>" alt="<?= htmlspecialchars($category['name']) ?>" title="<?= htmlspecialchars($category['name']) ?>">
                <?php elseif (!empty($category['icon'])): ?>
                    <span class="category-icon"><i class="<?= htmlspecialchars($category['icon']) ?>"></i></span>
                <?php else: ?>
                    <span class="category-icon"><i class="fa-regular fa-circle"></i></span>
                <?php endif; ?>
                <h3><?= htmlspecialchars($category['name']) ?></h3>
                <span class="category-count"><?= (int) $category['product_count'] ?> ürün</span>
            </a>
        <?php endforeach; ?>
    </div>
</main>
<?php
render_footer();
?>
