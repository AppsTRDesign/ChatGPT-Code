<?php
require_once __DIR__ . '/../config.php';
require_login_redirect();
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="card card-glass p-4">
        <h2 class="h5 mb-4">Dosyalarım</h2>
        <div class="table-responsive">
            <table class="table table-dark-glass align-middle" id="clientFilesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dosya</th>
                        <th>Boyut</th>
                        <th>Tarih</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<script>
async function loadClientFiles() {
    const response = await fetch('<?= BASE_URL ?>/api/files.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'list', csrf_token: window.APP_CONFIG.csrfToken })
    });
    const data = await response.json();
    if (data.status !== 'success') return;
    const tbody = document.querySelector('#clientFilesTable tbody');
    tbody.innerHTML = '';
    data.files.forEach(file => {
        const safeName = String(file.filename)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
        const tr = document.createElement('tr');
        tr.id = `file-${file.id}`;
        tr.innerHTML = `
            <td>${file.id}</td>
            <td><div class="fw-semibold">${safeName}</div><a class="link-light small" target="_blank" href="${window.APP_CONFIG.baseUrl}/file/${file.id}-${safeName.split('.')[0]}">Görüntüle</a></td>
            <td>${(Number(file.size) / 1024 / 1024).toFixed(2)} MB</td>
            <td>${new Date(file.uploaded_at).toLocaleString('tr-TR')}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-light me-2" data-action="rename" data-file-id="${file.id}" data-file-name="${encodeURIComponent(file.filename)}">Düzenle</button>
                <button class="btn btn-sm btn-danger" data-action="delete" data-file-id="${file.id}">Sil</button>
            </td>`;
        tbody.appendChild(tr);
    });
    tbody.querySelectorAll('[data-action="rename"]').forEach(btn => btn.addEventListener('click', () => renameFile(btn.dataset.fileId, decodeURIComponent(btn.dataset.fileName))));
    tbody.querySelectorAll('[data-action="delete"]').forEach(btn => btn.addEventListener('click', () => deleteFile(btn.dataset.fileId)));
}

async function renameFile(id, current) {
    const { value } = await Swal.fire({
        title: 'Dosya adını güncelle',
        input: 'text',
        inputValue: current,
        showCancelButton: true,
        confirmButtonText: 'Kaydet',
        cancelButtonText: 'İptal'
    });
    if (!value) return;
    const response = await fetch('<?= BASE_URL ?>/api/files.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'rename', file_id: id, filename: value, csrf_token: window.APP_CONFIG.csrfToken })
    });
    const data = await response.json();
    if (data.status === 'success') {
        Swal.fire({ icon: 'success', title: 'Güncellendi', text: data.message });
        loadClientFiles();
    } else {
        Swal.fire({ icon: 'error', title: 'Hata', text: data.message || 'Güncellenemedi' });
    }
}

async function deleteFile(id) {
    const confirm = await Swal.fire({
        icon: 'warning',
        title: 'Silmek istiyor musunuz?',
        showCancelButton: true,
        confirmButtonText: 'Sil',
        cancelButtonText: 'İptal'
    });
    if (!confirm.isConfirmed) return;
    const response = await fetch('<?= BASE_URL ?>/api/files.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'delete', file_id: id, csrf_token: window.APP_CONFIG.csrfToken })
    });
    const data = await response.json();
    if (data.status === 'success') {
        document.getElementById(`file-${id}`)?.remove();
        Swal.fire({ icon: 'success', title: 'Silindi', text: data.message });
    } else {
        Swal.fire({ icon: 'error', title: 'Hata', text: data.message || 'Silinemedi' });
    }
}

loadClientFiles();
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
