<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$products = db()->query('SELECT * FROM products ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

admin_header('Ürün Yönetimi');
?>
<section class="panel">
    <h2>Yeni Ürün Ekle</h2>
    <form class="admin-form" data-ajax="product" enctype="multipart/form-data" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <label>Ürün Adı<input type="text" name="name" required></label>
        <label>Açıklama<textarea name="description" rows="4"></textarea></label>
        <label>Fiyat<input type="number" step="0.01" name="price" required></label>
        <label>Ürün Görseli<input type="file" name="main_image"></label>
        <label>Galeri Görselleri<input type="file" name="gallery[]" multiple></label>
        <label>Sipariş Kanalı
            <select name="order_channel">
                <option value="whatsapp">WhatsApp</option>
                <option value="paytr">PayTR</option>
            </select>
        </label>
        <label>WhatsApp / PayTR Link<input type="text" name="order_link"></label>
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
                        <button class="btn" data-edit-product="<?= (int) $product['id'] ?>">Düzenle</button>
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
