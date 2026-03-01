<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$editId = (int) ($_GET['edit'] ?? 0);
$shipperData = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM shippers WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $shipperData = $stmt->fetch(PDO::FETCH_ASSOC);
}
$shippers = db()->query('SELECT * FROM shippers ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

admin_header('Kargo Firmaları');
?>
<section class="panel">
    <h2><?= $shipperData ? 'Kargo Düzenle' : 'Kargo Ekle' ?></h2>
    <form class="admin-form" data-ajax="shipper" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) ($shipperData['id'] ?? 0) ?>">
        <label>Firma Adı<input type="text" name="name" value="<?= htmlspecialchars($shipperData['name'] ?? '') ?>" required></label>
        <label>Adres<textarea name="address" rows="2"><?= htmlspecialchars($shipperData['address'] ?? '') ?></textarea></label>
        <label>Firma Web Sitesi<input type="url" name="website" value="<?= htmlspecialchars($shipperData['website'] ?? '') ?>"></label>
        <label>Firmada Sorgu Linki<input type="text" name="query_url" placeholder="https://cargo-firma.com/track?code=" value="<?= htmlspecialchars($shipperData['query_url'] ?? '') ?>"></label>
        <label>API Linki (tracking_number placeholder ile)<input type="text" name="api_url" placeholder="https://cargoafrik.org/api/tracking_json.php?tracking_number={tracking_number}&lang={lang}" value="<?= htmlspecialchars($shipperData['api_url'] ?? 'https://cargoafrik.org/api/tracking_json.php?tracking_number={tracking_number}&lang={lang}') ?>"></label>
        <label>Logo<input type="file" name="logo" accept="image/*"></label>
        <?php if (!empty($shipperData['logo'])): ?>
            <div class="media-preview" data-remove-on-delete>
                <img src="<?= htmlspecialchars($shipperData['logo']) ?>" alt="Logo" style="max-width:120px">
                <button class="btn danger" type="button" data-delete-shipper-logo="<?= (int) $shipperData['id'] ?>">Logo Sil</button>
            </div>
        <?php endif; ?>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<section class="panel">
    <h2>Kargo Firmaları</h2>
    <table>
        <thead><tr><th>Logo</th><th>Adı</th><th>Web</th><th>Firmada Sorgu</th><th>API</th><th>İşlem</th></tr></thead>
        <tbody>
            <?php foreach ($shippers as $shipper): ?>
                <tr>
                    <td><?php if (!empty($shipper['logo'])): ?><img src="<?= htmlspecialchars($shipper['logo']) ?>" alt="<?= htmlspecialchars($shipper['name']) ?>" style="max-width:70px"><?php endif; ?></td>
                    <td><?= htmlspecialchars($shipper['name']) ?></td>
                    <td><?= htmlspecialchars($shipper['website']) ?></td>
                    <td><small><?= htmlspecialchars($shipper['query_url'] ?? '') ?></small></td>
                    <td><small><?= htmlspecialchars($shipper['api_url']) ?></small></td>
                    <td>
                        <a class="btn" href="/admin/shippers.php?edit=<?= (int) $shipper['id'] ?>">Düzenle</a>
                        <button class="btn danger" data-delete-shipper="<?= (int) $shipper['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php admin_footer(); ?>
