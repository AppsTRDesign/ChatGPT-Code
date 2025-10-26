<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
$packages = $pdo->query('SELECT * FROM packages WHERE is_active = 1 ORDER BY price ASC')->fetchAll();
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="row g-4">
        <?php foreach ($packages as $package): $features = json_decode($package['features'] ?? '[]', true) ?: []; ?>
            <div class="col-md-4">
                <div class="card card-glass h-100 p-4 text-center">
                    <h3 class="h4 text-white mb-3"><?= sanitize($package['name']) ?></h3>
                    <p class="display-6 fw-bold text-white"><?= $package['price'] > 0 ? number_format($package['price'], 2) . ' ₺' : 'Ücretsiz' ?></p>
                    <span class="badge badge-custom mb-3">Depo: <?= format_bytes((int) $package['storage_limit']) ?></span>
                    <p class="text-white-50 small mb-3">Aynı anda <?= (int) $package['max_concurrent_uploads'] ?> yükleme hakkı</p>
                    <ul class="list-unstyled text-white-50 mb-4">
                        <?php foreach ($features as $feature): ?>
                            <li>• <?= sanitize($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button class="btn btn-gradient w-100" onclick="purchasePackage(<?= (int) $package['id'] ?>)">Paketi Seç</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
async function purchasePackage(id) {
    const appConfig = window.APP_CONFIG || {};
    const confirm = await Swal.fire({
        icon: 'question',
        title: 'Paketi onaylıyor musunuz?',
        showCancelButton: true,
        confirmButtonText: 'Satın Al',
        cancelButtonText: 'İptal'
    });
    if (!confirm.isConfirmed) return;
    try {
        const response = await fetch('<?= BASE_URL ?>/api/client.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'purchase-package', package_id: id, csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Paket seçilemedi');
        }
        Swal.fire({ icon: 'success', title: 'Başarılı', text: data.message });
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
    }
}
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
