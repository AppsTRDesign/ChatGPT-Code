const adminContent = document.getElementById('adminContent');
const adminLinks = document.querySelectorAll('#adminApp .nav-link');

const parseJSONResponse = async (response) => {
    const text = await response.text();
    let data = {};
    if (text) {
        try {
            data = JSON.parse(text);
        } catch (error) {
            console.error('JSON parse error', error, text);
            throw { error: 'Sunucudan beklenmeyen cevap alındı' };
        }
    }
    if (!response.ok) {
        throw data.error ? data : { error: 'İstek başarısız' };
    }
    return data;
};

const renderDashboard = async () => {
    try {
        const res = await fetch('/admin/dashboard');
        if (!res.ok) return;
        const { stats } = await parseJSONResponse(res);
    adminContent.innerHTML = `
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>Restoran</h5>
                        <p class="display-6">${stats.restaurants}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>Bugün Sipariş</h5>
                        <p class="display-6">${stats.orders_today}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5>Kullanıcı</h5>
                        <p class="display-6">${stats.users}</p>
                    </div>
                </div>
            </div>
        </div>
        <canvas id="planChart" class="mt-4"></canvas>
    `;
    const ctx = document.getElementById('planChart');
    const planLabels = stats.plans.map(plan => plan.name);
    const planData = stats.plans.map(plan => plan.restaurant_count || 0);
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: planLabels,
            datasets: [{
                data: planData,
                backgroundColor: ['#1d4ed8', '#10b981', '#f59e0b']
            }]
        }
    });
    } catch (error) {
        const message = error?.error || error?.message || 'Gösterge paneli yüklenemedi';
        Swal.fire('Hata', message, 'error');
    }
};

const renderRestaurants = async () => {
    try {
        const res = await fetch('/admin/restaurants');
        const data = await parseJSONResponse(res);
    adminContent.innerHTML = `
        <h3>Restoranlar</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Ad</th><th>Plan</th><th>Durum</th><th>İşlem</th></tr></thead>
                <tbody>
                ${data.restaurants.map(r => `
                    <tr>
                        <td>${r.name}<div class="text-muted small">${r.email}</div></td>
                        <td>${r.plan || 'Ücretsiz'}</td>
                        <td><span class="badge bg-${r.status === 'active' ? 'success' : 'warning'}">${r.status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-success" data-action="approve" data-restaurant="${r.id}" data-user="${r.user_id}">Onayla</button>
                            <button class="btn btn-sm btn-danger" data-action="delete" data-restaurant="${r.id}">Sil</button>
                        </td>
                    </tr>
                `).join('')}
                </tbody>
            </table>
        </div>
    `;

        adminContent.querySelectorAll('button[data-action]').forEach(btn => {
            btn.addEventListener('click', async () => {
                try {
                    const action = btn.dataset.action;
                    const payload = { restaurant_id: btn.dataset.restaurant };
                    let url = '';
                    if (action === 'approve') {
                        payload.user_id = btn.dataset.user;
                        url = '/admin/restaurants/approve';
                    } else {
                        url = '/admin/restaurants/delete';
                    }
                    const res = await fetch(url, {
                        method: action === 'delete' ? 'DELETE' : 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const result = await parseJSONResponse(res);
                    Swal.fire('Bilgi', result.message, 'success');
                    renderRestaurants();
                } catch (error) {
                    const message = error?.error || error?.message || 'İşlem başarısız';
                    Swal.fire('Hata', message, 'error');
                }
            });
        });
    } catch (error) {
        const message = error?.error || error?.message || 'Restoran listesi yüklenemedi';
        Swal.fire('Hata', message, 'error');
    }
};

const renderSettings = async () => {
    try {
        const res = await fetch('/admin/settings');
        const data = await parseJSONResponse(res);
    const settings = Object.fromEntries(data.settings.map(item => [item.key, item.value]));
    adminContent.innerHTML = `
        <h3>Genel Ayarlar</h3>
        <form id="settingsForm" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Dil</label>
                <input type="text" class="form-control" name="language" value="${settings.language || 'tr'}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Para Birimi</label>
                <input type="text" class="form-control" name="currency" value="${settings.currency || 'TRY'}">
            </div>
            <div class="col-md-12">
                <label class="form-label">Logo URL</label>
                <input type="text" class="form-control" name="logo" value="${settings.logo || ''}">
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Kaydet</button>
            </div>
        </form>
    `;

        document.getElementById('settingsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = Object.fromEntries(new FormData(e.target));
            try {
                const res = await fetch('/admin/settings', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await parseJSONResponse(res);
                Swal.fire('Bilgi', result.message, 'success');
            } catch (error) {
                const message = error?.error || error?.message || 'Ayarlar kaydedilemedi';
                Swal.fire('Hata', message, 'error');
            }
        });
    } catch (error) {
        const message = error?.error || error?.message || 'Ayarlar yüklenemedi';
        Swal.fire('Hata', message, 'error');
    }
};

adminLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        adminLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');
        const page = link.dataset.page;
        if (page === 'dashboard') renderDashboard();
        if (page === 'restaurants') renderRestaurants();
        if (page === 'settings') renderSettings();
    });
});

document.getElementById('logoutBtn')?.addEventListener('click', async () => {
    await fetch('/auth/logout', { method: 'POST' });
    window.location.href = '/';
});

renderDashboard();
