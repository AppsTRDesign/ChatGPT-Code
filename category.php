<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$slug = $_GET['slug'] ?? '';
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = :slug');
$stmt->execute(['slug' => $slug]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    http_response_code(404);
    render_header('Kategori Bulunamadı');
    echo '<main class="container"><p>Kategori bulunamadı.</p></main>';
    render_footer();
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$sort = $_GET['sort'] ?? 'recommended';
$priceMin = (float) ($_GET['price_min'] ?? 0);
$priceMax = (float) ($_GET['price_max'] ?? 0);
$sortMap = [
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC',
    'new' => 'created_at DESC',
    'popular' => 'visit_count DESC',
];
$orderBy = $sortMap[$sort] ?? 'created_at DESC';
$perPage = 9;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :category_id AND (:price_min = 0 OR price >= :price_min) AND (:price_max = 0 OR price <= :price_max)');
$countStmt->execute([
    'category_id' => $category['id'],
    'price_min' => $priceMin,
    'price_max' => $priceMax,
]);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$productsStmt = $pdo->prepare("SELECT * FROM products WHERE category_id = :category_id AND (:price_min = 0 OR price >= :price_min) AND (:price_max = 0 OR price <= :price_max) ORDER BY {$orderBy} LIMIT :limit OFFSET :offset");
$productsStmt->bindValue(':category_id', $category['id'], PDO::PARAM_INT);
$productsStmt->bindValue(':price_min', $priceMin);
$productsStmt->bindValue(':price_max', $priceMax);
$productsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$productsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$productsStmt->execute();
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
$categoryDescription = excerpt_words($category['description'] ?? '', 160);
$metaImage = $category['image'] ? absolute_url($category['image']) : '';
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
<main class="container">
    <div class="category-header">
        <?php if (!empty($category['image'])): ?>
            <img loading="lazy" src="<?= htmlspecialchars($category['image']) ?>" alt="<?= htmlspecialchars($category['name']) ?>">
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
    <form class="filter-bar" method="get">
        <input type="hidden" name="slug" value="<?= htmlspecialchars($category['slug']) ?>">
        <div class="filter-row">
            <select name="sort">
                <option value="recommended" <?= $sort === 'recommended' ? 'selected' : '' ?>>Önerilen</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Ucuzdan Pahalıya</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Pahalıdan Ucuza</option>
                <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>En Yeni</option>
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>En Popüler</option>
            </select>
            <input type="number" name="price_min" placeholder="Min ₺" value="<?= htmlspecialchars((string) $priceMin) ?>">
            <input type="number" name="price_max" placeholder="Max ₺" value="<?= htmlspecialchars((string) $priceMax) ?>">
            <button class="btn" type="submit">Uygula</button>
        </div>
    </form>
    <div class="grid">
        <?php foreach ($products as $product): ?>
            <?php $isFavorited = isset($favoriteMap[$product['id']]); ?>
            <article class="card">
                <div class="card-media">
                    <img class="product-image" loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <span class="card-badge">Ücretsiz Teslimat</span>
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
                    <p><?= htmlspecialchars(excerpt_words($product['description'], 120)) ?></p>
                    <p class="price"><?= currency((float) $product['price']) ?></p>
                    <a class="btn" href="<?= product_url($product) ?>">Ürünü İncele</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="<?= category_url($category) ?>?page=<?= $i ?>&sort=<?= urlencode($sort) ?>&price_min=<?= urlencode((string) $priceMin) ?>&price_max=<?= urlencode((string) $priceMax) ?>"><?= $i ?></a>
        <?php endfor; ?>
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
