<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['q'] ?? '');
$perPage = 10;
$offset = ($page - 1) * $perPage;
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE (:search = "" OR name LIKE :like_search)');
$countStmt->execute([
    'search' => $search,
    'like_search' => '%' . $search . '%',
]);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$productsStmt = $pdo->prepare('SELECT * FROM products WHERE (:search = "" OR name LIKE :like_search) ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$productsStmt->bindValue(':search', $search);
$productsStmt->bindValue(':like_search', '%' . $search . '%');
$productsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$productsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$productsStmt->execute();
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

admin_header('Ürünler');
?>
<section class="panel">
    <div class="panel-header">
        <h2>Ürünler</h2>
        <div class="button-row">
            <form method="get">
                <input type="text" name="q" placeholder="Ürün ara" value="<?= htmlspecialchars($search) ?>">
                <button class="btn" type="submit">Ara</button>
            </form>
            <a class="btn primary" href="/admin/products.php">Ürün Ekle</a>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Ürün</th>
                <th>Fiyat</th>
                <th>Kanal</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= currency((float) $product['price']) ?></td>
                    <td><?= htmlspecialchars($product['order_channel']) ?></td>
                    <td>
                        <a class="btn" href="/admin/products.php?edit=<?= (int) $product['id'] ?>">Düzenle</a>
                        <button class="btn danger" data-delete-product="<?= (int) $product['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/products-list.php?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php
admin_footer();
?>
