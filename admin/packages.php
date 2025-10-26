<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
$packages = $pdo->query('SELECT * FROM packages ORDER BY price ASC')->fetchAll();
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="card card-glass p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h5 mb-0">Paketler</h2>
            <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#packageModal">Yeni Paket</button>
        </div>
        <div class="table-responsive">
            <table class="table table-dark-glass align-middle">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Depolama</th>
                        <th>Maks. Yükleme</th>
                        <th>Fiyat</th>
                        <th>Durum</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($packages as $package): ?>
                        <tr id="package-<?= (int) $package['id'] ?>">
                            <td><?= sanitize($package['name']) ?></td>
                            <td><?= format_bytes((int) $package['storage_limit']) ?></td>
                            <td><?= (int) $package['max_concurrent_uploads'] ?></td>
                            <td><?= number_format((float) $package['price'], 2) ?> ₺</td>
                            <td>
                                <span class="badge <?= $package['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $package['is_active'] ? 'Aktif' : 'Pasif' ?></span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#packageModal" data-package='<?= json_encode($package, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>Düzenle</button>
                                <button class="btn btn-sm btn-danger" onclick="deletePackage(<?= (int) $package['id'] ?>)">Sil</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="packageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-0">
                <h5 class="modal-title">Paket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="packageForm">
                    <input type="hidden" name="id" id="packageId">
                    <div class="mb-3">
                        <label class="form-label" for="packageName">Paket Adı</label>
                        <input type="text" class="form-control" id="packageName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="packageStorage">Depolama (byte)</label>
                        <input type="number" class="form-control" id="packageStorage" name="storage_limit" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="packageUploads">Maksimum aynı anda yükleme</label>
                        <input type="number" class="form-control" id="packageUploads" name="max_concurrent_uploads" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="packagePrice">Fiyat</label>
                        <input type="number" step="0.01" class="form-control" id="packagePrice" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="packageFeatures">Özellikler (virgülle ayırın)</label>
                        <input type="text" class="form-control" id="packageFeatures" name="features">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="packageActive">
                        <label class="form-check-label" for="packageActive">Aktif</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-gradient" onclick="savePackage()">Kaydet</button>
            </div>
        </div>
    </div>
</div>
<script>
const appConfig = window.APP_CONFIG || {};
const packageModal = document.getElementById('packageModal');
packageModal?.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    if (!button?.getAttribute('data-package')) {
        document.getElementById('packageForm').reset();
        document.getElementById('packageId').value = '';
        document.getElementById('packageActive').checked = true;
        return;
    }
    const data = JSON.parse(button.getAttribute('data-package'));
    document.getElementById('packageId').value = data.id;
    document.getElementById('packageName').value = data.name;
    document.getElementById('packageStorage').value = data.storage_limit;
    document.getElementById('packageUploads').value = data.max_concurrent_uploads;
    document.getElementById('packagePrice').value = data.price;
    document.getElementById('packageFeatures').value = (JSON.parse(data.features || '[]') || []).join(', ');
    document.getElementById('packageActive').checked = data.is_active == 1;
});

async function savePackage() {
    const form = document.getElementById('packageForm');
    const formData = new FormData(form);
    const features = formData.get('features');
    formData.set('features', features ? features.split(',').map(item => item.trim()).filter(Boolean) : []);
    formData.append('action', 'save-package');
    formData.append('csrf_token', appConfig.csrfToken);
    formData.append('is_active', document.getElementById('packageActive').checked ? 1 : 0);
    try {
        const response = await fetch('<?= BASE_URL ?>/api/admin.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Paket kaydedilemedi');
        }
        Swal.fire({ icon: 'success', title: 'Kaydedildi', text: data.message });
        setTimeout(() => window.location.reload(), 800);
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
    }
}

async function deletePackage(id) {
    const confirm = await Swal.fire({
        icon: 'warning',
        title: 'Emin misiniz?',
        text: 'Paket silinecek.',
        showCancelButton: true,
        confirmButtonText: 'Sil',
        cancelButtonText: 'İptal'
    });
    if (!confirm.isConfirmed) return;
    try {
        const response = await fetch('<?= BASE_URL ?>/api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'delete-package', id, csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Paket silinemedi');
        }
        document.getElementById(`package-${id}`)?.remove();
        Swal.fire({ icon: 'success', title: 'Silindi', text: data.message });
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
    }
}
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
