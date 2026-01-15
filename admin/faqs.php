<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$pdo = db();
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$total = (int) $pdo->query('SELECT COUNT(*) FROM faqs')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$faqsStmt = $pdo->prepare('SELECT * FROM faqs ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$faqsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$faqsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$faqsStmt->execute();
$faqs = $faqsStmt->fetchAll(PDO::FETCH_ASSOC);
$editId = (int) ($_GET['edit'] ?? 0);
$faqData = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM faqs WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $faqData = $stmt->fetch(PDO::FETCH_ASSOC);
}

admin_header('SSS Yönetimi');
?>
<section class="panel">
    <h2><?= $faqData ? 'SSS Düzenle' : 'Yeni SSS' ?></h2>
    <form class="admin-form" data-ajax="faq" method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) ($faqData['id'] ?? 0) ?>">
        <label>Soru<input type="text" name="question" value="<?= htmlspecialchars($faqData['question'] ?? '') ?>" required></label>
        <label>Cevap<textarea name="answer" rows="4" required><?= htmlspecialchars($faqData['answer'] ?? '') ?></textarea></label>
        <button class="btn primary" type="submit">Kaydet</button>
    </form>
</section>
<section class="panel">
    <h2>SSS</h2>
    <table>
        <thead>
            <tr>
                <th>Soru</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($faqs as $faq): ?>
                <tr>
                    <td><?= htmlspecialchars($faq['question']) ?></td>
                    <td>
                        <a class="btn" href="/admin/faqs.php?edit=<?= (int) $faq['id'] ?>">Düzenle</a>
                        <button class="btn danger" data-delete-faq="<?= (int) $faq['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/faqs.php?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php
admin_footer();
?>
