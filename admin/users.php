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

admin_header('Kullanıcı Yönetimi');
?>
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
                        <button class="btn" data-view-user="<?= (int) $user['id'] ?>">Detay</button>
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
