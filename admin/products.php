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
    <form id="productForm" class="admin-form" data-ajax="product" enctype="multipart/form-data" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) ($productData['id'] ?? 0) ?>">
        <label>Ürün Adı<input type="text" name="name" value="<?= htmlspecialchars($productData['name'] ?? '') ?>" required></label>
        <label>Ürün Kodu<input type="text" name="sku" value="<?= htmlspecialchars($productData['sku'] ?? '') ?>"></label>
        <label>Stok Adeti<input type="number" name="stock" min="0" value="<?= htmlspecialchars((string) ($productData['stock'] ?? 0)) ?>"></label>
        <label>Kategori
            <select name="category_id">
                <option value="">Kategori Seçin</option>
                <?php if (!empty($categoryChildren[0])): ?>
                    <?php foreach ($categoryChildren[0] as $parent): ?>
                        <option value="<?= (int) $parent['id'] ?>" <?= ($productData && (int) $productData['category_id'] === (int) $parent['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($parent['name']) ?>
                        </option>
                        <?php foreach ($categoryChildren[(int) $parent['id']] ?? [] as $child): ?>
                            <option value="<?= (int) $child['id'] ?>" <?= ($productData && (int) $productData['category_id'] === (int) $child['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($parent['name'] . ' › ' . $child['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </label>
        <label>Kart Rozeti Metni<input type="text" name="badge_text" value="<?= htmlspecialchars($productData['badge_text'] ?? '') ?>" placeholder="Örn: Ücretsiz Teslimat"></label>
        <label>Kısa Açıklama<input type="text" name="short_description" value="<?= htmlspecialchars($productData['short_description'] ?? '') ?>" placeholder="Kartlarda görünen kısa metin"></label>
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
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script>
    tinymce.init({ selector: '.tinymce', height: 240, menubar: false });
</script>
<?php
admin_footer();
?>
