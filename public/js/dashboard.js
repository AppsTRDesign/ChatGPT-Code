const dashboardContent = document.getElementById('dashboardContent');
const dashboardLinks = document.querySelectorAll('#dashboardApp .nav-link');
const socket = io('http://127.0.0.1:4000');

socket.on('connect', () => console.log('Socket connected'));
socket.on('order:new', (payload) => {
    Swal.fire('Yeni Sipariş', `Masa ${payload.table_number} sipariş verdi`, 'info');
    if (currentPage === 'orders') loadOrders();
});
socket.on('order:update', (payload) => {
    Swal.fire('Sipariş Güncellendi', `Sipariş #${payload.order_id} ${payload.status}`, 'info');
    if (currentPage === 'orders') loadOrders();
});

let currentPage = 'orders';

const fetchJSON = async (url, options = {}) => {
    const res = await fetch(url, options);
    const data = await res.json();
    if (!res.ok) throw data;
    return data;
};

const loadOrders = async () => {
    const { orders } = await fetchJSON('/dashboard/orders');
    dashboardContent.innerHTML = `
        <h3>Gelen Siparişler</h3>
        <div class="list-group">
            ${orders.map(order => `
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Masa ${order.table_number}</strong>
                        <div class="small text-muted">${new Date(order.created_at).toLocaleString()}</div>
                        <div class="mt-2"><pre class="bg-light p-2 rounded">${order.items}</pre></div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-${order.status === 'pending' ? 'warning' : (order.status === 'approved' ? 'success' : 'danger')}">${order.status}</span>
                        <div class="btn-group ms-3">
                            <button class="btn btn-sm btn-success" data-action="approved" data-id="${order.id}">Onayla</button>
                            <button class="btn btn-sm btn-danger" data-action="rejected" data-id="${order.id}">Reddet</button>
                        </div>
                    </div>
                </div>
            </div>
            `).join('')}
        </div>
    `;

    dashboardContent.querySelectorAll('button[data-action]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const payload = { order_id: btn.dataset.id, status: btn.dataset.action };
            await fetchJSON('/dashboard/orders', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            loadOrders();
        });
    });
};

