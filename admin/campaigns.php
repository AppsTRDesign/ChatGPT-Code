<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$total = (int) $pdo->query('SELECT COUNT(*) FROM campaign_banners')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$campaignStmt = $pdo->prepare('SELECT * FROM campaign_banners ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$campaignStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$campaignStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$campaignStmt->execute();
$campaigns = $campaignStmt->fetchAll(PDO::FETCH_ASSOC);
$editId = (int) ($_GET['edit'] ?? 0);
$campaignData = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM campaign_banners WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $campaignData = $stmt->fetch(PDO::FETCH_ASSOC);
}

admin_header('Kampanya Görselleri');
?>
<section class="panel">
    <h2><?= $campaignData ? 'Kampanya Düzenle' : 'Yeni Kampanya Görseli' ?></h2>
    <form class="admin-form" data-ajax="campaign" enctype="multipart/form-data" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) ($campaignData['id'] ?? 0) ?>">
        <label>Kampanya Görseli
            <input type="file" name="image" <?= $campaignData ? '' : 'required' ?>>
        </label>
        <?php if (!empty($campaignData['image'])): ?>
            <div class="media-preview" data-remove-on-delete>
                <img src="<?= htmlspecialchars($campaignData['image']) ?>" alt="Kampanya Görseli">
                <button class="btn danger" type="button" data-delete-campaign-image="<?= (int) $campaignData['id'] ?>">Görseli Sil</button>
            </div>
        <?php endif; ?>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<section class="panel">
    <h2>Kampanya Görselleri</h2>
    <table>
        <thead>
            <tr>
                <th>Görsel</th>
                <th>Eklenme</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($campaigns as $campaign): ?>
                <tr>
                    <td>
                        <?php if (!empty($campaign['image'])): ?>
                            <img src="<?= htmlspecialchars($campaign['image']) ?>" alt="Kampanya Görseli" style="width: 120px; height: 70px; object-fit: cover; border-radius: 12px;">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($campaign['created_at']) ?></td>
                    <td>
                        <a class="btn" href="/admin/campaigns.php?edit=<?= (int) $campaign['id'] ?>">Düzenle</a>
                        <button class="btn danger" data-delete-campaign="<?= (int) $campaign['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/campaigns.php?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php
admin_footer();
?>
