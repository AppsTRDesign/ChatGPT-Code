<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$slug = $_GET['slug'] ?? '';
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = :slug');
$stmt->execute(['slug' => $slug]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);
$sectionContainerEnabled = settings('section_container_enabled', '1') === '1';
$containerClass = $sectionContainerEnabled ? 'container' : '';

if (!$category) {
    http_response_code(404);
    render_header('Kategori Bulunamadı');
    echo '<main class="' . $containerClass . '"><p>Kategori bulunamadı.</p></main>';
    render_footer();
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$sort = $_GET['sort'] ?? 'recommended';
$priceMin = (float) ($_GET['price_min'] ?? 0);
$priceMax = (float) ($_GET['price_max'] ?? 0);
$search = trim($_GET['q'] ?? '');
$layout = settings('homepage_layout', 'grid');
$listExcerptLimit = 50;
$summaryFor = static function (array $product) use ($layout, $listExcerptLimit): string {
    if ($layout === 'grid') {
        $short = trim((string) ($product['short_description'] ?? ''));
        return $short !== '' ? $short : excerpt_words((string) ($product['description'] ?? ''), 60);
    }
    return excerpt_words((string) ($product['description'] ?? ''), $listExcerptLimit);
};
$sortMap = [
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC',
    'new' => 'created_at DESC',
    'popular' => 'visit_count DESC',
    'discount' => 'discounted_price ASC',
];
$discountExpression = "CASE WHEN discount_type = 'percent' THEN price - (price * discount_value / 100) WHEN discount_type = 'amount' THEN price - discount_value ELSE price END";
$orderBy = $sortMap[$sort] ?? 'created_at DESC';
if ($orderBy === 'discounted_price ASC') {
    $orderBy = "{$discountExpression} ASC";
}
$perPage = 9;
$offset = ($page - 1) * $perPage;

$allCategories = $pdo->query('SELECT id, parent_id FROM categories')->fetchAll(PDO::FETCH_ASSOC);
$childrenMap = [];
foreach ($allCategories as $row) {
    $parentId = $row['parent_id'] !== null ? (int) $row['parent_id'] : 0;
    $childrenMap[$parentId][] = (int) $row['id'];
}
$categoryIds = [];
$categoryIdSet = [];
$queue = [(int) $category['id']];
while ($queue) {
    $currentId = array_shift($queue);
    if (isset($categoryIdSet[$currentId])) {
        continue;
    }
    $categoryIds[] = $currentId;
    $categoryIdSet[$currentId] = true;
    foreach ($childrenMap[$currentId] ?? [] as $childId) {
        $queue[] = $childId;
    }
}
$categoryPlaceholders = implode(',', array_fill(0, count($categoryIds), '?'));

$maxStmt = $pdo->prepare("SELECT MAX(price) FROM products WHERE category_id IN ({$categoryPlaceholders})");
$maxStmt->execute($categoryIds);
$maxPriceValue = (float) $maxStmt->fetchColumn();
$maxPrice = $maxPriceValue > 0 ? (int) ceil($maxPriceValue) : 1;

$priceMax = $priceMax > 0 ? $priceMax : $maxPrice;
$priceMin = max(0, min($priceMin, $priceMax));

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id IN ({$categoryPlaceholders}) AND (? = 0 OR price >= ?) AND (? = 0 OR price <= ?) AND (? = '' OR name LIKE ?)");
$countStmt->execute(array_merge(
    $categoryIds,
    [$priceMin, $priceMin, $priceMax, $priceMax, $search, '%' . $search . '%']
));
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$productsStmt = $pdo->prepare("SELECT * FROM products WHERE category_id IN ({$categoryPlaceholders}) AND (? = 0 OR price >= ?) AND (? = 0 OR price <= ?) AND (? = '' OR name LIKE ?) ORDER BY {$orderBy} LIMIT ? OFFSET ?");
foreach ($categoryIds as $index => $categoryId) {
    $productsStmt->bindValue($index + 1, $categoryId, PDO::PARAM_INT);
}
$priceMinIndex = count($categoryIds) + 1;
$productsStmt->bindValue($priceMinIndex, $priceMin);
$productsStmt->bindValue($priceMinIndex + 1, $priceMin);
$productsStmt->bindValue($priceMinIndex + 2, $priceMax);
$productsStmt->bindValue($priceMinIndex + 3, $priceMax);
$productsStmt->bindValue($priceMinIndex + 4, $search);
$productsStmt->bindValue($priceMinIndex + 5, '%' . $search . '%');
$productsStmt->bindValue($priceMinIndex + 6, $perPage, PDO::PARAM_INT);
$productsStmt->bindValue($priceMinIndex + 7, $offset, PDO::PARAM_INT);
$productsStmt->execute();
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
$categoryDescription = excerpt_words($category['description'] ?? '', 160);
$metaImage = $category['image'] ? absolute_url($category['image']) : '';
$childStmt = $pdo->prepare('SELECT categories.*, COUNT(products.id) AS product_count FROM categories LEFT JOIN products ON products.category_id = categories.id WHERE categories.parent_id = :parent_id GROUP BY categories.id ORDER BY categories.name ASC');
$childStmt->execute(['parent_id' => $category['id']]);
$childCategories = $childStmt->fetchAll(PDO::FETCH_ASSOC);
$user = current_user();
$favoriteMap = [];
if ($user && $products) {
    $productIds = array_column($products, 'id');
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $favStmt = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = ? AND product_id IN ({$placeholders})");
    $favStmt->execute(array_merge([$user['id']], $productIds));
    $favoriteMap = array_fill_keys($favStmt->fetchAll(PDO::FETCH_COLUMN), true);
}

render_header($category['name'], [
    'title' => $category['name'],
    'description' => $categoryDescription,
    'image' => $metaImage,
]);
?>
<main class="<?= $containerClass ?>">
    <div class="category-header">
        <?php if (!empty($category['image'])): ?>
            <img loading="lazy" src="<?= htmlspecialchars($category['image']) ?>" alt="<?= htmlspecialchars($category['name']) ?>" title="<?= htmlspecialchars($category['name']) ?>">
        <?php elseif (!empty($category['icon'])): ?>
            <span class="category-icon"><i class="<?= htmlspecialchars($category['icon']) ?>"></i></span>
        <?php endif; ?>
        <h1><?= htmlspecialchars($category['name']) ?></h1>
    </div>
    <?php if (!empty($category['description'])): ?>
        <div class="category-description">
            <?= nl2br(htmlspecialchars($category['description'])) ?>
        </div>
    <?php endif; ?>
    <?php if ($childCategories): ?>
        <div class="subcategory-slider splide" id="subcategorySlider">
            <div class="splide__track">
                <ul class="splide__list">
                    <?php foreach ($childCategories as $child): ?>
                        <li class="splide__slide">
                            <a class="subcategory-card" href="<?= category_url($child) ?>" title="<?= htmlspecialchars($child['name']) ?>">
                                <?php if (!empty($child['image'])): ?>
                                    <img loading="lazy" src="<?= htmlspecialchars($child['image']) ?>" alt="<?= htmlspecialchars($child['name']) ?>" title="<?= htmlspecialchars($child['name']) ?>">
                                <?php elseif (!empty($child['icon'])): ?>
                                    <span class="category-icon"><i class="<?= htmlspecialchars($child['icon']) ?>"></i></span>
                                <?php else: ?>
                                    <span class="category-icon"><i class="fa-regular fa-circle"></i></span>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($child['name']) ?></span>
                                <small class="category-count"><?= (int) $child['product_count'] ?> ürün</small>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
    <form class="filter-bar" method="get">
        <input type="hidden" name="slug" value="<?= htmlspecialchars($category['slug']) ?>">
        <div class="filter-row">
            <input class="filter-search" type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Bu kategoride ürün ara">
            <select name="sort">
                <option value="recommended" <?= $sort === 'recommended' ? 'selected' : '' ?>>Önerilen</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Ucuzdan Pahalıya</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Pahalıdan Ucuza</option>
                <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>En Yeni</option>
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>En Popüler</option>
                <option value="discount" <?= $sort === 'discount' ? 'selected' : '' ?>>İndirimli Ürünler</option>
            </select>
            <div class="price-range" data-price-range data-max="<?= $maxPrice ?>">
                <div class="price-field">
                    <label>Min ₺: <span data-range-value="min"><?= htmlspecialchars((string) $priceMin) ?></span></label>
                    <input type="range" min="0" max="<?= $maxPrice ?>" value="<?= htmlspecialchars((string) $priceMin) ?>" data-range="min">
                </div>
                <div class="price-field">
                    <label>Max ₺: <span data-range-value="max"><?= htmlspecialchars((string) $priceMax) ?></span></label>
                    <input type="range" min="0" max="<?= $maxPrice ?>" value="<?= htmlspecialchars((string) $priceMax) ?>" data-range="max">
                </div>
                <input type="hidden" name="price_min" value="<?= htmlspecialchars((string) $priceMin) ?>">
                <input type="hidden" name="price_max" value="<?= htmlspecialchars((string) $priceMax) ?>">
            </div>
            <button class="btn" type="submit">Uygula</button>
        </div>
    </form>
    <div class="grid">
        <?php foreach ($products as $product): ?>
            <?php
            $isFavorited = isset($favoriteMap[$product['id']]);
            $hasDiscount = product_has_discount($product);
            $finalPrice = product_discounted_price($product);
            ?>
            <article class="card">
                <div class="card-media">
                    <img class="product-image" loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" title="<?= htmlspecialchars($product['name']) ?>">
                    <?php if (!empty($product['badge_text'])): ?>
                        <span class="card-badge"><?= htmlspecialchars($product['badge_text']) ?></span>
                    <?php endif; ?>
                    <?php if ($hasDiscount): ?>
                        <span class="card-badge discount-badge">İndirimli</span>
                    <?php endif; ?>
                    <?php if ($user): ?>
                        <button
                            class="card-fav<?= $isFavorited ? ' is-active' : '' ?>"
                            type="button"
                            data-favorite="<?= (int) $product['id'] ?>"
                            data-favorite-filled="♥"
                            data-favorite-empty="♡"
                            aria-pressed="<?= $isFavorited ? 'true' : 'false' ?>"
                        >
                            <?= $isFavorited ? '♥' : '♡' ?>
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p><?= htmlspecialchars($summaryFor($product)) ?></p>
                    <p class="price">
                        <?php if ($hasDiscount): ?>
                            <span class="price-old"><?= currency((float) $product['price']) ?></span>
                        <?php endif; ?>
                        <span class="price-new"><?= currency($finalPrice) ?></span>
                    </p>
                    <a class="btn" href="<?= product_url($product) ?>" title="<?= htmlspecialchars($product['name']) ?>">Ürünü İncele</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="pagination">
        <?php
        $pages = array_unique(array_filter([
            1,
            2,
            $totalPages,
            $totalPages - 1,
            $page - 1,
            $page,
            $page + 1,
        ], static fn($value) => $value >= 1 && $value <= $totalPages));
        sort($pages);
        $lastPage = 0;
        foreach ($pages as $pageNumber) {
            if ($pageNumber - $lastPage > 1) {
                echo '<span class="pagination-ellipsis">…</span>';
            }
            $active = $pageNumber === $page ? 'primary' : '';
            $url = category_url($category) . '?page=' . $pageNumber
                . '&sort=' . urlencode($sort)
                . '&price_min=' . urlencode((string) $priceMin)
                . '&price_max=' . urlencode((string) $priceMax)
                . '&q=' . urlencode($search);
            echo '<a class="btn ' . $active . '" href="' . $url . '">' . $pageNumber . '</a>';
            $lastPage = $pageNumber;
        }
        ?>
    </div>
</main>
<script type="application/ld+json">
<?php
$itemList = [];
foreach ($products as $index => $product) {
    $itemList[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'name' => $product['name'],
        'url' => base_url('urun/' . $product['slug']),
        'image' => absolute_url($product['main_image'] ?: '/assets/images/placeholder.svg'),
    ];
}
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $category['name'],
    'description' => $categoryDescription,
    'url' => base_url('kategori/' . $category['slug']),
    'image' => $metaImage,
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListElement' => $itemList,
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
</script>
<?php
render_footer();
?>
