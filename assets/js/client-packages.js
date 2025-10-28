(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const container = document.getElementById('clientPackages');
    if (!container) {
        return;
    }

    const formatBytes = (bytes) => {
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '0 B';
        }
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        const value = bytes / Math.pow(1024, index);
        return `${value.toFixed(value >= 10 || index === 0 ? 0 : 2)} ${units[index]}`;
    };

    const renderPackages = (packages) => {
        container.innerHTML = '';
        if (!packages.length) {
            container.innerHTML = '<div class="col-12 text-center text-white-50">Aktif paket bulunamadı.</div>';
            return;
        }
        packages.forEach(pkg => {
            const col = document.createElement('div');
            col.className = 'col-md-4';
            col.innerHTML = `
                <div class="card card-glass h-100 p-4 text-center">
                    <h3 class="h4 text-white mb-3">${pkg.name}</h3>
                    <p class="display-6 fw-bold text-white">${pkg.price > 0 ? pkg.price.toFixed(2) + ' ₺' : 'Ücretsiz'}</p>
                    <span class="badge badge-custom mb-3">Depo: ${formatBytes(Number(pkg.storage_limit))}</span>
                    <p class="text-white-50 small mb-3">Aynı anda ${pkg.max_concurrent_uploads} yükleme hakkı</p>
                    <ul class="list-unstyled text-white-50 mb-4">
                        ${(pkg.features || []).map(feature => `<li>• ${feature}</li>`).join('') || '<li>• Standart özellikler</li>'}
                    </ul>
                    <button class="btn btn-gradient w-100" data-action="purchase" data-id="${pkg.id}">Paketi Seç</button>
                </div>
            `;
            container.appendChild(col);
        });
    };

    const loadPackages = async () => {
        const response = await fetch(`${appConfig.baseUrl}/api/client.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'list-packages', csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Paketler alınamadı');
        }
        renderPackages(data.data || []);
    };

    container.addEventListener('click', async (event) => {
        const button = event.target.closest('button[data-action="purchase"]');
        if (!button) {
            return;
        }
        const id = Number(button.dataset.id);
        const confirm = await Swal.fire({
            icon: 'question',
            title: 'Paketi onaylıyor musunuz?',
            showCancelButton: true,
            confirmButtonText: 'Satın Al',
            cancelButtonText: 'İptal'
        });
        if (!confirm.isConfirmed) {
            return;
        }
        try {
            const response = await fetch(`${appConfig.baseUrl}/api/client.php`, {
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
    });

    loadPackages().catch(error => {
        console.error(error);
        container.innerHTML = '<div class="col-12 text-center text-danger">Paketler yüklenemedi.</div>';
    });
})();
