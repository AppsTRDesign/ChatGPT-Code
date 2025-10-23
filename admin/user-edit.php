<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');
$db = Helpers::db();

$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
    Helpers::flash('message', 'Üye bulunamadı.');
    redirect('/admin/users');
}

$stmt = $db->prepare('SELECT id, username, email, role FROM users WHERE id = :id');
$stmt->execute(['id' => $id]);
$userRow = $stmt->fetch();

if (!$userRow) {
    Helpers::flash('message', 'Üye bulunamadı.');
    redirect('/admin/users');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Geçersiz oturum anahtarı.');
        redirect('/admin/user-edit?id=' . $id);
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'client';
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $email === '') {
        Helpers::flash('message', 'Kullanıcı adı ve e-posta alanları boş bırakılamaz.');
        redirect('/admin/user-edit?id=' . $id);
    }

    if (!in_array($role, ['admin', 'client'], true)) {
        $role = 'client';
    }

    $params = [
        'username' => $username,
        'email' => $email,
        'role' => $role,
        'id' => $id,
    ];

    $sql = 'UPDATE users SET username = :username, email = :email, role = :role';

    if ($password !== '') {
        $sql .= ', password = :password';
        $params['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= ' WHERE id = :id';

    $update = $db->prepare($sql);
    $update->execute($params);

    Helpers::flash('message', 'Üye bilgileri güncellendi.');
    redirect('/admin/users');
}

require __DIR__ . '/header.php';
?>
<h1 class="h3 mb-4">Üye Düzenle</h1>
<div class="card p-4">
    <form method="post" class="row g-4">
        <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
        <input type="hidden" name="id" value="<?= Helpers::e($userRow['id']) ?>">
        <div class="col-md-6">
            <label class="form-label">Kullanıcı Adı</label>
            <input type="text" class="form-control" name="username" value="<?= Helpers::e($userRow['username']) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">E-posta</label>
            <input type="email" class="form-control" name="email" value="<?= Helpers::e($userRow['email']) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Rol</label>
            <select name="role" class="form-select">
                <option value="client" <?= $userRow['role'] === 'client' ? 'selected' : '' ?>>Müşteri</option>
                <option value="admin" <?= $userRow['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Şifre (Opsiyonel)</label>
            <input type="password" class="form-control" name="password" placeholder="Yeni şifre">
            <small class="form-text">Şifre girmediğinizde mevcut şifre korunur.</small>
        </div>
        <div class="col-12 d-flex justify-content-between">
            <a href="/admin/users" class="btn btn-outline-light">İptal</a>
            <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/footer.php'; ?>
