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
        menuTemplate: window.APP_STATE?.settings?.menu?.template || 'menu1',
        qrPreview: window.APP_STATE?.qrPreview || '',
        currentSection: window.APP_STATE?.currentSection || 'dashboard',
        user: window.APP_USER || null,
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
        dailyMenu: [],
        flashTimeout: null,
    };

    const FLASH_DEFAULTS = {
        order: '#0F9D58',
        waiter: '#EA4335',
    };
    const HEX_COLOR_PATTERN = /^#([0-9A-F]{3}|[0-9A-F]{6})$/i;

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
        { class: 'bx bx-wine', label: 'İçecek' },
        { class: 'bx bx-restaurant', label: 'Ana Yemek' },
        { class: 'bx bx-lemon', label: 'Limonata' },
        { class: 'bx bx-ice-cream', label: 'Dondurma' },
        { class: 'fa-solid fa-burger', label: 'Burger' },
        { class: 'fa-solid fa-mug-hot', label: 'Sıcak İçecek' },
        { class: 'fa-solid fa-ice-cream', label: 'Tatlı' },
        { class: 'fa-solid fa-drumstick-bite', label: 'Tavuk' },
        { class: 'fa-solid fa-fish', label: 'Balık' },
        { class: 'fa-solid fa-utensils', label: 'Servis' },
        { class: 'fa-solid fa-martini-glass-citrus', label: 'Kokteyl' },
        { class: 'fa-solid fa-bowl-food', label: 'Ev Yemekleri' },
        { class: 'fa-solid fa-leaf', label: 'Vegan' },
        { class: 'fa-solid fa-pepper-hot', label: 'Acılı' },
        { class: 'fa-solid fa-bacon', label: 'Kahvaltı' },
        { class: 'fa-solid fa-pizza-slice', label: 'Atıştırmalık' },
    ];

    const ORDER_STATUSES = ['Beklemede', 'Hazırlanıyor', 'Hazırlandı', 'Ödeme Alındı', 'İptal'];
    let mobileNavCollapse = null;

    const selectors = {
        dashboardRoot: document.querySelector('.dashboard'),
        mobileHeader: document.querySelector('.dashboard__mobile-header'),
        sidebarToggle: document.querySelector('#sidebarToggle'),
        sidebarClose: document.querySelector('#sidebarClose'),
        sidebarOverlay: document.querySelector('#sidebarOverlay'),
        mobileNav: document.querySelector('#mobileNav'),
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
        dailyMenuList: document.querySelector('#dailyMenuList'),
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
        menuTemplateForm: document.querySelector('#menuTemplateForm'),
        brandingForm: document.querySelector('#brandingForm'),
        mailSettingsForm: document.querySelector('#mailSettingsForm'),
        qrForm: document.querySelector('#qrForm'),
        qrPreviewImage: document.querySelector('#qrPreviewImage'),
        logoutButton: document.querySelector('#logoutButton'),
        mobileLogout: document.querySelector('#mobileLogout'),
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
        accountForm: document.querySelector('#accountForm'),
        passwordForm: document.querySelector('#passwordForm'),
        orderSoundPreview: document.querySelector('#orderSoundPreview'),
        waiterSoundPreview: document.querySelector('#waiterSoundPreview'),
        newDailyMenuButton: document.querySelector('#newDailyMenuButton'),
        dailyMenuModal: document.querySelector('#dailyMenuModal'),
        dailyMenuForm: document.querySelector('#dailyMenuForm'),
        saveDailyMenu: document.querySelector('#saveDailyMenu'),
        flashOverlay: document.querySelector('#alertFlash'),
        flashOverlayText: document.querySelector('#alertFlashText'),
        flashToggle: document.querySelector('#flashToggle'),
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

    const getDefaultCurrencySymbol = () => {
        const defaultCode = (state.settings?.restaurant?.currency || 'TRY').toUpperCase();
        const entry = (state.settings?.currencies || []).find((currency) => (currency.code || '').toUpperCase() === defaultCode);
        return entry?.symbol || defaultCode;
    };

    const formatCurrencyValue = (amount) => {
        const symbol = getDefaultCurrencySymbol();
        const value = Number(amount || 0).toFixed(2);
        return /^[A-Za-z]{2,4}$/.test(symbol) ? `${value} ${symbol}` : `${symbol} ${value}`;
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

    const hideFlash = () => {
        if (state.flashTimeout) {
            clearTimeout(state.flashTimeout);
            state.flashTimeout = null;
        }
        if (!selectors.flashOverlay) {
            return;
        }
        selectors.flashOverlay.classList.remove('active');
        selectors.flashOverlay.setAttribute('aria-hidden', 'true');
    };

    const getNotifications = () => state.settings?.notifications || window.APP_STATE?.notifications || {};

    const ensureFlashColorCache = () => {
        if (!selectors.flashOverlay) {
            return;
        }
        const notifications = getNotifications();
        let orderColor = notifications.flash_order_color || selectors.flashOverlay.dataset.orderColor || FLASH_DEFAULTS.order;
        let waiterColor = notifications.flash_waiter_color || selectors.flashOverlay.dataset.waiterColor || FLASH_DEFAULTS.waiter;
        orderColor = HEX_COLOR_PATTERN.test(orderColor) ? orderColor.toUpperCase() : FLASH_DEFAULTS.order;
        waiterColor = HEX_COLOR_PATTERN.test(waiterColor) ? waiterColor.toUpperCase() : FLASH_DEFAULTS.waiter;
        selectors.flashOverlay.dataset.orderColor = orderColor;
        selectors.flashOverlay.dataset.waiterColor = waiterColor;
    };

    const hexToRgba = (hex, alpha = 1) => {
        if (!hex) {
            return `rgba(15, 157, 88, ${alpha})`;
        }
        let sanitized = hex.toString().trim().replace('#', '');
        if (sanitized.length === 3) {
            sanitized = sanitized.split('').map((char) => char + char).join('');
        }
        if (sanitized.length !== 6 || Number.isNaN(Number(`0x${sanitized}`))) {
            return `rgba(15, 157, 88, ${alpha})`;
        }
        const bigint = parseInt(sanitized, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    };

    const applyFlashColor = (type = 'order') => {
        if (!selectors.flashOverlay) {
            return;
        }
        ensureFlashColorCache();
        const key = type === 'waiter' ? 'waiterColor' : 'orderColor';
        const fallback = FLASH_DEFAULTS[type] || FLASH_DEFAULTS.order;
        const hex = selectors.flashOverlay.dataset[key] || fallback;
        const sanitizedHex = HEX_COLOR_PATTERN.test(hex) ? hex.toUpperCase() : fallback;
        selectors.flashOverlay.style.setProperty('--flash-color', hexToRgba(sanitizedHex, 0.9));
        selectors.flashOverlay.style.setProperty('--flash-color-soft', hexToRgba(sanitizedHex, 0.55));
    };

    const triggerFlash = (message, type = 'order') => {
        const notifications = getNotifications();
        if (!selectors.flashOverlay || !notifications.flash_enabled) {
            return;
        }
        applyFlashColor(type);
        if (selectors.flashOverlayText) {
            selectors.flashOverlayText.textContent = message || 'Yeni bildirim';
        }
        selectors.flashOverlay.classList.remove('active');
        // force reflow so CSS animation can restart
        // eslint-disable-next-line no-unused-expressions
        selectors.flashOverlay.offsetHeight;
        selectors.flashOverlay.classList.add('active');
        selectors.flashOverlay.setAttribute('aria-hidden', 'false');
        if (state.flashTimeout) {
            clearTimeout(state.flashTimeout);
        }
        state.flashTimeout = setTimeout(() => {
            hideFlash();
        }, 1800);
    };

    const updateSidebarToggleLabel = () => {
        const isOpen = selectors.dashboardRoot?.classList.contains('sidebar-open');
        const label = selectors.sidebarToggle?.querySelector('[data-toggle-label]');
        if (label) {
            label.textContent = isOpen ? 'Menüyü Kapat' : 'Menüyü Aç';
        }
        if (selectors.sidebarToggle) {
            selectors.sidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    };

    const syncMobileHeaderHeight = () => {
        if (!selectors.mobileHeader) {
            return;
        }
        const height = Math.ceil(selectors.mobileHeader.getBoundingClientRect().height);
        if (height > 0) {
            document.documentElement.style.setProperty('--mobile-header-height', `${height}px`);
        }
    };

    const closeSidebar = () => {
        if (mobileNavCollapse && selectors.mobileNav?.classList.contains('show')) {
            mobileNavCollapse.hide();
        }
        selectors.dashboardRoot?.classList.remove('sidebar-open');
        updateSidebarToggleLabel();
    };

    const openSidebar = () => {
        selectors.dashboardRoot?.classList.add('sidebar-open');
        if (mobileNavCollapse && !selectors.mobileNav?.classList.contains('show')) {
            mobileNavCollapse.show();
        }
        updateSidebarToggleLabel();
    };

    const bindSidebarToggle = () => {
        syncMobileHeaderHeight();
        if (selectors.mobileNav && window.bootstrap?.Collapse) {
            mobileNavCollapse = bootstrap.Collapse.getOrCreateInstance(selectors.mobileNav, { toggle: false });
            selectors.mobileNav.addEventListener('show.bs.collapse', () => {
                selectors.dashboardRoot?.classList.add('sidebar-open');
                updateSidebarToggleLabel();
            });
            selectors.mobileNav.addEventListener('hidden.bs.collapse', () => {
                selectors.dashboardRoot?.classList.remove('sidebar-open');
                updateSidebarToggleLabel();
            });
        }

        selectors.sidebarToggle?.addEventListener('click', (event) => {
            event.preventDefault();
            if (selectors.dashboardRoot?.classList.contains('sidebar-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        selectors.sidebarClose?.addEventListener('click', () => {
            closeSidebar();
        });
        selectors.sidebarOverlay?.addEventListener('click', () => {
            closeSidebar();
        });

        window.addEventListener('resize', () => {
            syncMobileHeaderHeight();
            if (window.innerWidth >= 992) {
                closeSidebar();
            }
        });

        const handleLoad = () => {
            syncMobileHeaderHeight();
            window.removeEventListener('load', handleLoad);
        };
        window.addEventListener('load', handleLoad);
        updateSidebarToggleLabel();
    };

    const bindFlashOverlay = () => {
        selectors.flashOverlay?.addEventListener('click', () => {
            hideFlash();
        });
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
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
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
                const ordersHtml = (table.active_orders || []).map((order) => {
                    const status = escapeHtml(order.status || '');
                    const total = escapeHtml(order.total_formatted || Number(order.total).toFixed(2));
                    return `
                        <li>
                            <span>#${order.id} - ${status}</span>
                            <span>${total}</span>
                        </li>
                    `;
                }).join('');
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
        const selectedProduct = selectors.dailyMenuForm?.querySelector('[name="product_id"]')?.value || '';
        populateDailyMenuSelect(selectedProduct);
    };

    const renderDailyMenu = () => {
        if (!selectors.dailyMenuList) return;
        selectors.dailyMenuList.innerHTML = '';

        if (!state.dailyMenu.length) {
            selectors.dailyMenuList.innerHTML = '<p class="text-muted mb-0">Henüz eklenmiş öğe bulunmuyor.</p>';
            return;
        }

        state.dailyMenu.forEach((item, index) => {
            const product = item.product || {};
            const image = product.image || 'assets/vendor/demo/coffee-1.png';
            const badge = (item.badge || '').trim();
            const headline = item.headline || product.name || '';
            const tagline = (item.tagline || '').trim();
            const price = formatCurrencyValue(product.price);
            const card = document.createElement('div');
            card.className = 'daily-menu-admin__item';
            card.innerHTML = `
                <div class="daily-menu-admin__media">
                    <img src="${image}" alt="${escapeHtml(product.name || '')}" loading="lazy">
                </div>
                <div class="daily-menu-admin__body">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                        <h3>${escapeHtml(headline)}</h3>
                        ${badge ? `<span class="badge bg-success">${escapeHtml(badge)}</span>` : ''}
                    </div>
                    ${tagline ? `<p class="text-muted mb-1">${escapeHtml(tagline)}</p>` : ''}
                    <small class="text-muted">${escapeHtml(product.name || '')} • ${price}</small>
                </div>
                <div class="daily-menu-admin__actions">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-daily-up="${item.id}" ${index === 0 ? 'disabled' : ''}>
                        <i class="bx bx-chevron-up"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-daily-down="${item.id}" ${index === state.dailyMenu.length - 1 ? 'disabled' : ''}>
                        <i class="bx bx-chevron-down"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-daily-edit="${item.id}">Düzenle</button>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-daily-delete="${item.id}">Sil</button>
                </div>
            `;
            selectors.dailyMenuList.appendChild(card);
        });
    };

    const fetchDailyMenu = async () => {
        const data = await fetchJSON('api/daily-menu.php');
        state.dailyMenu = data.items || [];
        renderDailyMenu();
    };

    const populateDailyMenuSelect = (selectedId = '') => {
        const select = selectors.dailyMenuForm?.querySelector('select[name="product_id"]');
        if (!select) return;
        if (!state.products.length) {
            select.innerHTML = '<option value="">Ürün bulunamadı</option>';
            select.disabled = true;
            return;
        }
        select.disabled = false;
        select.innerHTML = state.products.map((product) => {
            const isSelected = Number(product.id) === Number(selectedId);
            return `<option value="${product.id}" ${isSelected ? 'selected' : ''}>${escapeHtml(product.name || '')}</option>`;
        }).join('');
    };

    const openDailyMenuModal = (id = null) => {
        if (!selectors.dailyMenuModal || !selectors.dailyMenuForm) return;
        const modal = bootstrap.Modal.getOrCreateInstance(selectors.dailyMenuModal);
        selectors.dailyMenuForm.reset();
        selectors.dailyMenuForm.querySelector('[name="id"]').value = id || '';
        populateDailyMenuSelect();
        if (id) {
            const item = state.dailyMenu.find((entry) => entry.id === id);
            if (item) {
                selectors.dailyMenuForm.querySelector('[name="product_id"]').value = item.product_id;
                selectors.dailyMenuForm.querySelector('[name="headline"]').value = item.headline || '';
                selectors.dailyMenuForm.querySelector('[name="tagline"]').value = item.tagline || '';
                selectors.dailyMenuForm.querySelector('[name="badge"]').value = item.badge || '';
            }
        }
        modal.show();
    };

    const saveDailyMenuItem = async () => {
        if (!selectors.dailyMenuForm) return;
        const formData = new FormData(selectors.dailyMenuForm);
        const payload = Object.fromEntries(formData.entries());
        payload.product_id = Number(payload.product_id || 0);
        const response = await fetchJSON('api/daily-menu.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        state.dailyMenu = response.items || [];
        renderDailyMenu();
        toast.fire({ icon: 'success', title: response.message || 'Güncellendi.' });
        bootstrap.Modal.getInstance(selectors.dailyMenuModal)?.hide();
    };

    const reorderDailyMenu = async (order) => {
        const response = await fetchJSON('api/daily-menu.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reorder', order }),
        });
        state.dailyMenu = response.items || [];
        renderDailyMenu();
    };

    const moveDailyMenuItem = async (id, direction) => {
        const currentIndex = state.dailyMenu.findIndex((item) => item.id === id);
        if (currentIndex === -1) return;
        const targetIndex = direction === 'up' ? currentIndex - 1 : currentIndex + 1;
        if (targetIndex < 0 || targetIndex >= state.dailyMenu.length) {
            return;
        }
        const order = [...state.dailyMenu];
        const [moved] = order.splice(currentIndex, 1);
        order.splice(targetIndex, 0, moved);
        await reorderDailyMenu(order.map((item) => item.id));
    };

    const deleteDailyMenuItem = async (id) => {
        const confirmResult = await Swal.fire({
            title: 'Emin misiniz?',
            text: 'Günün menüsünden kaldırılacak.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sil',
            cancelButtonText: 'Vazgeç',
        });
        if (!confirmResult.isConfirmed) {
            return;
        }
        const response = await fetchJSON(`api/daily-menu.php?id=${id}`, { method: 'DELETE' });
        state.dailyMenu = response.items || [];
        renderDailyMenu();
        toast.fire({ icon: 'success', title: response.message || 'Silindi.' });
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
                state.menuTemplate = state.settings?.menu?.template || state.menuTemplate || 'menu1';
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
        state.menuTemplate = state.settings?.menu?.template || state.menuTemplate || 'menu1';
        state.qrPreview = data.qr_preview || state.qrPreview;
        updateBrandingPreviews();
        updateAudioSources();
        updateTemplateSelections();
        populateMailSettings();
        if (selectors.qrPreviewImage && state.qrPreview) {
            selectors.qrPreviewImage.src = state.qrPreview;
        }
        populateDefaultLanguage();
        populateCurrencySelect();
        populateTimezones();
        updateCurrencyBadges();
        await fetchTables();
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
        if (selectors.flashToggle) {
            const enabled = Boolean(notifications.flash_enabled);
            selectors.flashToggle.checked = enabled;
            selectors.flashToggle.setAttribute('aria-checked', enabled ? 'true' : 'false');
            if (!enabled) {
                hideFlash();
            }
        }
        if (selectors.notificationsForm) {
            const orderColorInput = selectors.notificationsForm.querySelector('[name="flash_order_color"]');
            const waiterColorInput = selectors.notificationsForm.querySelector('[name="flash_waiter_color"]');
            if (orderColorInput) {
                orderColorInput.value = notifications.flash_order_color || selectors.flashOverlay?.dataset.orderColor || FLASH_DEFAULTS.order;
            }
            if (waiterColorInput) {
                waiterColorInput.value = notifications.flash_waiter_color || selectors.flashOverlay?.dataset.waiterColor || FLASH_DEFAULTS.waiter;
            }
        }
        ensureFlashColorCache();
        applyFlashColor('order');
    };

    const populateMailSettings = () => {
        if (!selectors.mailSettingsForm) return;
        const mail = state.settings?.mail || {};
        selectors.mailSettingsForm.querySelector('[name="from_name"]').value = mail.from_name || '';
        selectors.mailSettingsForm.querySelector('[name="from_email"]').value = mail.from_email || '';
        selectors.mailSettingsForm.querySelector('[name="notification_email"]').value = mail.notification_email || '';
        selectors.mailSettingsForm.querySelector('[name="reply_to"]').value = mail.reply_to || '';
    };

    const populateAccountForm = () => {
        const user = state.user || window.APP_USER || {};
        if (selectors.accountForm) {
            const nameInput = selectors.accountForm.querySelector('[name="name"]');
            const emailInput = selectors.accountForm.querySelector('[name="email"]');
            const idInput = selectors.accountForm.querySelector('[name="user_id"]');
            if (nameInput) nameInput.value = user.name || '';
            if (emailInput) emailInput.value = user.email || '';
            if (idInput) idInput.value = user.id || '';
        }
        if (selectors.passwordForm) {
            const idInput = selectors.passwordForm.querySelector('[name="user_id"]');
            if (idInput) {
                idInput.value = user.id || '';
            }
        }
    };

    const updateTemplateSelections = () => {
        const current = state.menuTemplate || state.settings?.menu?.template || 'menu1';
        state.menuTemplate = current;
        if (!selectors.menuTemplateForm) return;
        const cards = selectors.menuTemplateForm.querySelectorAll('.template-card');
        cards.forEach((card) => {
            const input = card.querySelector('input[name="template"]');
            if (!input) {
                return;
            }
            const isActive = input.value === current;
            card.classList.toggle('active', isActive);
            input.checked = isActive;
        });
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

    const populateCurrencySelect = () => {
        const select = document.querySelector('#currencySelectAdmin');
        if (!select) return;
        const currencies = state.settings?.currencies || [];
        const defaultCode = (state.settings?.restaurant?.currency || select.dataset.default || 'TRY').toUpperCase();
        if (!currencies.length) {
            select.innerHTML = `<option value="${defaultCode}">${defaultCode}</option>`;
            select.value = defaultCode;
            select.disabled = false;
            return;
        }
        select.disabled = false;
        select.innerHTML = currencies
            .map((currency) => {
                const code = (currency.code || '').toUpperCase();
                const name = escapeHtml(currency.name || code);
                const symbol = currency.symbol ? escapeHtml(currency.symbol) : '';
                const label = symbol ? `${name} (${symbol})` : name;
                const selected = code === defaultCode ? 'selected' : '';
                return `<option value="${code}" ${selected}>${code} &mdash; ${label}</option>`;
            })
            .join('');
        select.value = defaultCode;
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
            populateCurrencySelect();
            return;
        }
        selectors.currencyBadges.innerHTML = currencies
            .map((currency) => {
                const badgeClass = Number(currency.is_default) === 1 ? 'bg-success' : 'bg-secondary';
                return `<span class="badge rounded-pill ${badgeClass} me-2 mb-2">${escapeHtml(currency.code)} &mdash; ${escapeHtml(currency.name || currency.code)}</span>`;
            })
            .join('');
        populateCurrencySelect();
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

    const bindDailyMenuActions = () => {
        selectors.newDailyMenuButton?.addEventListener('click', () => openDailyMenuModal());
        selectors.saveDailyMenu?.addEventListener('click', saveDailyMenuItem);
        selectors.dailyMenuList?.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-daily-edit], [data-daily-delete], [data-daily-up], [data-daily-down]');
            if (!button) return;
            if (button.dataset.dailyEdit) {
                openDailyMenuModal(button.dataset.dailyEdit);
                return;
            }
            if (button.dataset.dailyDelete) {
                await deleteDailyMenuItem(button.dataset.dailyDelete);
                return;
            }
            if (button.dataset.dailyUp) {
                await moveDailyMenuItem(button.dataset.dailyUp, 'up');
                return;
            }
            if (button.dataset.dailyDown) {
                await moveDailyMenuItem(button.dataset.dailyDown, 'down');
            }
        });
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
        if (selectors.menuTemplateForm) {
            selectors.menuTemplateForm.addEventListener('change', (event) => {
                if (event.target.matches('input[name="template"]')) {
                    state.menuTemplate = event.target.value;
                    updateTemplateSelections();
                }
            });
        }
        handleSettingsSubmit(selectors.menuTemplateForm, (formData) => ({ menu: { template: formData.get('template') } }), reloadSettings);
        handleSettingsSubmit(selectors.brandingForm, (formData) => ({ branding: Object.fromEntries(formData.entries()) }), reloadSettings);
        handleSettingsSubmit(selectors.qrForm, (formData) => ({ qr: Object.fromEntries(formData.entries()) }), reloadSettings);
        handleSettingsSubmit(selectors.notificationsForm, (formData) => ({ notifications: Object.fromEntries(formData.entries()) }), reloadSettings);
        handleSettingsSubmit(selectors.mailSettingsForm, (formData) => ({ mail: Object.fromEntries(formData.entries()) }), reloadSettings);
        selectors.flashToggle?.addEventListener('change', () => {
            selectors.flashToggle.setAttribute('aria-checked', selectors.flashToggle.checked ? 'true' : 'false');
        });
    };

    const bindAccountForm = () => {
        if (!selectors.accountForm) return;
        selectors.accountForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(selectors.accountForm);
            const payload = Object.fromEntries(formData.entries());
            const userId = payload.user_id || state.user?.id;
            if (!userId) {
                toast.fire({ icon: 'error', title: 'Kullanıcı bilgisi bulunamadı.' });
                return;
            }
            try {
                const data = await fetchJSON('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update-profile',
                        user_id: Number(userId),
                        name: payload.name,
                        email: payload.email,
                    }),
                });
                if (data.user) {
                    state.user = data.user;
                    window.APP_USER = data.user;
                    populateAccountForm();
                }
                toast.fire({ icon: 'success', title: data.message || 'Bilgiler güncellendi.' });
            } catch (error) {
                toast.fire({ icon: 'error', title: error.message });
            }
        });
    };

    const bindPasswordForm = () => {
        if (!selectors.passwordForm) return;
        selectors.passwordForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(selectors.passwordForm);
            const payload = Object.fromEntries(formData.entries());
            const userId = payload.user_id || state.user?.id;
            if (!userId) {
                toast.fire({ icon: 'error', title: 'Kullanıcı bilgisi bulunamadı.' });
                return;
            }
            if (!payload.new_password || payload.new_password.trim() === '') {
                toast.fire({ icon: 'error', title: 'Yeni şifre zorunludur.' });
                return;
            }
            if ((payload.confirm_password || '').trim() !== payload.new_password.trim()) {
                toast.fire({ icon: 'error', title: 'Yeni şifreler eşleşmiyor.' });
                return;
            }
            try {
                const data = await fetchJSON('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update-password',
                        user_id: Number(userId),
                        current_password: payload.current_password,
                        new_password: payload.new_password,
                    }),
                });
                selectors.passwordForm.reset();
                const hiddenId = selectors.passwordForm.querySelector('[name="user_id"]');
                if (hiddenId) hiddenId.value = userId;
                toast.fire({ icon: 'success', title: data.message || 'Şifre güncellendi.' });
            } catch (error) {
                toast.fire({ icon: 'error', title: error.message });
            }
        });
    };

    const bindAuth = () => {
        const attachLogout = (button) => {
            if (!button) return;
            button.addEventListener('click', async (event) => {
                event.preventDefault();
                await fetchJSON('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' }),
                });
                window.location.href = withBase('login');
            });
        };

        attachLogout(selectors.logoutButton);
        attachLogout(selectors.mobileLogout);
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
            autoWidth: false,
            scrollX: true,
            scrollCollapse: true,
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
                url: withBase('api/languages.php'),
                dataSrc: 'languages',
            },
            destroy: true,
            responsive: true,
            autoWidth: false,
            scrollX: true,
            scrollCollapse: true,
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
            autoWidth: false,
            scrollX: true,
            scrollCollapse: true,
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
            const statusText = payload.status || '';
            const title = payload.table ? `${payload.table} - ${statusText}` : `Sipariş ${statusText}`;
            const isNewOrder = statusText.toLowerCase() === 'beklemede';
            const flashMessage = payload.table
                ? `${payload.table} - ${isNewOrder ? 'Yeni Sipariş' : statusText}`
                : (isNewOrder ? 'Yeni Sipariş' : `Sipariş ${statusText}`);
            toast.fire({ icon: 'info', title });
            playAudio(selectors.audioOrder);
            triggerFlash(flashMessage, 'order');
            await fetchOrders();
            await fetchTables();
            await fetchDashboard();
        });

        socket.on('waiter:call', async (payload) => {
            const title = payload.table ? `${payload.table} garson istiyor.` : 'Yeni garson çağrısı';
            toast.fire({ icon: 'warning', title });
            playAudio(selectors.audioNotify);
            const flashMessage = payload.table ? `${payload.table} - Garson Çağrısı` : 'Garson Çağrısı';
            triggerFlash(flashMessage, 'waiter');
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
        bindSidebarToggle();
        bindFlashOverlay();
        bindNavigation();
        bindChartControls();
        initDropzones();
        bindFilters();
        bindOrderActions();
        bindTableActions();
        bindWaiterActions();
        bindMenuManagement();
        bindDailyMenuActions();
        bindSettingsForms();
        bindAccountForm();
        bindPasswordForm();
        populateDefaultLanguage();
        populateCurrencySelect();
        populateTimezones();
        updateBrandingPreviews();
        updateAudioSources();
        updateTemplateSelections();
        populateMailSettings();
        populateAccountForm();
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
            fetchDailyMenu(),
        ]);
    };

    return { init };
})();

document.addEventListener('DOMContentLoaded', () => AdminApp.init());
