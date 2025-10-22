<?php
require __DIR__ . '/header.php';

use App\Helpers;

$db = Helpers::db();
$users = $db->query('SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
?>
<h1 class="h3 mb-4">Üyeler</h1>
<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kullanıcı Adı</th>
                    <th>E-posta</th>
                    <th>Rol</th>
                    <th>Kayıt Tarihi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= Helpers::e($user['id']) ?></td>
                        <td><?= Helpers::e($user['username']) ?></td>
                        <td><?= Helpers::e($user['email']) ?></td>
                        <td><?= Helpers::e($user['role']) ?></td>
                        <td><?= Helpers::e($user['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
