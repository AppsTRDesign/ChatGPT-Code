(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const table = document.getElementById('adminUsersTable');
    if (!table) {
        return;
    }

    const tbody = table.querySelector('tbody');
    const searchInput = document.getElementById('adminUsersSearch');
    const modalEl = document.getElementById('userModal');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    const form = document.getElementById('userForm');
    const packageSelect = document.getElementById('userPackage');
    const verifiedInput = document.getElementById('userVerified');

    let users = [];
    let packages = [];

    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const renderPackages = () => {
        if (!packageSelect) {
            return;
        }
        const current = packageSelect.value;
        packageSelect.innerHTML = '<option value="">Seçilmedi</option>' + packages.map(pkg => `
            <option value="${pkg.id}">${escapeHtml(pkg.name)}</option>
        `).join('');
        if (current) {
            packageSelect.value = current;
        }
    };

    const renderUsers = (list) => {
        tbody.innerHTML = '';
        if (!list.length) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 6;
            td.className = 'text-center text-white-50';
            td.textContent = 'Kayıt bulunamadı.';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }
        list.forEach(user => {
            const tr = document.createElement('tr');
            tr.dataset.userId = user.id;
            tr.innerHTML = `
                <td data-label="ID">${user.id}</td>
                <td data-label="Ad Soyad">${escapeHtml(user.name)}</td>
                <td data-label="E-posta">${escapeHtml(user.email)}</td>
                <td data-label="Paket">${escapeHtml(user.package_name || '—')}</td>
                <td data-label="Durum"><span class="badge ${user.email_verified ? 'bg-success' : 'bg-secondary'}">${user.email_verified ? 'Onaylı' : 'Onaysız'}</span></td>
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
            renderUsers(users);
            return;
        }
        const filtered = users.filter(user => {
            const tokens = [user.name, user.email, user.package_name || '', user.role || ''].join(' ').toLowerCase();
            return tokens.includes(query);
        });
        renderUsers(filtered);
    };

    const loadUsers = async () => {
        const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'list-users', csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Kullanıcılar alınamadı');
        }
        users = data.data || [];
        applySearch();
    };

    const loadPackages = async () => {
        const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'list-packages', csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Paketler alınamadı');
        }
        packages = data.data || [];
        renderPackages();
    };

    const openModalForUser = (id) => {
        const user = users.find(item => item.id === Number(id));
        if (!user || !form) {
            return;
        }
        form.reset();
        form.querySelector('#userId').value = user.id;
        form.querySelector('#userName').value = user.name;
        form.querySelector('#userEmail').value = user.email;
        form.querySelector('#userRole').value = user.role;
        if (packageSelect) {
            packageSelect.value = user.package_id || '';
        }
        if (verifiedInput) {
            verifiedInput.checked = user.email_verified === 1;
        }
        modal?.show();
    };

    window.saveUser = async () => {
        if (!form) {
            return;
        }
        const formData = new FormData(form);
        formData.append('action', 'update-user');
        formData.append('csrf_token', appConfig.csrfToken);
        const verified = verifiedInput?.checked ? 1 : 0;
        try {
            const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.status !== 'success') {
                throw new Error(data.message || 'Kayıt güncellenemedi');
            }
            await fetch(`${appConfig.baseUrl}/api/admin.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'verify-user', id: formData.get('id'), verified, csrf_token: appConfig.csrfToken })
            });
            Swal.fire({ icon: 'success', title: 'Kaydedildi', text: data.message });
            modal?.hide();
            await loadUsers();
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
        }
    };

    window.deleteUser = async (id) => {
        const confirm = await Swal.fire({
            icon: 'warning',
            title: 'Emin misiniz?',
            text: 'Üye kalıcı olarak silinecek.',
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
                body: JSON.stringify({ action: 'delete-user', id, csrf_token: appConfig.csrfToken })
            });
            const data = await response.json();
            if (data.status !== 'success') {
                throw new Error(data.message || 'Silinemedi');
            }
            users = users.filter(user => user.id !== Number(id));
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
        const id = row?.dataset.userId;
        if (!id) {
            return;
        }
        const action = button.getAttribute('data-action');
        if (action === 'edit') {
            openModalForUser(id);
        } else if (action === 'delete') {
            window.deleteUser(id);
        }
    });

    searchInput?.addEventListener('input', applySearch);

    Promise.all([loadPackages(), loadUsers()]).catch(error => {
        console.error(error);
        Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
    });
})();
