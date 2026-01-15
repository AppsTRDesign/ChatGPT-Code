<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
$products = $pdo->query('SELECT * FROM products ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
$editId = (int) ($_GET['edit'] ?? 0);
$productData = null;
$featureText = '';
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $productData = $stmt->fetch(PDO::FETCH_ASSOC);
    $featureStmt = $pdo->prepare('SELECT feature_name, feature_value FROM product_features WHERE product_id = :product_id');
    $featureStmt->execute(['product_id' => $editId]);
    $features = $featureStmt->fetchAll(PDO::FETCH_ASSOC);
    $featureText = implode("\n", array_map(
        fn($feature) => $feature['feature_name'] . ': ' . $feature['feature_value'],
        $features
    ));
}

admin_header('Ürün Yönetimi');
?>
<section class="panel">
    <h2><?= $productData ? 'Ürün Düzenle' : 'Yeni Ürün Ekle' ?></h2>
    <form class="admin-form" data-ajax="product" enctype="multipart/form-data" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) ($productData['id'] ?? 0) ?>">
        <label>Ürün Adı<input type="text" name="name" value="<?= htmlspecialchars($productData['name'] ?? '') ?>" required></label>
        <label>Slug<input type="text" name="slug" value="<?= htmlspecialchars($productData['slug'] ?? '') ?>"></label>
        <label>Kategori
            <select name="category_id">
                <option value="">Kategori Seçin</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= ($productData && (int) $productData['category_id'] === (int) $category['id']) ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Açıklama<textarea class="tinymce" name="description" rows="4"><?= htmlspecialchars($productData['description'] ?? '') ?></textarea></label>
        <label>Fiyat<input type="number" step="0.01" name="price" value="<?= htmlspecialchars($productData['price'] ?? '') ?>" required></label>
        <label>Ürün Görseli<input type="file" name="main_image"></label>
        <label>Galeri Görselleri<input type="file" name="gallery[]" multiple></label>
        <label>Özellikler (Ör: Renk: Kırmızı)<textarea name="features" rows="4"><?= htmlspecialchars($featureText) ?></textarea></label>
        <label>Sipariş Kanalı
            <select name="order_channel">
                <option value="whatsapp" <?= ($productData && $productData['order_channel'] === 'whatsapp') ? 'selected' : '' ?>>WhatsApp</option>
                <option value="paytr" <?= ($productData && $productData['order_channel'] === 'paytr') ? 'selected' : '' ?>>PayTR</option>
            </select>
        </label>
        <label>WhatsApp Link<input type="text" name="order_link" value="<?= htmlspecialchars($productData['order_link'] ?? '') ?>"></label>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<section class="panel">
    <h2>Ürünler</h2>
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
</section>
<?php
admin_footer();
?>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script>
    tinymce.init({ selector: '.tinymce', height: 240, menubar: false });
</script>
