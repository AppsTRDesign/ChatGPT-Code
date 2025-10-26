<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="row g-4" id="adminStats">
        <div class="col-md-4">
            <div class="card card-glass p-4 h-100">
                <p class="text-white-50 small mb-1">Toplam Dosya</p>
                <h3 class="h2 mb-0" data-stat="total_files">-</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass p-4 h-100">
                <p class="text-white-50 small mb-1">Toplam Dosya Boyutu</p>
                <h3 class="h2 mb-0" data-stat="total_size">-</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass p-4 h-100">
                <p class="text-white-50 small mb-1">Kayıtlı Üye</p>
                <h3 class="h2 mb-0" data-stat="total_users">-</h3>
            </div>
        </div>
    </div>
</div>
<script>
(async () => {
    const appConfig = window.APP_CONFIG || {};
    try {
        const formData = new FormData();
        formData.append('action', 'stats');
        formData.append('csrf_token', appConfig.csrfToken);
        const response = await fetch('<?= BASE_URL ?>/api/admin.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        if (data.status === 'success') {
            Object.entries(data.data).forEach(([key, value]) => {
                const el = document.querySelector(`[data-stat="${key}"]`);
                if (el) {
                    el.textContent = value;
                }
            });
        }
    } catch (error) {
        console.error(error);
    }
})();
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
