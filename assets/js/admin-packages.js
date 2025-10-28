(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const table = document.getElementById('adminPackagesTable');
    if (!table) {
        return;
    }

    const tbody = table.querySelector('tbody');
    const searchInput = document.getElementById('adminPackagesSearch');
    const modalEl = document.getElementById('packageModal');
    let bootstrapLib = window.bootstrap || window.Bootstrap || null;
    if (typeof bootstrap !== 'undefined') {
        bootstrapLib = bootstrap;
    }
    const modal = modalEl && bootstrapLib ? new bootstrapLib.Modal(modalEl) : null;
    const form = document.getElementById('packageForm');
    const activeSwitch = document.getElementById('packageActive');
    const extensionTextarea = document.getElementById('packageExtensions');

    let packages = [];

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

    const summarizeExtensions = (list) => {
        if (!list?.length) {
            return 'Genel ayarlar';
        }
        if (list.length <= 3) {
            return list.map(item => `.${item}`).join(', ');
        }
        return `${list.slice(0, 3).map(item => `.${item}`).join(', ')}…`;
    };

    const renderTable = (data) => {
        tbody.innerHTML = '';
        if (!data.length) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 7;
            td.className = 'text-center text-white-50';
            td.textContent = 'Paket bulunamadı.';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }
        data.forEach(pkg => {
            const tr = document.createElement('tr');
            tr.dataset.packageId = pkg.id;
            tr.innerHTML = `
                <td data-label="Ad">${escapeHtml(pkg.name)}</td>
                <td data-label="Depolama">${formatBytes(Number(pkg.storage_limit))}</td>
                <td data-label="Maks. Yükleme">${pkg.max_concurrent_uploads}</td>
                <td data-label="İzinli Türler">${escapeHtml(summarizeExtensions(pkg.allowed_extensions))}</td>
                <td data-label="Fiyat">${Number(pkg.price).toFixed(2)} ₺</td>
                <td data-label="Durum"><span class="badge ${pkg.is_active ? 'bg-success' : 'bg-secondary'}">${pkg.is_active ? 'Aktif' : 'Pasif'}</span></td>
                <td data-label="İşlemler" class="text-end">
                    <button class="btn btn-sm btn-outline-light me-2" data-action="edit">Düzenle</button>
                    <button class="btn btn-sm btn-danger" data-action="delete">Sil</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    };

    const applySearch = () => {
        const query = (searchInput?.value || '').trim().toLowerCase();
        if (!query) {
            renderTable(packages);
            return;
        }
        const filtered = packages.filter(pkg => {
            const tokens = [pkg.name, summarizeExtensions(pkg.allowed_extensions), pkg.price].join(' ').toLowerCase();
            return tokens.includes(query);
        });
        renderTable(filtered);
    };

    const loadPackages = async () => {
        const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'list-packages', csrf_token: appConfig.csrfToken })
        });
        if (!response.ok) {
            throw new Error('Sunucu isteği başarısız oldu');
        }
        const raw = await response.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (error) {
            console.error('Sunucu yanıtı çözümlenemedi:', raw);
            throw new Error('Sunucudan beklenmeyen yanıt alındı.');
        }
        if (data.status !== 'success') {
            throw new Error(data.message || 'Paketler alınamadı');
        }
        packages = data.data || [];
        applySearch();
    };

    const openModal = (pkg, autoShow = true) => {
        if (!form) {
            return;
        }
        form.reset();
        form.querySelector('#packageId').value = pkg?.id || '';
        form.querySelector('#packageName').value = pkg?.name || '';
        form.querySelector('#packageStorage').value = pkg?.storage_limit || '';
        form.querySelector('#packageUploads').value = pkg?.max_concurrent_uploads || '';
        form.querySelector('#packagePrice').value = pkg?.price || '';
        form.querySelector('#packageFeatures').value = (pkg?.features || []).join(', ');
        if (activeSwitch) {
            activeSwitch.checked = pkg ? pkg.is_active === 1 : true;
        }
        if (extensionTextarea) {
            extensionTextarea.value = (pkg?.allowed_extensions || []).join('\n');
        }
        if (autoShow) {
            modal?.show();
        }
    };

    window.savePackage = async () => {
        if (!form) {
            return;
        }
        const formData = new FormData(form);
        const features = (formData.get('features') || '').toString()
            .split(',')
            .map(item => item.trim())
            .filter(Boolean);
        formData.set('features', JSON.stringify(features));
        formData.append('action', 'save-package');
        formData.append('csrf_token', appConfig.csrfToken);
        formData.append('is_active', activeSwitch?.checked ? 1 : 0);
        const extensionRaw = (formData.get('allowed_extensions') || '').toString();
        if (extensionRaw) {
            const extList = extensionRaw.split(/[\n,]+/).map(item => item.replace(/^\./, '').trim()).filter(Boolean);
            formData.set('allowed_extensions', JSON.stringify(extList));
        }
        try {
            const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.status !== 'success') {
                throw new Error(data.message || 'Paket kaydedilemedi');
            }
            Swal.fire({ icon: 'success', title: 'Kaydedildi', text: data.message });
            modal?.hide();
            await loadPackages();
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
        }
    };

    window.deletePackage = async (id) => {
        const confirm = await Swal.fire({
            icon: 'warning',
            title: 'Emin misiniz?',
            text: 'Paket silinecek.',
            showCancelButton: true,
            confirmButtonText: 'Sil',
            cancelButtonText: 'İptal'
        });
        if (!confirm.isConfirmed) {
            return;
        }
        try {
            const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'delete-package', id, csrf_token: appConfig.csrfToken })
            });
            const data = await response.json();
            if (data.status !== 'success') {
                throw new Error(data.message || 'Paket silinemedi');
            }
            packages = packages.filter(pkg => pkg.id !== Number(id));
            Swal.fire({ icon: 'success', title: 'Silindi', text: data.message });
            applySearch();
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
        }
    };

    tbody.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button) {
            return;
        }
        const row = button.closest('tr');
        const id = row?.dataset.packageId;
        if (!id) {
            return;
        }
        const pkg = packages.find(item => item.id === Number(id));
        const action = button.getAttribute('data-action');
        if (action === 'edit') {
            openModal(pkg || null);
        } else if (action === 'delete') {
            window.deletePackage(id);
        }
    });

    searchInput?.addEventListener('input', applySearch);

    document.getElementById('newPackageButton')?.addEventListener('click', () => {
        openModal(null, false);
    });

    loadPackages().catch(error => {
        console.error(error);
        Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
    });
})();
