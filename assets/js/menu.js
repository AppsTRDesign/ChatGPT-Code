const MenuApp = (() => {
    const strings = window.MENU_STATE?.strings || {};
    const t = (key, fallback = '') => {
        if (Object.prototype.hasOwnProperty.call(strings, key)) {
            return strings[key];
        }
        return fallback || key;
    };
    const format = (text, replacements = {}) => {
        if (!text) {
            return '';
        }
        return Object.entries(replacements).reduce(
            (accumulator, [token, value]) => accumulator.replace(new RegExp(`:${token}`, 'g'), value),
            text,
        );
    };
    const baseUrl = (document.body.dataset.baseUrl || window.MENU_STATE?.baseUrl || window.location.origin).replace(/\/+$/, '');
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

    const resolveAsset = (value, fallback = null) => {
        const source = value || fallback;
        if (!source) {
            return withBase('assets/vendor/demo/coffee-1.png');
        }
        if (/^https?:\/\//i.test(source)) {
            return source;
        }
        return withBase(source);
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

    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const state = {
        categories: [],
        products: [],
        dailyMenu: [],
        selectedCategory: null,
        cart: [],
        baseCurrency: (document.body.dataset.baseCurrency || 'TRY').toUpperCase(),
        currency: (document.body.dataset.currentCurrency || document.body.dataset.baseCurrency || 'TRY').toUpperCase(),
        currencyMeta: window.MENU_STATE?.currencyMeta || {},
        language: document.body.dataset.currentLanguage || (window.MENU_STATE?.languages?.[0] || 'tr'),
        tableId: Number(document.body.dataset.tableId || 0),
        tableName: window.MENU_STATE?.tableName || '',
        orders: [],
        overlayProduct: null,
        overlayVariant: null,
        overlayQuantity: 1,
        activeNav: 'homePage',
        sortOption: 'name_asc',
        contact: window.MENU_STATE?.contact || {},
    };

    const elements = {
        categories: document.querySelector('#menuCategories'),
        products: document.querySelector('#menuProducts'),
        dailySlider: document.querySelector('#dailySlider'),
        cartSummary: document.querySelector('#cartSummary'),
        currencySelect: document.querySelector('#currencySelect'),
        languageSelect: document.querySelector('#languageSelect'),
        waiterButton: document.querySelector('#callWaiter'),
        cartButton: document.querySelector('#cartButton'),
        searchInput: document.querySelector('#searchMenu'),
        sortSelect: document.querySelector('#sortProducts'),
        cartDrawer: document.querySelector('#cartDrawer'),
        cartBackdrop: document.querySelector('#cartBackdrop'),
        closeCart: document.querySelector('#closeCart'),
        cartItems: document.querySelector('#cartItems'),
        cartTotal: document.querySelector('#cartTotal'),
        clearCart: document.querySelector('#clearCart'),
        submitOrder: document.querySelector('#submitOrder'),
        productOverlay: document.querySelector('#productOverlay'),
        overlayProductName: document.querySelector('#overlayProductName'),
        overlayProductDescription: document.querySelector('#overlayProductDescription'),
        overlayVariants: document.querySelector('#overlayVariants'),
        qtyDecrease: document.querySelector('#qtyDecrease'),
        qtyIncrease: document.querySelector('#qtyIncrease'),
        qtyValue: document.querySelector('#qtyValue'),
        addToCartButton: document.querySelector('#addToCartButton'),
        closeProductOverlay: document.querySelector('#closeProductOverlay'),
        orderStatusList: document.querySelector('#orderStatusList'),
        orderPage: document.querySelector('#ordersPage'),
        refreshOrders: document.querySelector('#refreshOrders'),
        audioOrder: document.querySelector('#audioOrder'),
        audioNotify: document.querySelector('#audioNotify'),
        bottomNav: document.querySelector('#menuBottomNav'),
        contactForm: document.querySelector('#contactForm'),
        contactFeedback: document.querySelector('#contactFeedback'),
        categoryPageList: document.querySelector('#categoryPageList'),
        categoryPageProducts: document.querySelector('#categoryPageProducts'),
        pages: document.querySelectorAll('.menu-page'),
    };

    const socket = io('https://qrmenu.noasoft.org:4000');

    const toast = Swal.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 3200,
    });

    const fetchJSON = async (url, options = {}) => {
        const response = await fetch(withBase(url), options);
        const data = await response.json();
        if (data.error) {
            throw new Error(data.message || t('messages.error_generic', 'The operation could not be completed.'));
        }
        return data;
    };

    const isFriendlyPath = () => window.location.pathname.startsWith('/menu');

    const updateMenuLocation = (lang, currency, replace = true) => {
        const targetLang = (lang || state.language || 'tr').toLowerCase();
        const targetCurrency = (currency || state.currency || state.baseCurrency).toUpperCase();

        if (isFriendlyPath() && state.tableId) {
            const newPath = `/menu/${state.tableId}/${targetLang}/${targetCurrency}`;
            if (replace) {
                window.history.replaceState({}, '', newPath);
            } else {
                window.location.href = newPath;
            }
            return;
        }

        const params = new URLSearchParams(window.location.search);
        if (state.tableId) {
            params.set('table', state.tableId);
        }
        params.set('lang', targetLang);
        params.set('currency', targetCurrency);
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        if (replace) {
            window.history.replaceState({}, '', newUrl);
        } else {
            window.location.href = newUrl;
        }
    };

    const parseNumeric = (value) => {
        if (value === undefined || value === null) {
            return null;
        }
        const numeric = parseFloat(String(value).replace(/,/g, ''));
        return Number.isFinite(numeric) ? numeric : null;
    };

    let cachedRates = {};

    const refreshPriceViews = () => {
        renderDailyMenu();
        renderProducts();
        updateCartSummary();
        renderCart();
        updateOverlayButton();
        renderOrderStatus();
    };

    const convertPrice = (price) => {
        const amount = Number(price) || 0;
        const key = `${state.baseCurrency}_${state.currency}`;

        if (state.currency === state.baseCurrency) return amount.toFixed(2);

        if (cachedRates[key]) {
            return (amount * cachedRates[key]).toFixed(2);
        }

        fetchJSON(`api/currency.php?from=${state.baseCurrency}&to=${state.currency}&amount=1`)
            .then((data) => {
                const rate =
                    parseNumeric(data.secondary) ??
                    parseNumeric(data.rate) ??
                    parseNumeric(data.primary) ??
                    parseNumeric(data.amount);
                if (rate) {
                    cachedRates[key] = rate;
                    refreshPriceViews();
                }
            })
            .catch((error) => console.error(t('messages.currency_fetch', 'Currency conversion failed'), error));

        return amount.toFixed(2);
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

    const loadMenu = async () => {
        const data = await fetchJSON('api/menu.php');
        state.categories = data.categories || [];
        state.products = data.products || [];
        state.dailyMenu = data.daily_menu || data.daily || [];
        renderCategories();
        renderDailyMenu();
        renderProducts();
        updateCartSummary();
    };

    const selectCategory = (categoryId = null) => {
        state.selectedCategory = categoryId === null ? null : Number(categoryId);
        renderCategories();
        renderProducts();
    };

    const renderCategoryContainer = (container, categories, { includeAll = true } = {}) => {
        if (!container) return;
        container.innerHTML = '';

        const addButton = (label, iconHtml, isActive, onClick) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `category-card ${isActive ? 'active' : ''}`;
            button.innerHTML = `${iconHtml}<strong>${escapeHtml(label)}</strong>`;
            button.addEventListener('click', onClick);
            container.appendChild(button);
        };

        if (includeAll) {
            addButton(t('menu.categories_all', 'All'), '<span class="badge">🍽️</span>', state.selectedCategory === null, () => selectCategory(null));
        }

        categories.forEach((category) => {
            const iconHtml = category.image
                ? `<img src="${resolveAsset(category.image)}" alt="${escapeHtml(category.name)}" loading="lazy">`
                : category.icon
                    ? `<span class="badge"><i class="${resolveIconClass(category.icon)}"></i></span>`
                    : '<span class="badge">🍽️</span>';
            addButton(
                category.name,
                iconHtml,
                Number(state.selectedCategory) === Number(category.id),
                () => selectCategory(Number(category.id)),
            );
        });
    };

    const renderCategories = () => {
        const sortedCategories = [...state.categories].sort((a, b) => a.name.localeCompare(b.name, 'tr'));
        renderCategoryContainer(elements.categories, sortedCategories, { includeAll: true });
        renderCategoryContainer(elements.categoryPageList, sortedCategories, { includeAll: true });
    };

    const renderDailyMenu = () => {
        if (!elements.dailySlider) return;
        elements.dailySlider.innerHTML = '';

        if (!state.dailyMenu.length) {
            elements.dailySlider.innerHTML = `<p class="text-muted mb-0">${escapeHtml(t('menu.daily_empty', 'Daily menu is being prepared.'))}</p>`;
            return;
        }

        state.dailyMenu.forEach((item) => {
            const product = state.products.find((entry) => Number(entry.id) === Number(item.product_id))
                || item.product
                || null;
            if (!product) {
                return;
            }
            const image = resolveAsset(product.image, 'assets/vendor/demo/coffee-1.png');
            const headline = item.headline || product.name || '';
            const tagline = item.tagline || product.description || '';
            const badge = item.badge || '';
            const priceText = `${convertPrice(product.price)} ${state.currency}`;
            const card = document.createElement('article');
            card.className = 'daily-card';
            card.innerHTML = `
                <div class="daily-card__image">
                    <img src="${image}" alt="${escapeHtml(headline)}" loading="lazy">
                    ${badge ? `<span class="daily-card__badge">${escapeHtml(badge)}</span>` : ''}
                </div>
                <div class="daily-card__body">
                    <strong class="daily-card__price">${priceText}</strong>
                    <h3>${escapeHtml(headline)}</h3>
                    ${tagline ? `<p>${escapeHtml(tagline)}</p>` : ''}
                    <button type="button" class="daily-card__action" data-daily-add="${product.id}">${window.MENU_STATE?.addToCartText || t('menu.add_to_cart', 'Add to Cart')}</button>
                </div>
            `;
            card.querySelector('[data-daily-add]')?.addEventListener('click', (event) => {
                event.stopPropagation();
                openProductOverlay(product);
            });
            card.addEventListener('click', () => openProductOverlay(product));
            elements.dailySlider.appendChild(card);
        });
    };

    const sortProducts = (products) => {
        const items = [...products];
        const parsePrice = (value) => Number.parseFloat(value) || 0;
        switch (state.sortOption) {
            case 'name_desc':
                return items.sort((a, b) => b.name.localeCompare(a.name, 'tr'));
            case 'price_asc':
                return items.sort((a, b) => parsePrice(a.price) - parsePrice(b.price));
            case 'price_desc':
                return items.sort((a, b) => parsePrice(b.price) - parsePrice(a.price));
            case 'name_asc':
            default:
                return items.sort((a, b) => a.name.localeCompare(b.name, 'tr'));
        }
    };

    const filterProducts = () => {
        const query = elements.searchInput?.value.trim().toLowerCase() || '';
        const filtered = state.products.filter((product) => {
            if (state.selectedCategory && Number(product.category_id) !== Number(state.selectedCategory)) {
                return false;
            }
            if (!query) return true;
            const normalizedName = product.name.toLowerCase();
            const normalizedDescription = (product.description || '').toLowerCase();
            return normalizedName.includes(query) || normalizedDescription.includes(query);
        });
        return sortProducts(filtered);
    };

    const buildProductCard = (product) => {
        const card = document.createElement('div');
        card.className = 'product-card';
        const productImage = resolveAsset(product.image, 'assets/vendor/demo/coffee-1.png');
        const priceText = `${convertPrice(product.price)} ${state.currency}`;
        card.innerHTML = `
            <img src="${productImage}" alt="${escapeHtml(product.name)}" loading="lazy" />
            <div class="product-card__body">
                <h3>${escapeHtml(product.name)}</h3>
                ${product.description ? `<p>${escapeHtml(product.description)}</p>` : ''}
                <div class="product-card__price">${priceText}</div>
            </div>
            <button type="button" class="product-card__action" data-product="${product.id}">
                <i class="bx bx-cart-add"></i>
                <span>${window.MENU_STATE?.addToCartText || t('menu.add_to_cart', 'Add to Cart')}</span>
            </button>
        `;
        const actionButton = card.querySelector('.product-card__action');
        actionButton?.addEventListener('click', (event) => {
            event.stopPropagation();
            openProductOverlay(product);
        });
        card.addEventListener('click', () => openProductOverlay(product));
        return card;
    };

    const renderProductList = (container, products, emptyMessage) => {
        if (!container) return;
        container.innerHTML = '';
        if (!products.length) {
            container.innerHTML = `<p class="text-muted mb-0">${escapeHtml(emptyMessage)}</p>`;
            return;
        }
        products.forEach((product) => {
            container.appendChild(buildProductCard(product));
        });
    };

    const renderProducts = () => {
        const filtered = filterProducts();
        renderProductList(elements.products, filtered, t('menu.products_empty', 'No products match your filters.'));
        renderProductList(elements.categoryPageProducts, filtered, t('menu.category_empty', 'No products were found in this category.'));
    };

    const openProductOverlay = (product) => {
        state.overlayProduct = product;
        state.overlayVariant = null;
        state.overlayQuantity = 1;
        if (!elements.cartDrawer?.classList.contains('d-none')) {
            toggleCart(false);
        }
        if (!elements.productOverlay) {
            addToCart(product, null, 1);
            return;
        }
        elements.overlayProductName.textContent = product.name;
        elements.overlayProductDescription.textContent = product.description || '';
        elements.qtyValue.textContent = '1';
        renderOverlayVariants(product);
        elements.productOverlay.classList.remove('d-none');
        elements.cartBackdrop?.classList.remove('d-none');
    };

    const closeProductOverlay = () => {
        elements.productOverlay?.classList.add('d-none');
        if (elements.cartDrawer?.classList.contains('d-none')) {
            elements.cartBackdrop?.classList.add('d-none');
        }
    };

    const renderOverlayVariants = (product) => {
        if (!elements.overlayVariants) return;
        elements.overlayVariants.innerHTML = '';
        const variants = product.variants && product.variants.length ? product.variants : [{ id: null, name: t('menu.variant_default', 'Standard'), price: product.price }];
        variants.forEach((variant, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `overlay-variant ${index === 0 ? 'active' : ''}`;
            const priceLabel = `${convertPrice(variant.price)} ${state.currency}`;
            button.innerHTML = `
                <span>${variant.name}</span>
                <strong>${priceLabel}</strong>
            `;
            button.addEventListener('click', () => {
                state.overlayVariant = variant;
                elements.overlayVariants.querySelectorAll('.overlay-variant').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
            });
            elements.overlayVariants.appendChild(button);
            if (index === 0) {
                state.overlayVariant = variant;
            }
        });
        updateOverlayButton();
    };

    const updateOverlayButton = () => {
        if (!elements.addToCartButton || !state.overlayVariant) return;
        const total = state.overlayVariant.price * state.overlayQuantity;
        const addToCartLabel = window.MENU_STATE?.addToCartText || t('menu.add_to_cart', 'Add to Cart');
        elements.addToCartButton.textContent = `${addToCartLabel} • ${convertPrice(total)} ${state.currency}`;
    };

    const changeOverlayQuantity = (delta) => {
        state.overlayQuantity = Math.max(1, state.overlayQuantity + delta);
        elements.qtyValue.textContent = state.overlayQuantity;
    };

    const addToCart = (product, variant = null, quantity = 1) => {
        const variantId = variant?.id || null;
        const variantName = variant?.name || null;
        const unitPrice = variant?.price || product.price;
        const key = `${product.id}-${variantId || 'base'}`;
        const existing = state.cart.find((item) => item.key === key);
        if (existing) {
            existing.qty += quantity;
        } else {
            state.cart.push({
                key,
                id: product.id,
                variantId,
                name: product.name,
                variantName,
                price: Number(unitPrice),
                qty: quantity,
                image: resolveAsset(product.image, 'assets/vendor/demo/coffee-1.png'),
            });
        }
        updateCartSummary();
        renderCart();
        toast.fire({ icon: 'success', title: format(t('menu.toast_added', ':item has been added to your cart.'), { item: product.name }) });
    };

    const updateCartSummary = () => {
        if (!elements.cartSummary) return;
        const totalQty = state.cart.reduce((total, item) => total + item.qty, 0);
        const totalAmount = state.cart.reduce((total, item) => total + item.qty * item.price, 0);
        const convertedTotal = convertPrice(totalAmount);
        const summaryTemplate = t('menu.cart.summary', ':count items');
        const summaryText = format(summaryTemplate, { count: totalQty });
        elements.cartSummary.innerHTML = `
            <span>${escapeHtml(summaryText)}</span>
            <strong>${convertedTotal} ${state.currency}</strong>
        `;
        if (elements.cartTotal) {
            elements.cartTotal.textContent = `${convertedTotal} ${state.currency}`;
        }
    };

    const renderCart = () => {
        if (!elements.cartItems) return;
        elements.cartItems.innerHTML = '';
        if (!state.cart.length) {
            elements.cartItems.innerHTML = `<p class="text-muted">${escapeHtml(t('menu.cart.empty', 'Your cart is empty.'))}</p>`;
            return;
        }
        state.cart.forEach((item) => {
            const row = document.createElement('div');
            row.className = 'cart-item';
            row.innerHTML = `
                <div>
                    <strong>${item.name}</strong>
                    ${item.variantName ? `<small>${item.variantName}</small>` : ''}
                </div>
                <div class="cart-item__actions">
                    <div class="quantity-picker">
                        <button type="button" data-qty-minus="${item.key}">-</button>
                        <span>${item.qty}</span>
                        <button type="button" data-qty-plus="${item.key}">+</button>
                    </div>
                    <div class="cart-item__price">
                        <span>${convertPrice(item.price)} ${state.currency}</span>
                        <strong>${convertPrice(item.price * item.qty)} ${state.currency}</strong>
                    </div>
                    <button type="button" class="btn btn-link text-danger p-0" data-remove="${item.key}">${escapeHtml(t('menu.cart.remove', 'Remove'))}</button>
                </div>
            `;
            elements.cartItems.appendChild(row);
        });
    };

    const toggleCart = (show) => {
        if (!elements.cartDrawer || !elements.cartBackdrop) return;
        if (show) {
            closeProductOverlay();
            elements.cartDrawer.classList.remove('d-none');
            elements.cartBackdrop.classList.remove('d-none');
        } else {
            elements.cartDrawer.classList.add('d-none');
            if (elements.productOverlay?.classList.contains('d-none')) {
                elements.cartBackdrop.classList.add('d-none');
            }
        }
    };

    const clearCart = () => {
        state.cart = [];
        renderCart();
        updateCartSummary();
    };

    const submitOrder = async () => {
        if (!state.cart.length) {
            toast.fire({ icon: 'info', title: t('menu.cart.empty', 'Your cart is empty.') });
            return;
        }
        if (!state.tableId) {
            toast.fire({ icon: 'error', title: t('menu.table_missing', 'Table information could not be found.') });
            return;
        }
        const payload = {
            table_id: state.tableId,
            items: state.cart.map((item) => ({
                product_id: item.id,
                variant_id: item.variantId,
                quantity: item.qty,
            })),
        };
        try {
            const response = await fetchJSON('api/order-create.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            toast.fire({ icon: 'success', title: response.message || t('menu.order_success', 'Your order has been received.') });
            clearCart();
            toggleCart(false);
            playAudio(elements.audioOrder);
            const fallbackTableName = `${t('menu.table_prefix', 'Table')} ${state.tableId}`;
            socket.emit('order:new', { table: state.tableName || fallbackTableName, table_id: state.tableId });
            await refreshOrderStatus();
        } catch (error) {
            toast.fire({ icon: 'error', title: error.message });
        }
    };

    const refreshOrderStatus = async () => {
        if (!state.tableId || !elements.orderStatusList) return;
        try {
            const data = await fetchJSON(`api/order-status.php?table_id=${state.tableId}`);
            state.orders = data.orders || [];
        } catch (error) {
            console.error(t('messages.order_status_failed', 'Unable to load order status'), error);
            state.orders = [];
        }
        renderOrderStatus();
    };

    const renderOrderStatus = () => {
        if (!elements.orderStatusList) return;
        elements.orderStatusList.innerHTML = '';
        if (!state.orders.length) {
            elements.orderStatusList.innerHTML = `<p class="text-muted mb-0">${escapeHtml(t('menu.orders_empty', 'You do not have any active orders.'))}</p>`;
            return;
        }
        state.orders.forEach((order) => {
            const card = document.createElement('div');
            card.className = 'order-track-card';
            const totalAmount = `${convertPrice(order.total)} ${state.currency}`;
            card.innerHTML = `
                    <div class="order-track-card__head">
                        <div>
                            <h3>#${order.id}</h3>
                            <small>${order.created_at}</small>
                        </div>
                    <span class="badge status-${statusToClass(order.status)}">${order.status}</span>
                </div>
                <ul class="order-track-card__items">
                    ${(order.items || []).map((item) => `
                        <li>
                            <span>${item.name}${item.variant_name ? ` <small>${item.variant_name}</small>` : ''}</span>
                            <span>${item.quantity}</span>
                        </li>
                    `).join('')}
                </ul>
                <div class="order-track-card__footer">
                    <strong>${totalAmount}</strong>
                </div>
            `;
            elements.orderStatusList.appendChild(card);
        });
    };

    const applyAudioSources = () => {
        if (elements.audioOrder && window.MENU_STATE?.orderSound) {
            elements.audioOrder.src = window.MENU_STATE.orderSound;
            elements.audioOrder.load?.();
        }
        if (elements.audioNotify && window.MENU_STATE?.waiterSound) {
            elements.audioNotify.src = window.MENU_STATE.waiterSound;
            elements.audioNotify.load?.();
        }
    };

    const playAudio = (audioElement) => {
        if (!audioElement) return;
        audioElement.currentTime = 0;
        audioElement.play().catch(() => {});
    };

    const highlightBottomNav = (targetId) => {
        if (!elements.bottomNav) return;
        elements.bottomNav.querySelectorAll('.menu-bottom-nav__item').forEach((item) => {
            item.classList.toggle('active', item.dataset.target === targetId);
        });
    };

    const setActivePage = (pageId) => {
        state.activeNav = pageId;
        const pages = elements.pages ? Array.from(elements.pages) : Array.from(document.querySelectorAll('.menu-page'));
        pages.forEach((page) => {
            page.classList.toggle('is-active', page.id === pageId);
        });
        highlightBottomNav(pageId);
        window.scrollTo({ top: 0, behavior: 'smooth' });
        if (pageId === 'ordersPage') {
            refreshOrderStatus();
        }
    };

    const bindBottomNav = () => {
        if (!elements.bottomNav) return;
        elements.bottomNav.addEventListener('click', (event) => {
            const button = event.target.closest('.menu-bottom-nav__item');
            if (!button) return;
            const action = button.dataset.action;
            if (action === 'cart') {
                toggleCart(true);
                return;
            }
            const targetId = button.dataset.target;
            if (targetId) {
                setActivePage(targetId);
            }
        });
    };

    const bindContactForm = () => {
        if (!elements.contactForm) return;
        elements.contactForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(elements.contactForm);
            const payload = Object.fromEntries(formData.entries());
            if (elements.contactFeedback) {
                elements.contactFeedback.textContent = t('contact.sending', 'Sending your message...');
                elements.contactFeedback.className = 'contact-feedback text-muted';
            }
            try {
                const data = await fetchJSON('api/contact.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                if (elements.contactFeedback) {
                    elements.contactFeedback.textContent = data.message || t('contact.success', 'Your message has been sent successfully.');
                    elements.contactFeedback.className = 'contact-feedback text-success';
                }
                elements.contactForm.reset();
            } catch (error) {
                if (elements.contactFeedback) {
                    elements.contactFeedback.textContent = error.message;
                    elements.contactFeedback.className = 'contact-feedback text-danger';
                }
            }
        });
    };

    const bindEvents = () => {
        elements.currencySelect?.addEventListener('change', (event) => {
            state.currency = event.target.value.toUpperCase();
            cachedRates = {};
            refreshPriceViews();
            updateMenuLocation(state.language, state.currency, true);
        });

        elements.languageSelect?.addEventListener('change', (event) => {
            state.language = event.target.value.toLowerCase();
            updateMenuLocation(state.language, state.currency, false);
        });

        elements.waiterButton?.addEventListener('click', async () => {
            if (!state.tableId) {
                toast.fire({ icon: 'error', title: t('menu.table_missing', 'Table information could not be found.') });
                return;
            }
            try {
                await fetchJSON('api/waiter-calls.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ table_id: state.tableId }),
                });
                toast.fire({ icon: 'success', title: t('menu.waiter_called', 'Waiter call sent.') });
                const fallbackTableName = `${t('menu.table_prefix', 'Table')} ${state.tableId}`;
                socket.emit('waiter:call', { table: state.tableName || fallbackTableName, table_id: state.tableId });
                playAudio(elements.audioNotify);
            } catch (error) {
                toast.fire({ icon: 'error', title: error.message });
            }
        });

        elements.cartButton?.addEventListener('click', () => toggleCart(true));
        elements.cartBackdrop?.addEventListener('click', () => {
            toggleCart(false);
            closeProductOverlay();
        });
        elements.closeCart?.addEventListener('click', () => toggleCart(false));
        elements.clearCart?.addEventListener('click', clearCart);
        elements.submitOrder?.addEventListener('click', submitOrder);
        elements.cartItems?.addEventListener('click', (event) => {
            const minus = event.target.closest('[data-qty-minus]');
            const plus = event.target.closest('[data-qty-plus]');
            const remove = event.target.closest('[data-remove]');
            if (minus) {
                const item = state.cart.find((entry) => entry.key === minus.dataset.qtyMinus);
                if (item) {
                    item.qty = Math.max(1, item.qty - 1);
                    renderCart();
                    updateCartSummary();
                }
            }
            if (plus) {
                const item = state.cart.find((entry) => entry.key === plus.dataset.qtyPlus);
                if (item) {
                    item.qty += 1;
                    renderCart();
                    updateCartSummary();
                }
            }
            if (remove) {
                state.cart = state.cart.filter((entry) => entry.key !== remove.dataset.remove);
                renderCart();
                updateCartSummary();
            }
        });

        elements.closeProductOverlay?.addEventListener('click', closeProductOverlay);
        elements.qtyDecrease?.addEventListener('click', () => {
            changeOverlayQuantity(-1);
            updateOverlayButton();
        });
        elements.qtyIncrease?.addEventListener('click', () => {
            changeOverlayQuantity(1);
            updateOverlayButton();
        });
        elements.addToCartButton?.addEventListener('click', () => {
            if (!state.overlayProduct || !state.overlayVariant) return;
            addToCart(state.overlayProduct, state.overlayVariant, state.overlayQuantity);
            closeProductOverlay();
        });

        elements.overlayVariants?.addEventListener('click', (event) => {
            const button = event.target.closest('.overlay-variant');
            if (!button || !state.overlayProduct) return;
            const index = Array.from(elements.overlayVariants.children).indexOf(button);
            const variants = state.overlayProduct.variants && state.overlayProduct.variants.length
                ? state.overlayProduct.variants
                : [{ id: null, name: t('menu.variant_default', 'Standard'), price: state.overlayProduct.price }];
            state.overlayVariant = variants[index];
            elements.overlayVariants.querySelectorAll('.overlay-variant').forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
            updateOverlayButton();
        });

        elements.searchInput?.addEventListener('input', () => renderProducts());
        if (elements.sortSelect) {
            elements.sortSelect.value = state.sortOption;
            elements.sortSelect.addEventListener('change', (event) => {
                state.sortOption = event.target.value;
                renderProducts();
            });
        }
        elements.refreshOrders?.addEventListener('click', refreshOrderStatus);
    };

    const sameTable = (payload = {}) => {
        if (!state.tableId) {
            return false;
        }
        if (payload.table_id) {
            return Number(payload.table_id) === Number(state.tableId);
        }
        if (state.tableName) {
            const fallbackTableName = `${t('menu.table_prefix', 'Table')} ${state.tableId}`;
            return payload.table === state.tableName || payload.table === fallbackTableName;
        }
        return true;
    };

    const bindSocket = () => {
        socket.on('waiter:update', async (payload) => {
            if (!sameTable(payload)) {
                return;
            }
            toast.fire({ icon: 'info', title: format(t('menu.waiter_status', 'Waiter status: :status'), { status: payload.status }) });
            playAudio(elements.audioNotify);
            await refreshOrderStatus();
        });

        socket.on('waiter:call', (payload) => {
            if (!sameTable(payload)) {
                return;
            }
            toast.fire({ icon: 'info', title: t('menu.waiter_notice', 'Your waiter request has been sent.') });
            playAudio(elements.audioNotify);
        });

        socket.on('order:update', async (payload) => {
            if (!sameTable(payload)) {
                return;
            }
            toast.fire({ icon: 'info', title: format(t('menu.order_status_update', 'Order :status'), { status: payload.status }) });
            playAudio(elements.audioOrder);
            await refreshOrderStatus();
        });
    };

    const init = async () => {
        window.translationAddToCart = window.MENU_STATE?.addToCartText || t('menu.add_to_cart', 'Add to Cart');
        state.language = elements.languageSelect?.value || state.language;
        applyAudioSources();
        await loadMenu();
        refreshPriceViews();
        await refreshOrderStatus();
        bindEvents();
        bindBottomNav();
        setActivePage(state.activeNav || 'homePage');
        bindSocket();
    };

    return { init };
})();

document.addEventListener('DOMContentLoaded', () => MenuApp.init());
