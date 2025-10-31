Dropzone.autoDiscover = false;

const AdminApp = (() => {
    const state = {
        summary: {},
        chart: null,
        period: 'weekly',
        settings: window.APP_STATE?.settings || {},
        qrPreview: window.APP_STATE?.qrPreview || '',
        orders: [],
        orderStatus: 'all',
        orderSearch: '',
        orderPage: 1,
        tables: [],
        tableSearch: '',
        waiterCalls: [],
        waiterSearch: '',
        categories: [],
        products: [],
        productSearch: '',
        productCategory: 'all',
    };

    const ICON_LIBRARY = [
        'bx-coffee', 'bx-bowl-hot', 'bx-cake', 'bx-dish', 'bx-pizza', 'bx-baguette', 'bx-beer', 'bx-bowl-rice',
        'bx-dots-horizontal', 'bx-cube-alt', 'bx-water', 'bx-ice-cream', 'bx-restaurant', 'bx-food-menu', 'bx-sushi', 'bx-lemon',
        'bx-doughnut', 'bx-bone', 'bx-burger', 'bx-cheese', 'bx-cupcake', 'bx-wine', 'bx-fridge', 'bx-hot', 'bx-bowl',
        'bx-chilli', 'bx-bread', 'bx-croissant', 'bx-cocktail', 'bx-knife', 'bx-raspberry', 'bx-shrimp', 'bx-taco'
    ];

    const ORDER_STATUSES = ['Beklemede', 'Hazırlanıyor', 'Hazırlandı', 'Ödeme Alındı', 'İptal'];

    const selectors = {
        sections: document.querySelectorAll('.section'),
        navButtons: document.querySelectorAll('.dashboard__link'),
        summaryCards: document.querySelectorAll('[data-summary]'),
        chartCanvas: document.querySelector('#ordersChart'),
        orderStatusFilters: document.querySelectorAll('#orderStatusFilters button'),
        orderSearch: document.querySelector('#orderSearch'),
        ordersContainer: document.querySelector('#ordersContainer'),
        ordersPagination: document.querySelector('#ordersPagination'),
        tableSearch: document.querySelector('#tableSearch'),
        tablesContainer: document.querySelector('#tablesContainer'),
        newTableButton: document.querySelector('#newTableButton'),
        waiterSearch: document.querySelector('#waiterSearch'),
        waiterContainer: document.querySelector('#waiterContainer'),
        newCategoryButton: document.querySelector('#newCategoryButton'),
        saveCategory: document.querySelector('#saveCategory'),
        deleteCategoryButton: document.querySelector('#deleteCategoryButton'),
        categoryForm: document.querySelector('#categoryForm'),
        categoryList: document.querySelector('#categoryList'),
        productList: document.querySelector('#productList'),
        productForm: document.querySelector('#productForm'),
        saveProduct: document.querySelector('#saveProduct'),
        deleteProductButton: document.querySelector('#deleteProductButton'),
        newProductButton: document.querySelector('#newProductButton'),
        productCategoryFilter: document.querySelector('#productCategoryFilter'),
        productSearch: document.querySelector('#productSearch'),
        variantList: document.querySelector('#variantList'),
        addVariant: document.querySelector('#addVariant'),
        iconLibrary: document.querySelector('#iconLibrary'),
        generalSettingsForm: document.querySelector('#generalSettingsForm'),
        brandingForm: document.querySelector('#brandingForm'),
        qrForm: document.querySelector('#qrForm'),
        qrPreviewImage: document.querySelector('#qrPreviewImage'),
        logoutButton: document.querySelector('#logoutButton'),
        languageModal: document.querySelector('#languageModal'),
        languageForm: document.querySelector('#languageForm'),
        saveLanguage: document.querySelector('#saveLanguage'),
        currencyModal: document.querySelector('#currencyModal'),
        currencyForm: document.querySelector('#currencyForm'),
        saveCurrency: document.querySelector('#saveCurrency'),
        tableModal: document.querySelector('#tableModal'),
        tableForm: document.querySelector('#tableForm'),
        saveTable: document.querySelector('#saveTable'),
        deleteTableButton: document.querySelector('#deleteTableButton'),
        orderModal: document.querySelector('#orderModal'),
        orderModalContent: document.querySelector('#orderModalContent'),
        audioOrder: document.querySelector('#audioOrderAdmin'),
        audioNotify: document.querySelector('#audioNotifyAdmin'),
    };

    const toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4200,
    });

    const socket = io('https://qrmenu.noasoft.org:4000');

    const fetchJSON = async (url, options = {}) => {
        const response = await fetch(url, options);
        const data = await response.json();
        if (data.error) {
            throw new Error(data.message || 'Bilinmeyen bir hata oluştu.');
        }
        return data;
    };

    const statusToClass = (status = '') => {
        return status
            .toString()
            .replace(/İ/g, 'i')
            .replace(/ı/g, 'i')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    };

    const playAudio = (audioElement) => {
        if (!audioElement) return;
        audioElement.currentTime = 0;
        audioElement.play().catch(() => {});
    };

    const switchSection = (section) => {
        selectors.sections.forEach((element) => {
            element.classList.toggle('d-none', element.id !== `section-${section}`);
        });
    };

    const bindNavigation = () => {
        selectors.navButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                selectors.navButtons.forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                switchSection(button.dataset.section);
            });
        });
    };

    const renderChart = (chartData) => {
        if (!selectors.chartCanvas || !chartData) {
            return;
        }
        if (state.chart) {
            state.chart.destroy();
        }
        const datasets = (chartData.datasets || []).map((dataset) => ({
            ...dataset,
            borderRadius: 12,
            stack: 'stack1',
        }));
        state.chart = new Chart(selectors.chartCanvas, {
            type: 'bar',
            data: {
                labels: chartData.labels || [],
                datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true },
                    y: { stacked: true },
                },
                plugins: {
                    legend: { position: 'bottom' },
                },
            },
        });
    };

    const fetchDashboard = async () => {
        const data = await fetchJSON(`api/dashboard.php?period=${state.period}`);
        state.summary = data.summary || {};
        selectors.summaryCards.forEach((card) => {
            const key = card.dataset.summary;
            card.querySelector('strong').innerText = state.summary[key] ?? 0;
        });
        renderChart(data.charts);
    };

    const renderOrders = () => {
        if (!selectors.ordersContainer) return;
        const perPage = 6;
        const search = state.orderSearch.trim().toLowerCase();
        const filtered = state.orders.filter((order) => {
            if (state.orderStatus !== 'all' && order.status !== state.orderStatus) {
                return false;
            }
            if (!search) return true;
            return `${order.id}`.includes(search) || (order.table || '').toLowerCase().includes(search);
        });

        const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
        state.orderPage = Math.min(state.orderPage, totalPages);
        const start = (state.orderPage - 1) * perPage;
        const paginated = filtered.slice(start, start + perPage);

        selectors.ordersContainer.innerHTML = '';
        paginated.forEach((order) => {
            const card = document.createElement('div');
            card.className = 'order-card';
            const statusClass = `badge status-${statusToClass(order.status || 'beklemede')}`;
            const itemsHtml = (order.items || []).map((item) => `
                <li>
                    <span>${item.name}${item.variant_name ? ` <small>${item.variant_name}</small>` : ''}</span>
                    <span>${item.quantity} x ${item.unit_price_formatted || ''}</span>
                </li>
            `).join('');

            card.innerHTML = `
                <div class="order-card__head">
                    <div>
                        <h3>#${order.id}</h3>
                        <p>${order.table || 'Belirtilmedi'}</p>
                    </div>
                    <div class="text-end">
                        <span class="${statusClass}">${order.status}</span>
                        <p class="order-card__time">${order.created_at || ''}</p>
                    </div>
                </div>
                <ul class="order-card__items">${itemsHtml}</ul>
                <div class="order-card__footer">
                    <div>
                        <strong>${order.total_formatted || ''}</strong>
                        <small class="d-block text-muted">Ürün sayısı: ${(order.items || []).length}</small>
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <select class="form-select form-select-sm" data-order-status="${order.id}">
                            ${ORDER_STATUSES.map((status) => `<option value="${status}" ${order.status === status ? 'selected' : ''}>${status}</option>`).join('')}
                        </select>
                        <button class="btn btn-sm btn-outline-secondary" data-order-detail="${order.id}">Detay</button>
                        <button class="btn btn-sm btn-outline-primary" data-order-export="pdf" data-order="${order.id}">Adisyon</button>
                        <button class="btn btn-sm btn-outline-success" data-order-export="thermal" data-order="${order.id}">Yazar Kasa</button>
                    </div>
                </div>
            `;
            selectors.ordersContainer.appendChild(card);
        });

        renderPagination(selectors.ordersPagination, totalPages, state.orderPage, (page) => {
            state.orderPage = page;
            renderOrders();
        });
    };

    const fetchOrders = async () => {
        const query = new URLSearchParams({ status: state.orderStatus });
        if (state.orderSearch) {
            query.set('search', state.orderSearch);
        }
        const data = await fetchJSON(`api/orders.php?${query.toString()}`);
        state.orders = data.orders || [];
        renderOrders();
    };

    const updateOrderStatus = async (orderId, status) => {
        const data = await fetchJSON('api/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: orderId, status }),
        });
        toast.fire({ icon: 'success', title: data.message });
        await fetchOrders();
        await fetchTables();
        await fetchDashboard();
        socket.emit('order:update', { order: orderId, status, table: data.order?.table || '', table_id: data.order?.table_id || null });
    };

    const openOrderModal = (orderId) => {
        const order = state.orders.find((item) => Number(item.id) === Number(orderId));
        if (!order || !selectors.orderModalContent) return;
        selectors.orderModalContent.innerHTML = `
            <div class="order-modal">
                <h3 class="mb-3">#${order.id} - ${order.table}</h3>
                <p><span class="badge status-${statusToClass(order.status)}">${order.status}</span> • ${order.created_at}</p>
                <ul class="order-card__items mt-3">
                    ${(order.items || []).map((item) => `
                        <li>
                            <div>
                                <strong>${item.name}</strong>
                                ${item.variant_name ? `<small>${item.variant_name}</small>` : ''}
                            </div>
                            <div>${item.quantity} x ${item.unit_price_formatted}</div>
                        </li>
                    `).join('')}
                </ul>
                <div class="d-flex justify-content-between mt-3">
                    <span>Toplam:</span>
                    <strong>${order.total_formatted}</strong>
                </div>
            </div>
        `;
        const modal = bootstrap.Modal.getOrCreateInstance(selectors.orderModal);
        modal.show();
    };

    const renderTables = () => {
        if (!selectors.tablesContainer) return;
        const search = state.tableSearch.trim().toLowerCase();
        selectors.tablesContainer.innerHTML = '';
        state.tables
            .filter((table) => {
                if (!search) return true;
                return table.name.toLowerCase().includes(search) || `${table.id}`.includes(search);
            })
            .forEach((table) => {
                const card = document.createElement('div');
                card.className = 'table-card';
                const ordersHtml = (table.active_orders || []).map((order) => `
                    <li>
                        <span>#${order.id} - ${order.status}</span>
                        <span>${order.total_formatted || Number(order.total).toFixed(2)}</span>
                    </li>
                `).join('');

                card.innerHTML = `
                    <div class="table-card__head">
                        <div>
                            <h3>${table.name}</h3>
                            <span class="badge status-${statusToClass(table.status)}">${table.status_label}</span>
                        </div>
                        <img src="${table.qr_code_url}" alt="QR" loading="lazy">
                    </div>
                    <div class="table-card__body">
                        <p class="text-muted">Bağlantı: <a href="${table.qr_url}" target="_blank" rel="noopener">${table.qr_url}</a></p>
                        <div class="active-orders">
                            <strong>Aktif Siparişler</strong>
                            <ul>${ordersHtml || '<li>Aktif sipariş yok.</li>'}</ul>
                        </div>
                    </div>
                    <div class="table-card__footer d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-outline-primary" data-table-edit="${table.id}">Düzenle</button>
                        <button class="btn btn-sm btn-outline-secondary" data-copy="${table.qr_url}">Link Kopyala</button>
                        <a class="btn btn-sm btn-outline-success" href="${table.qr_code_url}" target="_blank" rel="noopener">QR Görüntüle</a>
                    </div>
                `;
                selectors.tablesContainer.appendChild(card);
            });
    };

    const fetchTables = async () => {
        const data = await fetchJSON('api/tables.php');
        state.tables = data.tables || [];
        renderTables();
    };

    const renderWaiterCalls = () => {
        if (!selectors.waiterContainer) return;
        const search = state.waiterSearch.trim().toLowerCase();
        selectors.waiterContainer.innerHTML = '';
        state.waiterCalls
            .filter((call) => {
                if (!search) return true;
                return (call.table || '').toLowerCase().includes(search) || (call.status || '').toLowerCase().includes(search);
            })
            .forEach((call) => {
                const item = document.createElement('div');
                item.className = 'waiter-item';
                item.innerHTML = `
                    <div>
                        <h3>${call.table}</h3>
                        <small>${call.created_at}</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge status-${statusToClass(call.status)}">${call.status_label}</span>
                        <select class="form-select form-select-sm" data-waiter-status="${call.id}">
                            <option value="waiting" ${call.status === 'waiting' ? 'selected' : ''}>Beklemede</option>
                            <option value="on_the_way" ${call.status === 'on_the_way' ? 'selected' : ''}>Yolda</option>
                            <option value="completed" ${call.status === 'completed' ? 'selected' : ''}>Tamamlandı</option>
                        </select>
                    </div>
                `;
                selectors.waiterContainer.appendChild(item);
            });
    };

    const fetchWaiterCalls = async () => {
        const data = await fetchJSON('api/waiter-calls.php');
        state.waiterCalls = data.calls || [];
        renderWaiterCalls();
    };

    const updateWaiterStatus = async (id, status) => {
        const data = await fetchJSON('api/waiter-calls.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status }),
        });
        toast.fire({ icon: 'success', title: data.message });
        await fetchWaiterCalls();
        await fetchTables();
        socket.emit('waiter:update', { table: data.call?.table || '', status: data.call?.status_label || status, table_id: data.call?.table_id || null });
    };

    const renderCategories = () => {
        if (!selectors.categoryList) return;
        selectors.categoryList.innerHTML = '';
        state.categories.forEach((category) => {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'category-admin-card';
            const iconHtml = category.image ? `<img src="${category.image}" alt="${category.name}"/>` : `<i class='bx ${category.icon || 'bx-dots-horizontal'}'></i>`;
            card.innerHTML = `
                ${iconHtml}
                <span>${category.name}</span>
                <small>Düzenle</small>
            `;
            card.addEventListener('click', () => openCategoryModal(category.id));
            selectors.categoryList.appendChild(card);
        });
    };

    const fetchCategories = async () => {
        const data = await fetchJSON('api/categories.php');
        state.categories = data.categories || [];
        renderCategories();
        renderCategoryFilter();
    };

    const renderCategoryFilter = () => {
        if (!selectors.productCategoryFilter) return;
        selectors.productCategoryFilter.innerHTML = '<option value="all">Tüm Kategoriler</option>' +
            state.categories.map((category) => `<option value="${category.id}">${category.name}</option>`).join('');
        const select = selectors.productForm?.querySelector('select[name="category_id"]');
        if (select) {
            select.innerHTML = state.categories.map((category) => `<option value="${category.id}">${category.name}</option>`).join('');
        }
    };

    const renderProducts = () => {
        if (!selectors.productList) return;
        const search = state.productSearch.trim().toLowerCase();
        selectors.productList.innerHTML = '';
        state.products
            .filter((product) => {
                if (state.productCategory !== 'all' && Number(product.category_id) !== Number(state.productCategory)) {
                    return false;
                }
                if (!search) return true;
                return product.name.toLowerCase().includes(search) || (product.description || '').toLowerCase().includes(search);
            })
            .forEach((product) => {
                const card = document.createElement('div');
                card.className = 'product-admin-card';
                card.innerHTML = `
                    <img src="${product.image || 'assets/vendor/demo/coffee-1.png'}" alt="${product.name}" loading="lazy">
                    <div class="product-admin-card__content">
                        <h3>${product.name}</h3>
                        <p class="text-muted">${product.description || ''}</p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-light text-dark">${product.category_name || ''}</span>
                            <strong>${Number(product.price).toFixed(2)}</strong>
                        </div>
                        <ul class="variant-mini">
                            ${(product.variants || []).map((variant) => `<li>${variant.name} - ${Number(variant.price).toFixed(2)}</li>`).join('') || '<li>Varyasyon eklenmedi.</li>'}
                        </ul>
                    </div>
                    <div class="product-admin-card__actions">
                        <button class="btn btn-sm btn-outline-primary" data-product-edit="${product.id}">Düzenle</button>
                        <button class="btn btn-sm btn-outline-danger" data-product-delete="${product.id}">Sil</button>
                    </div>
                `;
                selectors.productList.appendChild(card);
            });
    };

    const fetchProducts = async () => {
        const data = await fetchJSON('api/products.php');
        state.products = data.products || [];
        renderProducts();
    };

    const openCategoryModal = (id = null) => {
        const modal = bootstrap.Modal.getOrCreateInstance('#categoryModal');
        selectors.categoryForm.reset();
        selectors.categoryForm.querySelector('[name="id"]').value = id || '';
        selectors.deleteCategoryButton.classList.toggle('d-none', !id);
        const category = state.categories.find((item) => Number(item.id) === Number(id));
        if (category) {
            selectors.categoryForm.querySelector('[name="name"]').value = category.name;
            selectors.categoryForm.querySelector('[name="icon"]').value = category.icon || '';
            selectors.categoryForm.querySelector('[name="image"]').value = category.image || '';
        }
        renderIconLibrary(category?.icon || '');
        modal.show();
    };

    const renderIconLibrary = (selected) => {
        if (!selectors.iconLibrary) return;
        selectors.iconLibrary.innerHTML = '';
        ICON_LIBRARY.forEach((icon) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `icon-library__item ${selected === icon ? 'active' : ''}`;
            button.innerHTML = `<i class='bx ${icon}'></i>`;
            button.addEventListener('click', () => {
                selectors.iconLibrary.querySelectorAll('.icon-library__item').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                selectors.categoryForm.querySelector('[name="icon"]').value = icon;
            });
            selectors.iconLibrary.appendChild(button);
        });
    };

    const saveCategory = async () => {
        const formData = new FormData(selectors.categoryForm);
        const payload = Object.fromEntries(formData.entries());
        const data = await fetchJSON('api/categories.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        state.categories = data.categories || [];
        renderCategories();
        renderCategoryFilter();
        toast.fire({ icon: 'success', title: data.message });
        bootstrap.Modal.getInstance('#categoryModal')?.hide();
    };

    const deleteCategory = async () => {
        const id = selectors.categoryForm.querySelector('[name="id"]').value;
        if (!id) return;
        const confirmResult = await Swal.fire({
            title: 'Emin misiniz?',
            text: 'Kategori silinecek.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sil',
            cancelButtonText: 'Vazgeç',
        });
        if (!confirmResult.isConfirmed) return;
        const data = await fetchJSON(`api/categories.php?id=${id}`, { method: 'DELETE' });
        state.categories = data.categories || [];
        renderCategories();
        renderCategoryFilter();
        toast.fire({ icon: 'success', title: data.message });
        bootstrap.Modal.getInstance('#categoryModal')?.hide();
    };

    const openProductModal = (id = null) => {
        const modal = bootstrap.Modal.getOrCreateInstance('#productModal');
        selectors.productForm.reset();
        selectors.productForm.querySelector('[name="id"]').value = id || '';
        selectors.deleteProductButton.classList.toggle('d-none', !id);
        renderCategoryFilter();
        selectors.variantList.innerHTML = '';
        const product = state.products.find((item) => Number(item.id) === Number(id));
        if (product) {
            selectors.productForm.querySelector('[name="category_id"]').value = product.category_id;
            selectors.productForm.querySelector('[name="name"]').value = product.name;
            selectors.productForm.querySelector('[name="description"]').value = product.description || '';
            selectors.productForm.querySelector('[name="price"]').value = Number(product.price).toFixed(2);
            selectors.productForm.querySelector('[name="image"]').value = product.image || '';
            (product.variants || []).forEach((variant) => addVariantRow(variant));
        } else {
            addVariantRow();
        }
        modal.show();
    };

    const addVariantRow = (variant = {}) => {
        const row = document.createElement('div');
        row.className = 'variant-row';
        row.innerHTML = `
            <input type="hidden" name="variant_id" value="${variant.id || ''}">
            <input type="text" class="form-control" name="variant_name" placeholder="Örn. Büyük" value="${variant.name || ''}">
            <input type="number" step="0.01" min="0" class="form-control" name="variant_price" placeholder="Fiyat" value="${variant.price ? Number(variant.price).toFixed(2) : ''}">
            <button type="button" class="btn btn-sm btn-outline-danger">Sil</button>
        `;
        row.querySelector('button').addEventListener('click', () => row.remove());
        selectors.variantList.appendChild(row);
    };

    const collectVariants = () => {
        return Array.from(selectors.variantList.querySelectorAll('.variant-row')).map((row) => {
            return {
                id: row.querySelector('input[name="variant_id"]').value || null,
                name: row.querySelector('input[name="variant_name"]').value,
                price: row.querySelector('input[name="variant_price"]').value,
            };
        }).filter((variant) => variant.name && variant.price);
    };

    const saveProduct = async () => {
        const formData = new FormData(selectors.productForm);
        const payload = Object.fromEntries(formData.entries());
        payload.variants = collectVariants();
        const data = await fetchJSON('api/products.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        state.products = data.products || [];
        renderProducts();
        toast.fire({ icon: 'success', title: data.message });
        bootstrap.Modal.getInstance('#productModal')?.hide();
    };

    const deleteProduct = async (id) => {
        const confirmResult = await Swal.fire({
            title: 'Emin misiniz?',
            text: 'Ürün silinecek.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sil',
            cancelButtonText: 'Vazgeç',
        });
        if (!confirmResult.isConfirmed) return;
        const data = await fetchJSON(`api/products.php?id=${id}`, { method: 'DELETE' });
        state.products = data.products || [];
        renderProducts();
        toast.fire({ icon: 'success', title: data.message });
    };

    const openTableModal = (id = null) => {
        const modal = bootstrap.Modal.getOrCreateInstance('#tableModal');
        selectors.tableForm.reset();
        selectors.tableForm.querySelector('[name="id"]').value = id || '';
        const table = state.tables.find((item) => Number(item.id) === Number(id));
        if (table) {
            selectors.tableForm.querySelector('[name="name"]').value = table.name;
            selectors.tableForm.querySelector('[name="status"]').value = table.status;
        }
        selectors.deleteTableButton.dataset.id = id || '';
        selectors.deleteTableButton.classList.toggle('d-none', !id);
        modal.show();
    };

    const saveTable = async () => {
        const formData = new FormData(selectors.tableForm);
        const payload = Object.fromEntries(formData.entries());
        const data = await fetchJSON('api/tables.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        state.tables = data.tables || [];
        renderTables();
        toast.fire({ icon: 'success', title: data.message });
        bootstrap.Modal.getInstance('#tableModal')?.hide();
    };

    const deleteTable = async () => {
        const id = selectors.deleteTableButton.dataset.id;
        if (!id) return;
        const confirmResult = await Swal.fire({
            title: 'Masa silinsin mi?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sil',
            cancelButtonText: 'Vazgeç',
        });
        if (!confirmResult.isConfirmed) return;
        const data = await fetchJSON(`api/tables.php?id=${id}`, { method: 'DELETE' });
        state.tables = data.tables || [];
        renderTables();
        toast.fire({ icon: 'success', title: data.message });
        bootstrap.Modal.getInstance('#tableModal')?.hide();
    };

    const handleCopy = (text) => {
        navigator.clipboard.writeText(text).then(() => {
            toast.fire({ icon: 'success', title: 'Bağlantı kopyalandı.' });
        });
    };

    const initDropzones = () => {
        document.querySelectorAll('[data-dropzone]').forEach((element) => {
            const dz = new Dropzone(element, {
                url: 'api/upload.php',
                paramName: 'file',
                maxFiles: 1,
                acceptedFiles: 'image/*',
                addRemoveLinks: true,
                dictDefaultMessage: element.dataset.placeholder || 'Dosyayı buraya bırakın',
            });

            dz.on('success', (file, response) => {
                if (!response.success) {
                    toast.fire({ icon: 'error', title: response.message || 'Dosya yüklenemedi.' });
                    return;
                }
                const target = document.querySelector(element.dataset.target);
                if (target) {
                    const useAbsolute = element.dataset.absolute === 'true';
                    target.value = useAbsolute ? response.url : response.path;
                }
                if (element.dataset.preview) {
                    const preview = document.querySelector(element.dataset.preview);
                    if (preview) {
                        preview.src = response.url || response.path;
                    }
                }
                toast.fire({ icon: 'success', title: 'Dosya yüklendi.' });
            });

            dz.on('error', () => {
                toast.fire({ icon: 'error', title: 'Dosya yüklenirken hata oluştu.' });
            });
        });
    };

    const handleSettingsSubmit = (form, builder, onSuccess) => {
        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const payload = builder(new FormData(form));
                const data = await fetchJSON('api/settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                state.settings = data.settings || {};
                toast.fire({ icon: 'success', title: data.message });
                if (typeof onSuccess === 'function') {
                    await onSuccess(data);
                }
            } catch (error) {
                toast.fire({ icon: 'error', title: error.message });
            }
        });
    };

    const reloadSettings = async () => {
        const data = await fetchJSON('api/settings.php');
        state.settings = data.settings || {};
        state.qrPreview = data.qr_preview || state.qrPreview;
        updateBrandingPreviews();
        if (selectors.qrPreviewImage && state.qrPreview) {
            selectors.qrPreviewImage.src = state.qrPreview;
        }
        populateDefaultLanguage();
    };

    const updateBrandingPreviews = () => {
        const branding = state.settings?.branding || {};
        const logoPreview = document.querySelector('#logoPreview');
        const faviconPreview = document.querySelector('#faviconPreview');
        const qrLogoPreview = document.querySelector('#qrLogoPreview');
        if (logoPreview) logoPreview.src = branding.logo || '';
        if (faviconPreview) faviconPreview.src = branding.favicon || '';
        if (qrLogoPreview) qrLogoPreview.src = branding.qr_logo || '';
    };

    const populateDefaultLanguage = () => {
        const select = document.querySelector('#defaultLanguage');
        if (!select) return;
        const languages = state.settings?.languages || [];
        if (languages.length) {
            select.innerHTML = languages
                .map((language) => `<option value="${language.code}">${language.label}</option>`)
                .join('');
            if (state.settings?.restaurant?.language) {
                select.value = state.settings.restaurant.language;
            }
        }
    };

    const bindOrderActions = () => {
        selectors.ordersContainer?.addEventListener('change', (event) => {
            const select = event.target.closest('[data-order-status]');
            if (select) {
                const orderId = select.dataset.orderStatus;
                updateOrderStatus(orderId, select.value);
            }
        });

        selectors.ordersContainer?.addEventListener('click', (event) => {
            const detailButton = event.target.closest('[data-order-detail]');
            const exportButton = event.target.closest('[data-order-export]');
            if (detailButton) {
                openOrderModal(detailButton.dataset.orderDetail);
            }
            if (exportButton) {
                const type = exportButton.dataset.orderExport;
                const orderId = exportButton.dataset.order;
                window.open(`api/export.php?type=${type}&order=${orderId}`, '_blank');
            }
        });
    };

    const bindTableActions = () => {
        selectors.tablesContainer?.addEventListener('click', (event) => {
            const editButton = event.target.closest('[data-table-edit]');
            const copyButton = event.target.closest('[data-copy]');
            if (editButton) {
                openTableModal(editButton.dataset.tableEdit);
            }
            if (copyButton) {
                handleCopy(copyButton.dataset.copy);
            }
        });

        selectors.newTableButton?.addEventListener('click', () => openTableModal());
        selectors.saveTable?.addEventListener('click', saveTable);
        selectors.deleteTableButton?.addEventListener('click', deleteTable);
    };

    const bindWaiterActions = () => {
        selectors.waiterContainer?.addEventListener('change', (event) => {
            const select = event.target.closest('[data-waiter-status]');
            if (select) {
                updateWaiterStatus(select.dataset.waiterStatus, select.value);
            }
        });
    };

    const bindMenuManagement = () => {
        selectors.newCategoryButton?.addEventListener('click', () => openCategoryModal());
        selectors.saveCategory?.addEventListener('click', saveCategory);
        selectors.deleteCategoryButton?.addEventListener('click', deleteCategory);
        selectors.newProductButton?.addEventListener('click', () => openProductModal());
        selectors.saveProduct?.addEventListener('click', saveProduct);
        selectors.deleteProductButton?.addEventListener('click', () => {
            const id = selectors.productForm.querySelector('[name="id"]').value;
            if (id) {
                deleteProduct(id);
            }
        });
        selectors.productList?.addEventListener('click', (event) => {
            const edit = event.target.closest('[data-product-edit]');
            const remove = event.target.closest('[data-product-delete]');
            if (edit) {
                openProductModal(edit.dataset.productEdit);
            }
            if (remove) {
                deleteProduct(remove.dataset.productDelete);
            }
        });
        selectors.addVariant?.addEventListener('click', () => addVariantRow());
    };

    const bindFilters = () => {
        selectors.orderStatusFilters.forEach((button) => {
            button.addEventListener('click', async () => {
                selectors.orderStatusFilters.forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                state.orderStatus = button.dataset.status;
                state.orderPage = 1;
                await fetchOrders();
            });
        });

        selectors.orderSearch?.addEventListener('input', async (event) => {
            state.orderSearch = event.target.value;
            state.orderPage = 1;
            await fetchOrders();
        });

        selectors.tableSearch?.addEventListener('input', (event) => {
            state.tableSearch = event.target.value;
            renderTables();
        });

        selectors.waiterSearch?.addEventListener('input', (event) => {
            state.waiterSearch = event.target.value;
            renderWaiterCalls();
        });

        selectors.productSearch?.addEventListener('input', (event) => {
            state.productSearch = event.target.value;
            renderProducts();
        });

        selectors.productCategoryFilter?.addEventListener('change', (event) => {
            state.productCategory = event.target.value;
            renderProducts();
        });
    };

    const renderPagination = (container, totalPages, currentPage, onPage) => {
        if (!container) return;
        container.innerHTML = '';
        if (totalPages <= 1) return;
        for (let page = 1; page <= totalPages; page += 1) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `page-btn ${page === currentPage ? 'active' : ''}`;
            button.textContent = page;
            button.addEventListener('click', () => onPage(page));
            container.appendChild(button);
        }
    };

    const bindSettingsForms = () => {
        handleSettingsSubmit(selectors.generalSettingsForm, (formData) => ({ restaurant: Object.fromEntries(formData.entries()) }), reloadSettings);
        handleSettingsSubmit(selectors.brandingForm, (formData) => ({ branding: Object.fromEntries(formData.entries()) }), reloadSettings);
        handleSettingsSubmit(selectors.qrForm, (formData) => ({ qr: Object.fromEntries(formData.entries()) }), reloadSettings);
    };

    const bindAuth = () => {
        selectors.logoutButton?.addEventListener('click', async () => {
            await fetchJSON('api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' }),
            });
            window.location.href = 'login.php';
        });
    };

    const initLanguagesTable = () => {
        const table = $('#languagesTable');
        if (!table.length) return;
        table.DataTable({
            ajax: {
                url: 'api/languages.php',
                dataSrc: 'languages',
            },
            destroy: true,
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json' },
            columns: [
                { data: 'code', title: 'Kod' },
                { data: 'label', title: 'Dil Adı' },
            ],
        });
    };

    const initReportsTable = () => {
        const table = $('#reportsTable');
        if (!table.length) return;
        table.DataTable({
            ajax: {
                url: 'api/reports.php',
                dataSrc: 'reports',
                data: () => ({
                    start: document.querySelector('#reportStart')?.value || '',
                    end: document.querySelector('#reportEnd')?.value || '',
                }),
            },
            destroy: true,
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json' },
            columns: [
                { data: 'period', title: 'Dönem' },
                { data: 'orders', title: 'Sipariş' },
                { data: 'completed_revenue', title: 'Tamamlanan Ciro' },
                { data: 'pending_revenue', title: 'Bekleyen Ciro' },
            ],
        });
    };

    const bindExportButtons = () => {
        document.querySelector('#exportPdf')?.addEventListener('click', () => exportReport('pdf'));
        document.querySelector('#exportExcel')?.addEventListener('click', () => exportReport('excel'));
        document.querySelector('#reportStart')?.addEventListener('change', fetchReports);
        document.querySelector('#reportEnd')?.addEventListener('change', fetchReports);
    };

    const exportReport = async (format) => {
        const query = new URLSearchParams({
            start: document.querySelector('#reportStart')?.value || '',
            end: document.querySelector('#reportEnd')?.value || '',
            format,
        });
        window.open(`api/export.php?${query.toString()}`, '_blank');
    };

    const fetchReports = async () => {
        $('#reportsTable').DataTable().ajax.reload();
    };

    const bindLanguageModal = () => {
        selectors.saveLanguage?.addEventListener('click', async () => {
            const formData = new FormData(selectors.languageForm);
            const payload = Object.fromEntries(formData.entries());
            const response = await fetchJSON('api/languages.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            toast.fire({ icon: 'success', title: response.message || 'Dil kaydedildi.' });
            state.settings = state.settings || {};
            state.settings.languages = response.languages || [];
            await reloadSettings();
            bootstrap.Modal.getInstance(selectors.languageModal)?.hide();
            $('#languagesTable').DataTable().ajax.reload();
        });
    };

    const bindCurrencyModal = () => {
        selectors.saveCurrency?.addEventListener('click', async () => {
            const formData = new FormData(selectors.currencyForm);
            const payload = Object.fromEntries(formData.entries());
            const response = await fetchJSON('api/currencies.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            toast.fire({ icon: 'success', title: response.message || 'Para birimi kaydedildi.' });
            bootstrap.Modal.getInstance(selectors.currencyModal)?.hide();
            $('#currenciesTable').DataTable().ajax.reload();
        });
    };

    const initCurrenciesTable = () => {
        $('#currenciesTable').DataTable({
            ajax: {
                url: 'api/currencies.php',
                dataSrc: 'currencies',
            },
            destroy: true,
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json' },
            columns: [
                { data: 'code', title: 'Kod' },
                { data: 'name', title: 'Ad' },
                { data: 'symbol', title: 'Sembol' },
                {
                    data: 'is_default',
                    title: 'Varsayılan',
                    render: (value) => (Number(value) === 1 ? 'Evet' : 'Hayır'),
                },
            ],
        });
    };

    const bindSocket = () => {
        socket.on('order:update', async (payload) => {
            const title = payload.table ? `${payload.table} - ${payload.status}` : `Sipariş ${payload.status}`;
            toast.fire({ icon: 'info', title });
            playAudio(selectors.audioOrder);
            await fetchOrders();
            await fetchTables();
            await fetchDashboard();
        });

        socket.on('waiter:call', async (payload) => {
            const title = payload.table ? `${payload.table} garson istiyor.` : 'Yeni garson çağrısı';
            toast.fire({ icon: 'warning', title });
            playAudio(selectors.audioNotify);
            await fetchWaiterCalls();
            await fetchTables();
            await fetchDashboard();
        });

        socket.on('waiter:update', async (payload) => {
            toast.fire({ icon: 'info', title: `Garson durumu: ${payload.status}` });
            playAudio(selectors.audioNotify);
            await fetchWaiterCalls();
            await fetchDashboard();
        });
    };

    const init = async () => {
        bindNavigation();
        initDropzones();
        bindFilters();
        bindOrderActions();
        bindTableActions();
        bindWaiterActions();
        bindMenuManagement();
        bindSettingsForms();
        populateDefaultLanguage();
        bindAuth();
        bindExportButtons();
        bindLanguageModal();
        bindCurrencyModal();
        initLanguagesTable();
        initCurrenciesTable();
        initReportsTable();
        bindSocket();

        await Promise.all([
            fetchDashboard(),
            fetchOrders(),
            fetchTables(),
            fetchWaiterCalls(),
            fetchCategories(),
            fetchProducts(),
        ]);
    };

    return { init };
})();

document.addEventListener('DOMContentLoaded', () => AdminApp.init());