const loadMenu = async () => {
    const [categoriesRes, productsRes] = await Promise.all([
        fetchJSON('/dashboard/categories'),
        fetchJSON('/dashboard/products')
    ]);
    dashboardContent.innerHTML = `
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Kategoriler</span>
                        <button class="btn btn-sm btn-primary" id="addCategory">Ekle</button>
                    </div>
                    <div class="card-body">
                        ${(categoriesRes.categories || []).map(cat => `
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <strong>${cat.name}</strong>
                                    <div class="text-muted small">${cat.description || ''}</div>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${cat.id}" data-type="category">Düzenle</button>
                                    <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${cat.id}" data-type="category">Sil</button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Ürünler</span>
                        <button class="btn btn-sm btn-primary" id="addProduct">Ekle</button>
                    </div>
                    <div class="card-body">
                        ${(productsRes.products || []).map(product => `
                            <div class="product-item">
                                <div>
                                    <div class="fw-semibold">${product.name}</div>
                                    <div class="text-muted small">${product.description || ''}</div>
                                    <div class="fw-bold">${product.price} ${product.currency || 'TRY'}</div>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${product.id}" data-type="product">Düzenle</button>
                                    <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${product.id}" data-type="product">Sil</button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('addCategory').addEventListener('click', () => openCategoryModal());
    document.getElementById('addProduct').addEventListener('click', () => openProductModal(categoriesRes.categories));

    dashboardContent.querySelectorAll('button[data-type="category"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const category = categoriesRes.categories.find(c => c.id == btn.dataset.id);
            if (btn.dataset.action === 'edit') {
                openCategoryModal(category);
            } else {
                confirmDelete('/dashboard/categories', { id: category.id });
            }
        });
    });

    dashboardContent.querySelectorAll('button[data-type="product"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const product = productsRes.products.find(p => p.id == btn.dataset.id);
            if (btn.dataset.action === 'edit') {
                openProductModal(categoriesRes.categories, product);
            } else {
                confirmDelete('/dashboard/products', { id: product.id });
            }
        });
    });
};

const openCategoryModal = (category = {}) => {
    Swal.fire({
        title: category.id ? 'Kategoriyi Güncelle' : 'Kategori Ekle',
        html: `
            <input id="catName" class="swal2-input" placeholder="Ad" value="${category.name || ''}">
            <input id="catDescription" class="swal2-input" placeholder="Açıklama" value="${category.description || ''}">
        `,
        preConfirm: async () => {
            const payload = {
                name: document.getElementById('catName').value,
                description: document.getElementById('catDescription').value,
            };
            if (category.id) payload.id = category.id;
            await fetchJSON('/dashboard/categories', {
                method: category.id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
        }
    }).then(result => {
        if (result.isConfirmed) loadMenu();
    });
};

const openProductModal = (categories, product = {}) => {
    Swal.fire({
        title: product.id ? 'Ürünü Güncelle' : 'Ürün Ekle',
        html: `
            <input id="productName" class="swal2-input" placeholder="Ad" value="${product.name || ''}">
            <input id="productDescription" class="swal2-input" placeholder="Açıklama" value="${product.description || ''}">
            <input id="productPrice" class="swal2-input" placeholder="Fiyat" type="number" step="0.01" value="${product.price || ''}">
            <select id="productCategory" class="swal2-select">
                ${categories.map(cat => `<option value="${cat.id}" ${product.category_id == cat.id ? 'selected' : ''}>${cat.name}</option>`).join('')}
            </select>
        `,
        preConfirm: async () => {
            const payload = {
                name: document.getElementById('productName').value,
                description: document.getElementById('productDescription').value,
                price: document.getElementById('productPrice').value,
                category_id: document.getElementById('productCategory').value,
            };
            if (product.id) payload.id = product.id;
            await fetchJSON('/dashboard/products', {
                method: product.id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
        }
    }).then(result => {
        if (result.isConfirmed) loadMenu();
    });
};

const confirmDelete = async (url, payload) => {
    const result = await Swal.fire({
        title: 'Emin misiniz?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet'
    });
    if (result.isConfirmed) {
        await fetchJSON(url, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        loadMenu();
    }
};

const loadReport = async () => {
    const { report } = await fetchJSON('/dashboard/reports', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({})
    });
    dashboardContent.innerHTML = `
        <h3>Satış Raporu</h3>
        <canvas id="reportChart"></canvas>
    `;
    const ctx = document.getElementById('reportChart');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: report.map(r => r.date),
            datasets: [{
                label: 'Sipariş',
                data: report.map(r => r.order_count),
                borderColor: '#1d4ed8',
                backgroundColor: 'rgba(29,78,216,0.3)'
            }, {
                label: 'Gelir',
                data: report.map(r => r.total),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.3)'
            }]
        }
    });
};

const loadTheme = async () => {
    const { theme } = await fetchJSON('/dashboard/theme');
    dashboardContent.innerHTML = `
        <h3>Tema Ayarları</h3>
        <form id="themeForm" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Tema</label>
                <select name="theme" class="form-select">
                    <option value="light" ${theme.theme === 'light' ? 'selected' : ''}>Açık</option>
                    <option value="dark" ${theme.theme === 'dark' ? 'selected' : ''}>Koyu</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Ana Renk</label>
                <input type="color" name="primary_color" class="form-control form-control-color" value="${theme.primary_color}">
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Kaydet</button>
            </div>
        </form>
    `;
    document.getElementById('themeForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(e.target));
        await fetchJSON('/dashboard/theme', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        Swal.fire('Başarılı', 'Tema güncellendi', 'success');
    });
};

const loadQR = async () => {
    const { theme } = await fetchJSON('/dashboard/theme');
    const restaurantSlug = theme.slug;
    const qrUrl = `https://qrcode.noasoft.org/api/v1/qr?token=ff47a9fc9403a50f45662cbef42cb6ca864b8237ff1838f176124ba1201631bf&type=url&url=${window.location.origin}/menu/${restaurantSlug}`;
    dashboardContent.innerHTML = `
        <h3>QR Kodunuz</h3>
        <div class="text-center">
            <img src="${qrUrl}" alt="QR Kod" class="img-fluid" style="max-width: 320px;" />
            <div class="mt-3">
                <a href="${qrUrl}" download="qr-menu.png" class="btn btn-primary">İndir</a>
                <button class="btn btn-outline-secondary" onclick="window.print()">Yazdır</button>
            </div>
        </div>
    `;
};

dashboardLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        dashboardLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');
        currentPage = link.dataset.page;
        if (currentPage === 'orders') loadOrders();
        if (currentPage === 'menu') loadMenu();
        if (currentPage === 'report') loadReport();
        if (currentPage === 'theme') loadTheme();
        if (currentPage === 'qr') loadQR();
    });
});

document.getElementById('logoutBtn')?.addEventListener('click', async () => {
    await fetch('/auth/logout', { method: 'POST' });
    window.location.href = '/';
});

loadOrders();
