Dropzone.autoDiscover = false;

const AdminApp = (() => {
    const baseUrl = (window.APP_STATE?.baseUrl || window.location.origin).replace(/\/+$/, '');
    const withBase = (path = '') => {
        if (!path) {
            return baseUrl;
        }
        if (/^https?:\/\//i.test(path)) {
            return path;
        }
        const cleanedPath = String(path).replace(/^\/+/, '');
        return `${baseUrl}/${cleanedPath}`;
    };

    const resolveIconClass = (value) => {
        const icon = (value || '').trim();
        if (!icon) {
            return 'bx bx-dots-horizontal';
        }
        if (icon.includes(' ')) {
            return icon;
        }
        if (icon.startsWith('bx-')) {
            return `bx ${icon}`;
        }
        if (icon.startsWith('fa-')) {
            return `fa-solid ${icon}`;
        }
        return icon;
    };

    const state = {
        summary: {},
        chart: null,
        period: 'weekly',
        settings: window.APP_STATE?.settings || {},
        qrPreview: window.APP_STATE?.qrPreview || '',
        currentSection: window.APP_STATE?.currentSection || 'dashboard',
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

    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const ICON_LIBRARY = [
        { class: 'bx bx-coffee', label: 'Kahve' },
        { class: 'bx bx-bowl-hot', label: 'Çorba' },
        { class: 'bx bx-cake', label: 'Pasta' },
        { class: 'bx bx-dish', label: 'Günün Menüsü' },
        { class: 'bx bx-pizza', label: 'Pizza' },
        { class: 'bx bx-baguette', label: 'Sandviç' },
        { class: 'bx bx-wine', label: 'İçecek' },
        { class: 'bx bx-bowl-rice', label: 'Ana Yemek' },
        { class: 'bx bx-ice-cream', label: 'Dondurma' },
        { class: 'bx bx-restaurant', label: 'Şef Önerisi' },
        { class: 'bx bx-food-menu', label: 'Menü' },
        { class: 'bx bx-lemon', label: 'Limonata' },
        { class: 'fa-solid fa-burger', label: 'Burger' },
        { class: 'fa-solid fa-mug-hot', label: 'Sıcak İçecek' },
        { class: 'fa-solid fa-ice-cream', label: 'Tatlı' },
        { class: 'fa-solid fa-drumstick-bite', label: 'Tavuk' },
        { class: 'fa-solid fa-fish', label: 'Balık' },
        { class: 'fa-solid fa-bowl-rice', label: 'Pilav' },
        { class: 'fa-solid fa-utensils', label: 'Servis' },
        { class: 'fa-solid fa-martini-glass-citrus', label: 'Kokteyl' },
    ];

    const ORDER_STATUSES = ['Beklemede', 'Hazırlanıyor', 'Hazırlandı', 'Ödeme Alındı', 'İptal'];

    const selectors = {
        sections: document.querySelectorAll('.section'),
        navButtons: document.querySelectorAll('.dashboard__link'),
        summaryCards: document.querySelectorAll('[data-summary]'),
        chartCanvas: document.querySelector('#ordersChart'),
        chartPeriodButtons: document.querySelectorAll('[data-report]'),
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
        currencyBadges: document.querySelector('#currencyBadges'),
        tableModal: document.querySelector('#tableModal'),
        tableForm: document.querySelector('#tableForm'),
        saveTable: document.querySelector('#saveTable'),
        deleteTableButton: document.querySelector('#deleteTableButton'),
        orderModal: document.querySelector('#orderModal'),
        orderModalContent: document.querySelector('#orderModalContent'),
        audioOrder: document.querySelector('#audioOrderAdmin'),
        audioNotify: document.querySelector('#audioNotifyAdmin'),
        notificationsForm: document.querySelector('#notificationsForm'),
        orderSoundPreview: document.querySelector('#orderSoundPreview'),
        waiterSoundPreview: document.querySelector('#waiterSoundPreview'),
    };

    const toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4200,
    });

    const socket = io('https://qrmenu.noasoft.org:4000');

    const fetchJSON = async (url, options = {}) => {
        const response = await fetch(withBase(url), options);
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

    const detectSectionFromLocation = () => {
        const sectionPaths = window.APP_STATE?.sectionPaths || {};
        const currentPath = window.location.pathname.replace(/\/+$/, '') || '/panel';
        const match = Object.entries(sectionPaths).find(([, path]) => path === currentPath);
        return match ? match[0] : 'dashboard';
    };

    const switchSection = (section, push = false) => {
        if (!section) return;
        state.currentSection = section;
        selectors.sections.forEach((element) => {
            element.classList.toggle('d-none', element.id !== `section-${section}`);
        });
        selectors.navButtons.forEach((item) => {
            item.classList.toggle('active', item.dataset.section === section);
        });
        if (push) {
            const targetLink = [...selectors.navButtons].find((item) => item.dataset.section === section);
            const targetUrl = targetLink?.dataset.url || targetLink?.getAttribute('href');
            if (targetUrl) {
                window.history.pushState({ section }, '', targetUrl);
            }
        }
    };

    const bindNavigation = () => {
        selectors.navButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                switchSection(button.dataset.section, true);
            });
        });

        window.addEventListener('popstate', (event) => {
            const section = event.state?.section || detectSectionFromLocation();
            switchSection(section, false);
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
                const sessionHtml = (table.session_orders || []).map((session) => `
                    <li>
                        <span>#${session.order_id} - ${session.status}</span>
                        <span>${session.total_formatted || ''}</span>
                    </li>
                `).join('');
                const sessionBlock = sessionHtml ? `
                    <div class="session-orders">
                        <strong>Geçici Siparişler</strong>
                        <ul>${sessionHtml}</ul>
                    </div>
                ` : '';

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
                        ${sessionBlock}
                    </div>
                    <div class="table-card__footer d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-outline-primary" data-table-edit="${table.id}">Düzenle</button>
                        <button class="btn btn-sm btn-outline-secondary" data-copy="${table.qr_url}">Link Kopyala</button>
                        <a class="btn btn-sm btn-outline-success" href="${table.qr_code_url}" target="_blank" rel="noopener">QR Görüntüle</a>
                        <a class="btn btn-sm btn-success" href="${table.qr_download_url}" target="_blank" rel="noopener">QR İndir</a>
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
            const iconClass = resolveIconClass(category.icon);
            const iconHtml = category.image
                ? `<img src="${category.image}" alt="${category.name}"/>`
                : `<i class='${iconClass}'></i>`;
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
        const normalizedSelected = (selected || '').trim();
        ICON_LIBRARY.forEach((iconOption) => {
            const button = document.createElement('button');
            button.type = 'button';
            const iconClass = iconOption.class;
            const altClass = iconClass.replace(/^bx\s+/, 'bx-');
            const isActive = normalizedSelected === iconClass || normalizedSelected === altClass;
            button.className = `icon-library__item ${isActive ? 'active' : ''}`;
            button.innerHTML = `<i class='${iconClass}'></i><span>${iconOption.label}</span>`;
            button.addEventListener('click', () => {
                selectors.iconLibrary.querySelectorAll('.icon-library__item').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                selectors.categoryForm.querySelector('[name="icon"]').value = iconClass;
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
            const acceptedFiles = element.dataset.accept || 'image/*';
            const dz = new Dropzone(element, {
                url: withBase('api/upload.php'),
                paramName: 'file',
                maxFiles: 1,
                acceptedFiles,
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
                        preview.src = response.url || withBase(response.path || '');
                    }
                }
                if (element.dataset.audio) {
                    const audio = document.querySelector(element.dataset.audio);
                    if (audio) {
                        audio.src = response.url || withBase(response.path || '');
                        audio.load?.();
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
        updateAudioSources();
        if (selectors.qrPreviewImage && state.qrPreview) {
            selectors.qrPreviewImage.src = state.qrPreview;
        }
        populateDefaultLanguage();
        populateTimezones();
        updateCurrencyBadges();
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

    const updateAudioSources = () => {
        const notifications = state.settings?.notifications || window.APP_STATE?.notifications || {};
        if (selectors.audioOrder) {
            selectors.audioOrder.src = notifications.order_sound || selectors.audioOrder.src;
            selectors.audioOrder.load?.();
        }
        if (selectors.audioNotify) {
            selectors.audioNotify.src = notifications.waiter_sound || selectors.audioNotify.src;
            selectors.audioNotify.load?.();
        }
        if (selectors.orderSoundPreview) {
            selectors.orderSoundPreview.src = notifications.order_sound || '';
            selectors.orderSoundPreview.load?.();
        }
        if (selectors.waiterSoundPreview) {
            selectors.waiterSoundPreview.src = notifications.waiter_sound || '';
            selectors.waiterSoundPreview.load?.();
        }
        const orderInput = document.querySelector('#orderSoundInput');
        const waiterInput = document.querySelector('#waiterSoundInput');
        if (orderInput) {
            orderInput.value = notifications.order_sound || '';
        }
        if (waiterInput) {
            waiterInput.value = notifications.waiter_sound || '';
        }
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

    const populateTimezones = () => {
        const select = document.querySelector('#timezoneSelect');
        if (!select) return;
        const timezones = state.settings?.timezones || window.APP_STATE?.timezones || [];
        const selected = state.settings?.restaurant?.timezone || select.value || 'Europe/Istanbul';
        select.innerHTML = timezones.map((timezone) => `<option value="${timezone}">${timezone}</option>`).join('');
        select.value = selected;
    };

    const updateCurrencyBadges = () => {
        if (!selectors.currencyBadges) return;
        const currencies = state.settings?.currencies || [];
        if (!currencies.length) {
            selectors.currencyBadges.innerHTML = '<span class="badge bg-secondary">Para birimi ekleyin</span>';
            return;
        }
        selectors.currencyBadges.innerHTML = currencies
            .map((currency) => {
                const badgeClass = Number(currency.is_default) === 1 ? 'bg-success' : 'bg-secondary';
                return `<span class="badge rounded-pill ${badgeClass} me-2 mb-2">${escapeHtml(currency.code)} &mdash; ${escapeHtml(currency.name || currency.code)}</span>`;
            })
            .join('');
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
                window.open(withBase(`api/export.php?type=${type}&order=${orderId}`), '_blank');
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

    const bindChartControls = () => {
        Array.from(selectors.chartPeriodButtons || []).forEach((button) => {
            button.addEventListener('click', async () => {
                Array.from(selectors.chartPeriodButtons || []).forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                state.period = button.dataset.report || 'weekly';
                await fetchDashboard();
            });
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
        handleSettingsSubmit(selectors.notificationsForm, (formData) => ({ notifications: Object.fromEntries(formData.entries()) }), reloadSettings);
    };

    const bindAuth = () => {
        selectors.logoutButton?.addEventListener('click', async () => {
            await fetchJSON('api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' }),
            });
            window.location.href = withBase('login');
        });
    };

    const initLanguagesTable = () => {
        const table = $('#languagesTable');
        if (!table.length) return;
        table.DataTable({
            ajax: {
                url: withBase('api/languages.php'),
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
                url: withBase('api/reports.php'),
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
        window.open(withBase(`api/export.php?${query.toString()}`), '_blank');
    };

    const fetchReports = async () => {
        $('#reportsTable').DataTable().ajax.reload();
    };

    const openLanguageModal = async (code = '') => {
        if (!selectors.languageForm) return;
        selectors.languageForm.reset();
        const codeInput = selectors.languageForm.querySelector('[name="code"]');
        const labelInput = selectors.languageForm.querySelector('[name="label"]');
        const translationsInput = selectors.languageForm.querySelector('[name="translations"]');
        if (codeInput) {
            codeInput.readOnly = Boolean(code);
            codeInput.value = code || '';
        }
        if (labelInput) {
            const languageMeta = (state.settings?.languages || []).find((language) => language.code === code);
            labelInput.value = languageMeta?.label || '';
        }
        if (translationsInput) {
            translationsInput.value = JSON.stringify({}, null, 2);
        }

        if (code) {
            const data = await fetchJSON(`api/languages.php?code=${code}`);
            if (translationsInput) {
                translationsInput.value = JSON.stringify(data.translations || {}, null, 2);
            }
        }

        bootstrap.Modal.getOrCreateInstance(selectors.languageModal).show();
    };

    const setDefaultLanguage = async (code) => {
        const response = await fetchJSON('api/languages.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code }),
        });
        toast.fire({ icon: 'success', title: response.message || 'Varsayılan dil güncellendi.' });
        await reloadSettings();
        $('#languagesTable').DataTable().ajax.reload(null, false);
    };

    const deleteLanguage = async (code) => {
        const confirmResult = await Swal.fire({
            title: 'Dil silinsin mi?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sil',
            cancelButtonText: 'Vazgeç',
        });
        if (!confirmResult.isConfirmed) return;
        const response = await fetchJSON(`api/languages.php?code=${code}`, { method: 'DELETE' });
        toast.fire({ icon: 'success', title: response.message || 'Dil silindi.' });
        await reloadSettings();
        $('#languagesTable').DataTable().ajax.reload(null, false);
    };

    const bindLanguageModal = () => {
        document.querySelectorAll('[data-bs-target="#languageModal"]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                openLanguageModal();
            });
        });

        selectors.saveLanguage?.addEventListener('click', async () => {
            try {
                const formData = new FormData(selectors.languageForm);
                const payload = Object.fromEntries(formData.entries());
                payload.code = (payload.code || '').toLowerCase();
                payload.label = payload.label || payload.code.toUpperCase();
                let translations;
                try {
                    translations = JSON.parse(payload.translations || '{}');
                } catch (error) {
                    toast.fire({ icon: 'error', title: 'Geçerli bir JSON içeriği girin.' });
                    return;
                }
                payload.translations = translations;
                const response = await fetchJSON('api/languages.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                toast.fire({ icon: 'success', title: response.message || 'Dil kaydedildi.' });
                await reloadSettings();
                bootstrap.Modal.getInstance(selectors.languageModal)?.hide();
                $('#languagesTable').DataTable().ajax.reload(null, false);
            } catch (error) {
                toast.fire({ icon: 'error', title: error.message });
            }
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
                { data: 'code', title: 'Kod', render: (value) => value.toUpperCase() },
                { data: 'label', title: 'Dil Adı' },
                {
                    data: 'is_default',
                    title: 'Varsayılan',
                    render: (value) => (Number(value) === 1 ? 'Evet' : 'Hayır'),
                },
                {
                    data: null,
                    title: 'İşlemler',
                    orderable: false,
                    render: (data, type, row) => {
                        const disabled = Number(row.is_default) === 1 ? 'disabled' : '';
                        return `
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" data-language-edit data-code="${row.code}">Düzenle</button>
                                <button type="button" class="btn btn-outline-success" data-language-default data-code="${row.code}" ${disabled}>Varsayılan</button>
                                <button type="button" class="btn btn-outline-danger" data-language-delete data-code="${row.code}" ${disabled}>Sil</button>
                            </div>
                        `;
                    },
                },
            ],
        });

        table.on('click', '[data-language-edit]', (event) => {
            const { code } = event.currentTarget.dataset;
            if (code) {
                openLanguageModal(code);
            }
        });

        table.on('click', '[data-language-default]', async (event) => {
            const { code } = event.currentTarget.dataset;
            if (code) {
                await setDefaultLanguage(code);
            }
        });

        table.on('click', '[data-language-delete]', async (event) => {
            const { code } = event.currentTarget.dataset;
            if (code) {
                await deleteLanguage(code);
            }
        });
    };

    const openCurrencyModal = (currency = null) => {
        if (!selectors.currencyForm) return;
        selectors.currencyForm.reset();
        const codeInput = selectors.currencyForm.querySelector('[name="code"]');
        const nameInput = selectors.currencyForm.querySelector('[name="name"]');
        const symbolInput = selectors.currencyForm.querySelector('[name="symbol"]');
        const defaultSelect = selectors.currencyForm.querySelector('[name="is_default"]');
        if (currency) {
            if (codeInput) {
                codeInput.value = currency.code || '';
                codeInput.readOnly = true;
            }
            if (nameInput) nameInput.value = currency.name || '';
            if (symbolInput) symbolInput.value = currency.symbol || '';
            if (defaultSelect) defaultSelect.value = Number(currency.is_default) === 1 ? '1' : '0';
        } else {
            if (codeInput) {
                codeInput.value = '';
                codeInput.readOnly = false;
            }
            if (defaultSelect) defaultSelect.value = '0';
        }
        bootstrap.Modal.getOrCreateInstance(selectors.currencyModal).show();
    };

    const setDefaultCurrency = async (code) => {
        const response = await fetchJSON('api/currencies.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code }),
        });
        toast.fire({ icon: 'success', title: response.message || 'Varsayılan para birimi güncellendi.' });
        await reloadSettings();
        $('#currenciesTable').DataTable().ajax.reload(null, false);
    };

    const deleteCurrency = async (id) => {
        const confirmResult = await Swal.fire({
            title: 'Para birimi silinsin mi?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sil',
            cancelButtonText: 'Vazgeç',
        });
        if (!confirmResult.isConfirmed) return;
        const response = await fetchJSON(`api/currencies.php?id=${id}`, { method: 'DELETE' });
        toast.fire({ icon: 'success', title: response.message || 'Para birimi silindi.' });
        await reloadSettings();
        $('#currenciesTable').DataTable().ajax.reload(null, false);
    };

    const bindCurrencyModal = () => {
        document.querySelectorAll('[data-bs-target="#currencyModal"]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                openCurrencyModal();
            });
        });

        selectors.saveCurrency?.addEventListener('click', async () => {
            try {
                const formData = new FormData(selectors.currencyForm);
                const payload = Object.fromEntries(formData.entries());
                payload.code = (payload.code || '').toUpperCase();
                payload.symbol = payload.symbol || '';
                payload.name = payload.name || payload.code;
                payload.is_default = Number(payload.is_default || 0);
                const response = await fetchJSON('api/currencies.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                toast.fire({ icon: 'success', title: response.message || 'Para birimi kaydedildi.' });
                await reloadSettings();
                bootstrap.Modal.getInstance(selectors.currencyModal)?.hide();
                $('#currenciesTable').DataTable().ajax.reload(null, false);
            } catch (error) {
                toast.fire({ icon: 'error', title: error.message });
            }
        });
    };

    const initCurrenciesTable = () => {
        const table = $('#currenciesTable');
        if (!table.length) return;
        table.DataTable({
            ajax: {
                url: withBase('api/currencies.php'),
                dataSrc: 'currencies',
            },
            destroy: true,
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json' },
            columns: [
                { data: 'code', title: 'Kod', render: (value) => value.toUpperCase() },
                { data: 'name', title: 'Ad' },
                { data: 'symbol', title: 'Sembol' },
                {
                    data: 'is_default',
                    title: 'Varsayılan',
                    render: (value) => (Number(value) === 1 ? 'Evet' : 'Hayır'),
                },
                {
                    data: null,
                    title: 'İşlemler',
                    orderable: false,
                    render: (data, type, row) => {
                        const disabled = Number(row.is_default) === 1 ? 'disabled' : '';
                        return `
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" data-currency-edit data-code="${row.code}" data-name="${escapeHtml(row.name || '')}" data-symbol="${escapeHtml(row.symbol || '')}" data-default="${row.is_default}" data-id="${row.id}">Düzenle</button>
                                <button type="button" class="btn btn-outline-success" data-currency-default data-code="${row.code}" ${disabled}>Varsayılan</button>
                                <button type="button" class="btn btn-outline-danger" data-currency-delete data-id="${row.id}" ${disabled}>Sil</button>
                            </div>
                        `;
                    },
                },
            ],
        });

        table.on('click', '[data-currency-edit]', (event) => {
            const button = event.currentTarget;
            openCurrencyModal({
                code: button.dataset.code || '',
                name: button.dataset.name || '',
                symbol: button.dataset.symbol || '',
                is_default: button.dataset.default || 0,
            });
        });

        table.on('click', '[data-currency-default]', async (event) => {
            const { code } = event.currentTarget.dataset;
            if (code) {
                await setDefaultCurrency(code);
            }
        });

        table.on('click', '[data-currency-delete]', async (event) => {
            const { id } = event.currentTarget.dataset;
            if (id) {
                await deleteCurrency(id);
            }
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
        state.currentSection = detectSectionFromLocation();
        switchSection(state.currentSection);
        window.history.replaceState({ section: state.currentSection }, '', window.location.pathname);
        bindNavigation();
        bindChartControls();
        initDropzones();
        bindFilters();
        bindOrderActions();
        bindTableActions();
        bindWaiterActions();
        bindMenuManagement();
        bindSettingsForms();
        populateDefaultLanguage();
        populateTimezones();
        updateBrandingPreviews();
        updateAudioSources();
        updateCurrencyBadges();
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
