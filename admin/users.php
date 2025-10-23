<?php
require __DIR__ . '/header.php';

use App\Auth;
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
                    <th class="text-end">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= Helpers::e($user['id']) ?></td>
                        <td><?= Helpers::e($user['username']) ?></td>
                        <td><?= Helpers::e($user['email']) ?></td>
                        <td>
                            <span class="badge<?= $user['role'] === 'admin' ? ' bg-danger' : '' ?>">
                                <?= Helpers::e(ucfirst($user['role'])) ?>
                            </span>
                        </td>
                        <td><?= Helpers::e($user['created_at']) ?></td>
                        <td class="text-end">
                            <a href="/admin/user-edit?id=<?= Helpers::e($user['id']) ?>" class="btn btn-sm btn-outline-primary">Düzenle</a>
                            <?php if ((int) $user['id'] !== (int) Auth::user()['id']): ?>
                                <form action="/admin/user-delete" method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                                    <input type="hidden" name="id" value="<?= Helpers::e($user['id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Bu üyeyi silmek istediğinizden emin misiniz?">Sil</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
