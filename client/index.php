<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="row g-4" id="clientSummary">
        <div class="col-md-4">
            <div class="card card-glass p-4 h-100">
                <p class="text-white-50 small mb-1">Toplam Dosya</p>
                <h3 class="h2 mb-0" data-summary="total_files">-</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass p-4 h-100">
                <p class="text-white-50 small mb-1">Kullanılan Alan</p>
                <h3 class="h2 mb-0" data-summary="total_size">-</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-glass p-4 h-100">
                <p class="text-white-50 small mb-1">Paket</p>
                <h3 class="h4 mb-0" data-summary="package_name">-</h3>
            </div>
        </div>
    </div>
</div>
<script>
(async () => {
    const response = await fetch('<?= BASE_URL ?>/api/client.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'dashboard', csrf_token: window.APP_CONFIG.csrfToken })
    });
    const data = await response.json();
    if (data.status !== 'success') return;
    const { usage, package: pkg } = data.data;
    document.querySelector('[data-summary="total_files"]').textContent = usage.total_files;
    document.querySelector('[data-summary="total_size"]').textContent = `${(usage.total_size / 1024 / 1024).toFixed(2)} MB`;
    document.querySelector('[data-summary="package_name"]').textContent = pkg ? pkg.name : 'Seçilmedi';
})();
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
