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
$perPage = 9;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :category_id');
$countStmt->execute(['category_id' => $category['id']]);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$productsStmt = $pdo->prepare('SELECT * FROM products WHERE category_id = :category_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$productsStmt->bindValue(':category_id', $category['id'], PDO::PARAM_INT);
$productsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$productsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$productsStmt->execute();
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

render_header($category['name']);
?>
<main class="container">
    <h1><?= htmlspecialchars($category['name']) ?></h1>
    <div class="grid">
        <?php foreach ($products as $product): ?>
            <article class="card">
                <img loading="lazy" src="<?= htmlspecialchars($product['main_image'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <div class="card-body">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p><?= htmlspecialchars($product['description']) ?></p>
                    <a class="btn" href="<?= product_url($product) ?>">Ürünü İncele</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="<?= category_url($category) ?>?page=<?= $i ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</main>
<?php
render_footer();
?>
