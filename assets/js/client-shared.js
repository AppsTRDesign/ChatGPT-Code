(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const tableBody = document.querySelector('#clientSharedTable tbody');
    const refreshButton = document.querySelector('[data-refresh-shares]');

    if (!tableBody) {
        return;
    }

    let rows = [];

    const render = () => {
        tableBody.innerHTML = '';
        if (!rows.length) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-white-50 py-4">Aktif paylaşım bulunmuyor.</td></tr>';
            return;
        }
        tableBody.innerHTML = rows.map((item) => {
            const shareLink = item.share_url || '#';
            const expires = item.share_expires_at ? new Date(item.share_expires_at).toLocaleString('tr-TR') : '—';
            const created = item.share_created_at ? new Date(item.share_created_at).toLocaleString('tr-TR') : '—';
            return `
                <tr data-share-id="${item.id}" data-share-token="${item.share_token}">
                    <td>${item.filename || '—'}</td>
                    <td class="text-break"><a href="${shareLink}" target="_blank" class="link-light">${shareLink}</a></td>
                    <td>${created}</td>
                    <td>${expires}</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-light" data-action="copy">Kopyala</button>
                            <button type="button" class="btn btn-outline-danger" data-action="revoke">Kaldır</button>
                        </div>
                    </td>
                </tr>`;
        }).join('');
    };

    const loadShares = async () => {
        const response = await fetch(`${appConfig.baseUrl}/api/client.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'list-shared-files', csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Paylaşılan dosyalar alınamadı');
        }
        rows = data.data || [];
        render();
    };

    const handleCopy = async (shareUrl) => {
        try {
            await navigator.clipboard.writeText(shareUrl);
            Swal.fire({ icon: 'success', title: 'Kopyalandı', text: 'Bağlantı panoya kopyalandı.' });
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Kopyalama hatası', text: 'Bağlantı kopyalanamadı.' });
        }
    };

    const handleRevoke = async (fileId) => {
        const confirm = await Swal.fire({
            icon: 'warning',
            title: 'Paylaşımı kaldır?',
            text: 'Bağlantı erişime kapatılacaktır.',
            showCancelButton: true,
            confirmButtonText: 'Evet, kaldır',
            cancelButtonText: 'İptal'
        });
        if (!confirm.isConfirmed) {
            return;
        }
        const response = await fetch(`${appConfig.baseUrl}/api/files.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'revoke-share', file_id: fileId, csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Paylaşım kaldırılamadı');
        }
        rows = rows.filter((row) => Number(row.id) !== Number(fileId));
        render();
        Swal.fire({ icon: 'success', title: 'Paylaşım kapatıldı', text: data.message || 'Bağlantı erişime kapatıldı.' });
    };

    tableBody.addEventListener('click', (event) => {
        const actionButton = event.target.closest('button[data-action]');
        if (!actionButton) {
            return;
        }
        const row = actionButton.closest('tr[data-share-id]');
        if (!row) {
            return;
        }
        const fileId = row.dataset.shareId;
        const shareUrl = row.querySelector('a')?.getAttribute('href') || '';
        if (actionButton.dataset.action === 'copy') {
            handleCopy(shareUrl);
        } else if (actionButton.dataset.action === 'revoke') {
            handleRevoke(fileId).catch((error) => Swal.fire({ icon: 'error', title: 'Hata', text: error.message }));
        }
    });

    refreshButton?.addEventListener('click', () => {
        loadShares().catch((error) => Swal.fire({ icon: 'error', title: 'Hata', text: error.message }));
    });

    loadShares().catch((error) => {
        console.error(error);
        Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
    });
})();
