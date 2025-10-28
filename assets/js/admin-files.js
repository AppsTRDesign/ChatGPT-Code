(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const table = document.getElementById('adminFilesTable');
    if (!table) {
        return;
    }

    const tbody = table.querySelector('tbody');
    const searchInput = document.getElementById('adminFilesSearch');
    let rows = [];

    const formatBytes = (bytes) => {
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '0 B';
        }
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        const value = bytes / Math.pow(1024, index);
        return `${value.toFixed(value >= 10 || index === 0 ? 0 : 2)} ${units[index]}`;
    };

    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const render = (data) => {
        tbody.innerHTML = '';
        if (!data.length) {
            const empty = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 6;
            cell.className = 'text-center text-white-50';
            cell.textContent = 'Kayıt bulunamadı.';
            empty.appendChild(cell);
            tbody.appendChild(empty);
            return;
        }
        data.forEach(file => {
            const tr = document.createElement('tr');
            tr.dataset.fileId = file.id;
            tr.innerHTML = `
                <td data-label="ID">${file.id}</td>
                <td data-label="Dosya">
                    <div class="fw-semibold">${escapeHtml(file.filename)}</div>
                    <div class="extra-small text-white-50">${escapeHtml(file.folder_path || 'Ana Depo')}</div>
                    <div class="mt-1 d-flex gap-2 flex-wrap extra-small">
                        <a class="link-light" href="${file.download_url}" target="_blank">Bağlantı</a>
                        <a class="link-light" href="${file.direct_url}" target="_blank">Sunucuda görüntüle</a>
                    </div>
                </td>
                <td data-label="Boyut">${formatBytes(Number(file.size))}</td>
                <td data-label="Kullanıcı">${escapeHtml(file.owner_name || '-')}</td>
                <td data-label="Tarih">${new Date(file.uploaded_at).toLocaleString('tr-TR')}</td>
                <td data-label="İşlemler" class="text-end">
                    <button class="btn btn-sm btn-outline-light me-2" data-action="rename">Düzenle</button>
                    <button class="btn btn-sm btn-danger" data-action="delete">Sil</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    };

    const applySearch = () => {
        const query = (searchInput?.value || '').trim().toLowerCase();
        if (!query) {
            render(rows);
            return;
        }
        const filtered = rows.filter(file => {
            const tokens = [
                file.filename,
                file.owner_name || '',
                file.type || '',
                file.folder_path || ''
            ].join(' ').toLowerCase();
            return tokens.includes(query);
        });
        render(filtered);
    };

    const loadFiles = async () => {
        const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'list-files', csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Dosyalar alınamadı');
        }
        rows = data.data || [];
        applySearch();
    };

    const handleRename = async (fileId) => {
        const file = rows.find(item => item.id === Number(fileId));
        const currentName = file?.filename || '';
        const { value: newName } = await Swal.fire({
            title: 'Dosya adını güncelle',
            input: 'text',
            inputValue: currentName,
            showCancelButton: true,
            confirmButtonText: 'Kaydet',
            cancelButtonText: 'İptal'
        });
        if (!newName) {
            return;
        }
        const response = await fetch(`${appConfig.baseUrl}/api/files.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'rename', file_id: fileId, filename: newName, csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Güncellenemedi');
        }
        Swal.fire({ icon: 'success', title: 'Güncellendi', text: data.message });
        await loadFiles();
    };

    const handleDelete = async (fileId) => {
        const confirm = await Swal.fire({
            icon: 'warning',
            title: 'Emin misiniz?',
            text: 'Dosya kalıcı olarak silinecek.',
            showCancelButton: true,
            confirmButtonText: 'Sil',
            cancelButtonText: 'İptal'
        });
        if (!confirm.isConfirmed) {
            return;
        }
        const response = await fetch(`${appConfig.baseUrl}/api/files.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'delete', file_id: fileId, csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Silinemedi');
        }
        rows = rows.filter(item => item.id !== Number(fileId));
        Swal.fire({ icon: 'success', title: 'Silindi', text: data.message });
        applySearch();
    };

    table.addEventListener('click', (event) => {
        const target = event.target.closest('button[data-action]');
        if (!target) {
            return;
        }
        const row = target.closest('tr');
        if (!row) {
            return;
        }
        const fileId = row.dataset.fileId;
        if (!fileId) {
            return;
        }
        const action = target.getAttribute('data-action');
        if (action === 'rename') {
            handleRename(fileId).catch(error => Swal.fire({ icon: 'error', title: 'Hata', text: error.message }));
        } else if (action === 'delete') {
            handleDelete(fileId).catch(error => Swal.fire({ icon: 'error', title: 'Hata', text: error.message }));
        }
    });

    searchInput?.addEventListener('input', applySearch);

    loadFiles().catch(error => {
        console.error(error);
        Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
    });
})();
