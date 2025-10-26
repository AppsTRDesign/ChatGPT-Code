<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
$user = current_user();
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="card card-glass p-4">
        <h2 class="h5 mb-4">Profilim</h2>
        <form id="profileForm">
            <div class="mb-3">
                <label class="form-label">Ad Soyad</label>
                <input type="text" class="form-control" name="name" value="<?= sanitize($user['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">E-posta</label>
                <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Yeni Şifre (opsiyonel)</label>
                <input type="password" class="form-control" name="password" placeholder="Yeni şifre">
            </div>
            <button type="button" class="btn btn-gradient" onclick="updateProfile()">Güncelle</button>
        </form>
    </div>
</div>
<script>
async function updateProfile() {
    const appConfig = window.APP_CONFIG || {};
    const form = document.getElementById('profileForm');
    const formData = new FormData(form);
    formData.append('action', 'profile-update');
    formData.append('csrf_token', appConfig.csrfToken);
    try {
        const response = await fetch('<?= BASE_URL ?>/api/client.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Profil güncellenemedi');
        }
        Swal.fire({ icon: 'success', title: 'Güncellendi', text: data.message });
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
    }
}
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
