<?php
require_once __DIR__ . '/../config.php';
require_auth(true);
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/nav.php';
?>
<div class="container pb-5">
    <div class="card card-glass p-4">
        <h2 class="h5 mb-4">Dosya Yönetimi</h2>
        <div class="table-responsive">
            <table class="table table-dark-glass align-middle" id="adminFilesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dosya</th>
                        <th>Boyut</th>
                        <th>Kullanıcı</th>
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
const escapeHtml = (value = '') => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

async function loadAdminFiles() {
    const formData = new FormData();
    formData.append('action', 'list');
    formData.append('csrf_token', window.APP_CONFIG.csrfToken);
    const response = await fetch('<?= BASE_URL ?>/api/files.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await response.json();
    if (data.status !== 'success') {
        throw new Error(data.message || 'Dosyalar alınamadı');
    }
    const tbody = document.querySelector('#adminFilesTable tbody');
    tbody.innerHTML = '';
    data.files.forEach(file => {
        const tr = document.createElement('tr');
        tr.id = `file-${file.id}`;
        const safeName = escapeHtml(file.filename);
        const fileLink = `${window.APP_CONFIG.baseUrl}/file/${file.id}-${safeName.split('.')[0]}`;
        tr.innerHTML = `
            <td>${file.id}</td>
            <td>
                <div class="fw-semibold">${safeName}</div>
                <a class="link-light small" href="${window.APP_CONFIG.baseUrl}/uploads/${file.stored_name}" target="_blank">Görüntüle</a>
            </td>
            <td>${(Number(file.size) / 1024 / 1024).toFixed(2)} MB</td>
            <td>${file.owner_name || '-'}</td>
            <td>${new Date(file.uploaded_at).toLocaleString('tr-TR')}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-light me-2" data-action="rename" data-file-id="${file.id}" data-file-name="${safeName}">Düzenle</button>
                <button class="btn btn-sm btn-danger" data-delete-url="<?= BASE_URL ?>/api/files.php" data-file-id="${file.id}">Sil</button>
            </td>`;
        tbody.appendChild(tr);
    });
    tbody.querySelectorAll('[data-delete-url]').forEach(btn => btn.addEventListener('click', () => deleteFile(btn)));
    tbody.querySelectorAll('[data-action="rename"]').forEach(btn => btn.addEventListener('click', () => promptRename(btn.dataset.fileId, btn.dataset.fileName)));
}

async function deleteFile(button) {
    const fileId = button.getAttribute('data-file-id');
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Emin misiniz?',
        text: 'Dosya kalıcı olarak silinecek.',
        showCancelButton: true,
        confirmButtonText: 'Sil',
        cancelButtonText: 'İptal'
    });
    if (!result.isConfirmed) return;
    const response = await fetch('<?= BASE_URL ?>/api/files.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'delete', file_id: fileId, csrf_token: window.APP_CONFIG.csrfToken })
    });
    const data = await response.json();
    if (data.status === 'success') {
        document.getElementById(`file-${fileId}`)?.remove();
        Swal.fire({ icon: 'success', title: 'Silindi', text: data.message });
    } else {
        Swal.fire({ icon: 'error', title: 'Hata', text: data.message || 'Silinemedi' });
    }
}

async function promptRename(fileId, currentName) {
    const { value: newName } = await Swal.fire({
        title: 'Dosya adını güncelle',
        input: 'text',
        inputValue: currentName,
        showCancelButton: true,
        confirmButtonText: 'Kaydet',
        cancelButtonText: 'İptal'
    });
    if (!newName) return;
    const response = await fetch('<?= BASE_URL ?>/api/files.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'rename', file_id: fileId, filename: newName, csrf_token: window.APP_CONFIG.csrfToken })
    });
    const data = await response.json();
    if (data.status === 'success') {
        Swal.fire({ icon: 'success', title: 'Güncellendi', text: data.message });
        loadAdminFiles();
    } else {
        Swal.fire({ icon: 'error', title: 'Hata', text: data.message || 'Güncellenemedi' });
    }
}

loadAdminFiles().catch(console.error);
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
