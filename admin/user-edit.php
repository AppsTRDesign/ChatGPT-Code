<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\PackageManager;
use App\Subscription;

Auth::requireRole('admin');
$db = Helpers::db();

$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
    Helpers::flash('message', 'Üye bulunamadı.');
    redirect('/admin/users');
}

$stmt = $db->prepare('SELECT id, username, email, role, is_approved, login_blocked, email_verified FROM users WHERE id = :id');
$stmt->execute(['id' => $id]);
$userRow = $stmt->fetch();

if (!$userRow) {
    Helpers::flash('message', 'Üye bulunamadı.');
    redirect('/admin/users');
}

$currentPackage = Subscription::activeForUser($id);
$packages = PackageManager::allActive();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
        Helpers::flash('message', 'Geçersiz oturum anahtarı.');
        redirect('/admin/user-edit?id=' . $id);
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'client';
    $password = trim($_POST['password'] ?? '');
    $isApproved = isset($_POST['is_approved']) ? 1 : (int) ($userRow['is_approved'] ?? 1);
    $loginBlocked = isset($_POST['login_blocked']) ? 1 : 0;
    $emailVerified = isset($_POST['email_verified']) ? 1 : 0;

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
        'is_approved' => $isApproved,
        'login_blocked' => $loginBlocked,
        'email_verified' => $emailVerified,
        'id' => $id,
    ];

    $sql = 'UPDATE users SET username = :username, email = :email, role = :role, is_approved = :is_approved, login_blocked = :login_blocked, email_verified = :email_verified';

    if ($password !== '') {
        $sql .= ', password = :password';
        $params['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= ' WHERE id = :id';

    $update = $db->prepare($sql);
    $update->execute($params);

    $messages = ['Üye bilgileri güncellendi.'];

    if ($loginBlocked !== (int) ($userRow['login_blocked'] ?? 0)) {
        $messages[] = $loginBlocked ? 'Kullanıcının giriş izni kapatıldı.' : 'Kullanıcının giriş izni açıldı.';
    }

    if ($emailVerified !== (int) ($userRow['email_verified'] ?? 0)) {
        $messages[] = $emailVerified ? 'E-posta adresi doğrulandı.' : 'E-posta doğrulaması kaldırıldı.';
    }

    if (isset($_POST['package_id'])) {
        $packageId = (int) $_POST['package_id'];
        if ($packageId === 0) {
            Subscription::cancelActive($id);
            $messages[] = 'Aktif paket iptal edildi.';
        } elseif ($packageId > 0) {
            if (!$currentPackage || (int) $currentPackage['package_id'] !== $packageId) {
                if (Subscription::assignPackage($id, $packageId)) {
                    $messages[] = 'Yeni paket atandı ve aktifleştirildi.';
                } else {
                    $messages[] = 'Seçilen paket atanamadı.';
                }
            }
        }
    }

    Helpers::flash('message', implode(' ', $messages));
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
        <div class="col-md-4">
            <label class="form-label">Mail Onayı</label>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="userEmailVerified" name="email_verified" value="1" <?= (int) ($userRow['email_verified'] ?? 0) === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="userEmailVerified">E-posta doğrulandı</label>
            </div>
            <small class="form-text text-white-50">Doğrulama e-postası olmadan manuel olarak onaylayabilirsiniz.</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">Giriş İzni</label>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="userLoginBlocked" name="login_blocked" value="1" <?= (int) ($userRow['login_blocked'] ?? 0) === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="userLoginBlocked">Girişi engelle</label>
            </div>
            <small class="form-text text-white-50">Aktif olduğunda kullanıcı giriş yapamaz.</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">Üyelik Durumu</label>
            <div class="form-control bg-transparent border border-secondary text-white-50">
                <?= (int) ($userRow['is_approved'] ?? 1) === 1 ? 'Onaylı' : 'Onay bekliyor' ?>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Paket Yönetimi</label>
            <select name="package_id" class="form-select">
                <option value="-1">Değişiklik yapma</option>
                <option value="0">Aktif paketi iptal et</option>
                <?php foreach ($packages as $package): ?>
                    <option value="<?= (int) $package['id'] ?>" <?= $currentPackage && (int) $currentPackage['package_id'] === (int) $package['id'] ? 'selected' : '' ?>>
                        <?= Helpers::e($package['name']) ?> (<?= Helpers::e($package['monthly_limit']) ?>/ay)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($currentPackage): ?>
                <small class="form-text text-white-50">Mevcut paket: <?= Helpers::e($currentPackage['name']) ?>, bitiş <?= Helpers::e(date('d.m.Y', strtotime((string) $currentPackage['expires_at']))) ?></small>
            <?php else: ?>
                <small class="form-text text-white-50">Aktif paket bulunmuyor.</small>
            <?php endif; ?>
        </div>
        <div class="col-12 d-flex justify-content-between">
            <a href="/admin/users" class="btn btn-outline-light">İptal</a>
            <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/footer.php'; ?>
