<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$total = (int) $pdo->query('SELECT COUNT(*) FROM sliders')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$slidersStmt = $pdo->prepare('SELECT * FROM sliders ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$slidersStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$slidersStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$slidersStmt->execute();
$sliders = $slidersStmt->fetchAll(PDO::FETCH_ASSOC);
$editId = (int) ($_GET['edit'] ?? 0);
$sliderData = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM sliders WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $sliderData = $stmt->fetch(PDO::FETCH_ASSOC);
}

admin_header('Slider Yönetimi');
?>
<section class="panel">
    <h2><?= $sliderData ? 'Slider Düzenle' : 'Yeni Slider' ?></h2>
    <form class="admin-form" data-ajax="slider" enctype="multipart/form-data" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) ($sliderData['id'] ?? 0) ?>">
        <label>Başlık<input type="text" name="title" value="<?= htmlspecialchars($sliderData['title'] ?? '') ?>" required></label>
        <label>Açıklama<textarea name="description" rows="3"><?= htmlspecialchars($sliderData['description'] ?? '') ?></textarea></label>
        <label>Buton Metni<input type="text" name="button_text" value="<?= htmlspecialchars($sliderData['button_text'] ?? '') ?>"></label>
        <label>Buton Linki<input type="text" name="button_url" value="<?= htmlspecialchars($sliderData['button_url'] ?? '') ?>"></label>
        <label>Slider Görseli<input type="file" name="image"></label>
        <label>Aktif
            <select name="is_active">
                <option value="1" <?= ($sliderData && (int) $sliderData['is_active'] === 1) ? 'selected' : '' ?>>Evet</option>
                <option value="0" <?= ($sliderData && (int) $sliderData['is_active'] === 0) ? 'selected' : '' ?>>Hayır</option>
            </select>
        </label>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<section class="panel">
    <h2>Sliderlar</h2>
    <table>
        <thead>
            <tr>
                <th>Başlık</th>
                <th>Durum</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sliders as $slider): ?>
                <tr>
                    <td><?= htmlspecialchars($slider['title']) ?></td>
                    <td><?= (int) $slider['is_active'] === 1 ? 'Aktif' : 'Pasif' ?></td>
                    <td>
                        <a class="btn" href="/admin/sliders.php?edit=<?= (int) $slider['id'] ?>">Düzenle</a>
                        <button class="btn danger" data-delete-slider="<?= (int) $slider['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/sliders.php?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php
admin_footer();
?>
