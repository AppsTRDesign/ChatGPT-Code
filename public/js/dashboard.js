const dashboardContent = document.getElementById('dashboardContent');
const dashboardLinks = document.querySelectorAll('#dashboardApp .nav-link');
const dashboardContext = window.dashboardContext || {};
const socketUrl = dashboardContext.socketUrl || '';
const socketRestaurantId = dashboardContext.restaurantId ? String(dashboardContext.restaurantId) : '';
const socket = socketUrl ? io(socketUrl, { auth: { restaurantId: socketRestaurantId } }) : null;

if (socket) {
    socket.on('connect', () => {
        console.log('Socket connected');
        if (socketRestaurantId) {
            socket.emit('registerRestaurant', { restaurantId: socketRestaurantId });
        }
    });
    socket.on('order:new', (payload) => {
        if (socketRestaurantId && String(payload.restaurant_id) !== socketRestaurantId) return;
        Swal.fire('Yeni Sipariş', `Masa ${payload.table_number} sipariş verdi`, 'info');
        if (currentPage === 'orders') loadOrders();
    });
    socket.on('order:update', (payload) => {
        if (socketRestaurantId && String(payload.restaurant_id) !== socketRestaurantId) return;
        Swal.fire('Sipariş Güncellendi', `Sipariş #${payload.order_id} ${payload.status}`, 'info');
        if (currentPage === 'orders') loadOrders();
    });
}

let currentPage = 'orders';

const fetchJSON = async (url, options = {}) => {
    const res = await fetch(url, options);
    const data = await res.json();
    if (!res.ok) throw data;
    return data;
};

const uploadProductImage = async (file) => {
    const formData = new FormData();
    formData.append('image', file);
    const res = await fetch('/dashboard/products/upload', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    if (!res.ok) {
        throw new Error(data.error || 'Yükleme başarısız');
    }
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
    let selectedFile = null;
    let previewUrl = null;
    Swal.fire({
        title: product.id ? 'Ürünü Güncelle' : 'Ürün Ekle',
        html: `
            <input id="productName" class="swal2-input" placeholder="Ad" value="${product.name || ''}">
            <input id="productDescription" class="swal2-input" placeholder="Açıklama" value="${product.description || ''}">
            <input id="productPrice" class="swal2-input" placeholder="Fiyat" type="number" step="0.01" value="${product.price || ''}">
            <select id="productCategory" class="swal2-select">
                ${categories.map(cat => `<option value="${cat.id}" ${product.category_id == cat.id ? 'selected' : ''}>${cat.name}</option>`).join('')}
            </select>
            <div class="upload-dropzone mt-3" id="productDropzone">
                <input type="file" accept="image/*" id="productFileInput" class="d-none" />
                <div id="productDropzoneText">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <p class="mb-0">Görseli buraya sürükleyin veya tıklayın</p>
                </div>
                <img id="productPreview" class="img-fluid rounded mt-2 d-none" alt="Önizleme" />
            </div>
        `,
        focusConfirm: false,
        didOpen: () => {
            const popup = Swal.getPopup();
            const dropzone = popup.querySelector('#productDropzone');
            const fileInput = popup.querySelector('#productFileInput');
            const preview = popup.querySelector('#productPreview');
            const text = popup.querySelector('#productDropzoneText');

            const updatePreview = (url) => {
                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                    previewUrl = null;
                }
                if (url) {
                    preview.src = url;
                    preview.classList.remove('d-none');
                    text.classList.add('d-none');
                    previewUrl = url;
                } else {
                    preview.src = '';
                    preview.classList.add('d-none');
                    text.classList.remove('d-none');
                }
            };

            const handleFiles = (files) => {
                if (!files || !files.length) return;
                const file = files[0];
                if (!file.type.startsWith('image/')) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Lütfen bir görsel dosyası seçin',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true
                    });
                    return;
                }
                selectedFile = file;
                updatePreview(URL.createObjectURL(file));
            };

            if (product.image_url) {
                preview.src = product.image_url;
                preview.classList.remove('d-none');
                text.classList.add('d-none');
            }

            dropzone.addEventListener('click', () => fileInput.click());
            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropzone.classList.add('is-dragover');
            });
            dropzone.addEventListener('dragleave', () => dropzone.classList.remove('is-dragover'));
            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzone.classList.remove('is-dragover');
                handleFiles(e.dataTransfer.files);
            });
            fileInput.addEventListener('change', (e) => handleFiles(e.target.files));
        },
        willClose: () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }
        },
        preConfirm: async () => {
            const name = document.getElementById('productName').value.trim();
            const description = document.getElementById('productDescription').value.trim();
            const price = document.getElementById('productPrice').value;
            const categoryId = document.getElementById('productCategory').value;

            if (!name || !price) {
                Swal.showValidationMessage('Ad ve fiyat zorunludur');
                return false;
            }

            const payload = {
                name,
                description,
                price,
                category_id: categoryId,
                image_url: product.image_url || null
            };

            if (selectedFile) {
                try {
                    const upload = await uploadProductImage(selectedFile);
                    payload.image_url = upload.url;
                } catch (error) {
                    Swal.showValidationMessage(error.message || 'Yükleme başarısız');
                    return false;
                }
            }

            if (product.id) payload.id = product.id;
            try {
                await fetchJSON('/dashboard/products', {
                    method: product.id ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
            } catch (error) {
                const message = error?.error || error?.message || 'İşlem başarısız';
                Swal.showValidationMessage(message);
                return false;
            }
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
