<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$total = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$productsStmt = $pdo->prepare('SELECT * FROM products ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$productsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$productsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$productsStmt->execute();
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

admin_header('Ürünler');
?>
<section class="panel">
    <div class="panel-header">
        <h2>Ürünler</h2>
        <a class="btn primary" href="/admin/products.php">Ürün Ekle</a>
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
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/products-list.php?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php
admin_footer();
?>
