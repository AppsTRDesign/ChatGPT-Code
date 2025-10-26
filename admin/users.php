<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
$packages = $pdo->query('SELECT id, name FROM packages WHERE is_active = 1 ORDER BY name')->fetchAll();
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="card card-glass p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Üye Yönetimi</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-dark-glass align-middle" id="adminUsersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Paket</th>
                        <th>Doğrulama</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT u.*, p.name AS package_name FROM users u LEFT JOIN packages p ON p.id = u.package_id ORDER BY u.created_at DESC");
                    foreach ($stmt as $user):
                    ?>
                    <tr id="user-<?= (int) $user['id'] ?>">
                        <td><?= (int) $user['id'] ?></td>
                        <td><?= sanitize($user['name']) ?></td>
                        <td><?= sanitize($user['email']) ?></td>
                        <td><?= sanitize($user['package_name'] ?? '—') ?></td>
                        <td><span class="badge <?= $user['email_verified'] ? 'bg-success' : 'bg-secondary' ?>"><?= $user['email_verified'] ? 'Onaylı' : 'Onaysız' ?></span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#userModal" data-user='<?= json_encode($user, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>Düzenle</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= (int) $user['id'] ?>)">Sil</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-0">
                <h5 class="modal-title">Üye Bilgileri</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" name="id" id="userId">
                    <div class="mb-3">
                        <label class="form-label" for="userName">Ad Soyad</label>
                        <input type="text" class="form-control" id="userName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="userEmail">E-posta</label>
                        <input type="email" class="form-control" id="userEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="userRole">Rol</label>
                        <select class="form-select" id="userRole" name="role">
                            <option value="client">Üye</option>
                            <option value="admin">Yönetici</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="userPackage">Paket</label>
                        <select class="form-select" id="userPackage" name="package_id">
                            <option value="">Seçilmedi</option>
                            <?php foreach ($packages as $package): ?>
                                <option value="<?= (int) $package['id'] ?>"><?= sanitize($package['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="userVerified">
                        <label class="form-check-label" for="userVerified">E-posta onaylı</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-gradient" onclick="saveUser()">Kaydet</button>
            </div>
        </div>
    </div>
</div>
<script>
const appConfig = window.APP_CONFIG || {};
const userModal = document.getElementById('userModal');
userModal?.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const data = JSON.parse(button.getAttribute('data-user'));
    document.getElementById('userId').value = data.id;
    document.getElementById('userName').value = data.name;
    document.getElementById('userEmail').value = data.email;
    document.getElementById('userRole').value = data.role;
    document.getElementById('userPackage').value = data.package_id || '';
    document.getElementById('userVerified').checked = data.email_verified == 1;
});

async function saveUser() {
    const form = document.getElementById('userForm');
    const formData = new FormData(form);
    formData.append('action', 'update-user');
    formData.append('csrf_token', appConfig.csrfToken);
    const verified = document.getElementById('userVerified').checked;
    try {
        const response = await fetch('<?= BASE_URL ?>/api/admin.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Kayıt güncellenemedi');
        }
        await fetch('<?= BASE_URL ?>/api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'verify-user', id: formData.get('id'), verified, csrf_token: appConfig.csrfToken })
        });
        Swal.fire({ icon: 'success', title: 'Kaydedildi', text: data.message });
        setTimeout(() => window.location.reload(), 800);
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
    }
}

async function deleteUser(id) {
    const confirm = await Swal.fire({
        icon: 'warning',
        title: 'Emin misiniz?',
        text: 'Üye kalıcı olarak silinecek.',
        showCancelButton: true,
        confirmButtonText: 'Sil',
        cancelButtonText: 'İptal'
    });
    if (!confirm.isConfirmed) return;
    try {
        const response = await fetch('<?= BASE_URL ?>/api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'delete-user', id, csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Silinemedi');
        }
        document.getElementById(`user-${id}`)?.remove();
        Swal.fire({ icon: 'success', title: 'Silindi', text: data.message });
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
    }
}
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
