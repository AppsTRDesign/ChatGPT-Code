<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/layout.php';

require_admin();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$total = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$usersStmt = db()->prepare('SELECT id, name, email, phone, role, created_at FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$usersStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$usersStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$usersStmt->execute();
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

$viewId = (int) ($_GET['view'] ?? 0);
$viewUser = null;
if ($viewId > 0) {
    $viewStmt = db()->prepare('SELECT id, name, email, phone, address, role, avatar, created_at FROM users WHERE id = :id');
    $viewStmt->execute(['id' => $viewId]);
    $viewUser = $viewStmt->fetch(PDO::FETCH_ASSOC);
}

admin_header('Kullanıcı Yönetimi');
?>
<?php if ($viewUser): ?>
<section class="panel">
    <h2>Kullanıcı Detayı #<?= (int) $viewUser['id'] ?></h2>
    <p><strong>Ad Soyad:</strong> <?= htmlspecialchars($viewUser['name']) ?></p>
    <p><strong>E-posta:</strong> <?= htmlspecialchars($viewUser['email']) ?></p>
    <p><strong>Telefon:</strong> <?= htmlspecialchars($viewUser['phone'] ?? '-') ?></p>
    <p><strong>Adres:</strong> <?= htmlspecialchars($viewUser['address'] ?? '-') ?></p>
    <p><strong>Rol:</strong> <?= htmlspecialchars($viewUser['role']) ?></p>
    <p><strong>Kayıt Tarihi:</strong> <?= htmlspecialchars($viewUser['created_at']) ?></p>
    <?php if (!empty($viewUser['avatar'])): ?>
        <p><img src="<?= htmlspecialchars($viewUser['avatar']) ?>" alt="<?= htmlspecialchars($viewUser['name']) ?>" style="max-width:90px;border-radius:50%"></p>
    <?php endif; ?>
</section>
<?php endif; ?>
<section class="panel">
    <table>
        <thead>
            <tr>
                <th>Ad Soyad</th>
                <th>E-posta</th>
                <th>Telefon</th>
                <th>Rol</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td>
                        <a class="btn" href="/admin/users.php?view=<?= (int) $user['id'] ?>">Detay</a>
                        <a class="btn" href="/admin/user-edit.php?id=<?= (int) $user['id'] ?>">Düzenle</a>
                        <button class="btn danger" data-delete-user="<?= (int) $user['id'] ?>">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="btn <?= $i === $page ? 'primary' : '' ?>" href="/admin/users.php?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php
admin_footer();
?>
