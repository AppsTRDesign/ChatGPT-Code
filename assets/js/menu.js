const MenuApp = (() => {
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

    const state = {
        categories: [],
        products: [],
        selectedCategory: null,
        cart: [],
        baseCurrency: (document.body.dataset.baseCurrency || 'TRY').toUpperCase(),
        currency: (document.body.dataset.currentCurrency || document.body.dataset.baseCurrency || 'TRY').toUpperCase(),
        language: document.body.dataset.currentLanguage || (window.MENU_STATE?.languages?.[0] || 'tr'),
        exchangeRate: 1,
        tableId: Number(document.body.dataset.tableId || 0),
        tableName: window.MENU_STATE?.tableName || '',
        orders: [],
        overlayProduct: null,
        overlayVariant: null,
        overlayQuantity: 1,
    };

    const elements = {
        categories: document.querySelector('#menuCategories'),
        products: document.querySelector('#menuProducts'),
        cartSummary: document.querySelector('#cartSummary'),
        currencySelect: document.querySelector('#currencySelect'),
        languageSelect: document.querySelector('#languageSelect'),
        waiterButton: document.querySelector('#callWaiter'),
        cartButton: document.querySelector('#cartButton'),
        searchInput: document.querySelector('#searchMenu'),
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
        refreshOrders: document.querySelector('#refreshOrders'),
        audioOrder: document.querySelector('#audioOrder'),
        audioNotify: document.querySelector('#audioNotify'),
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
            throw new Error(data.message || 'İşlem gerçekleştirilemedi.');
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

    const convertPrice = (price) => {
        const amount = Number(price) || 0;
        return (amount * state.exchangeRate).toFixed(2);
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
        renderCategories();
        renderProducts();
        updateCartSummary();
    };

    const renderCategories = () => {
        if (!elements.categories) return;
        elements.categories.innerHTML = '';
        const allButton = document.createElement('button');
        allButton.type = 'button';
        allButton.className = `category-card ${state.selectedCategory === null ? 'active' : ''}`;
        allButton.innerHTML = '<span class="badge">🍽️</span><strong>Tümü</strong>';
        allButton.addEventListener('click', () => {
            state.selectedCategory = null;
            renderCategories();
            renderProducts();
        });
        elements.categories.appendChild(allButton);

        [...state.categories].sort((a, b) => a.name.localeCompare(b.name, 'tr')).forEach((category) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `category-card ${Number(state.selectedCategory) === Number(category.id) ? 'active' : ''}`;
            const iconHtml = category.image
                ? `<img src="${resolveAsset(category.image)}" alt="${category.name}" loading="lazy">`
                : category.icon
                    ? `<span class="badge"><i class="${resolveIconClass(category.icon)}"></i></span>`
                    : '<span class="badge">🍽️</span>';
            button.innerHTML = `${iconHtml}<strong>${category.name}</strong>`;
            button.addEventListener('click', () => {
                state.selectedCategory = Number(category.id);
                renderCategories();
                renderProducts();
            });
            elements.categories.appendChild(button);
        });
    };

    const renderProducts = () => {
        if (!elements.products) return;
        elements.products.innerHTML = '';
        const query = elements.searchInput?.value.trim().toLowerCase() || '';
        state.products
            .filter((product) => {
                if (state.selectedCategory && Number(product.category_id) !== Number(state.selectedCategory)) {
                    return false;
                }
                if (!query) return true;
                return product.name.toLowerCase().includes(query) || (product.description || '').toLowerCase().includes(query);
            })
            .forEach((product) => {
                const card = document.createElement('div');
                card.className = 'product-card';
                const productImage = resolveAsset(product.image, 'assets/vendor/demo/coffee-1.png');
                card.innerHTML = `
                    <img src="${productImage}" alt="${product.name}" loading="lazy" />
                    <div>
                        <h3>${product.name}</h3>
                        <p>${product.description || ''}</p>
                        <strong>${convertPrice(product.price)} ${state.currency}</strong>
                    </div>
                    <button type="button" data-product="${product.id}">
                        <span>+</span>
                        <span>${window.MENU_STATE?.addToCartText || 'Sepete Ekle'}</span>
                    </button>
                `;
                card.querySelector('button').addEventListener('click', () => openProductOverlay(product));
                elements.products.appendChild(card);
            });
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
        const variants = product.variants && product.variants.length ? product.variants : [{ id: null, name: 'Standart', price: product.price }];
        variants.forEach((variant, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `overlay-variant ${index === 0 ? 'active' : ''}`;
            button.innerHTML = `
                <span>${variant.name}</span>
                <strong>${convertPrice(variant.price)} ${state.currency}</strong>
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
        elements.addToCartButton.textContent = `${window.MENU_STATE?.addToCartText || 'Sepete Ekle'} • ${convertPrice(total)} ${state.currency}`;
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
        toast.fire({ icon: 'success', title: `${product.name} sepete eklendi.` });
    };

    const updateCartSummary = () => {
        if (!elements.cartSummary) return;
        const totalQty = state.cart.reduce((total, item) => total + item.qty, 0);
        const totalAmount = state.cart.reduce((total, item) => total + item.qty * item.price, 0);
        elements.cartSummary.innerHTML = `
            <span>${totalQty} ürün</span>
            <strong>${convertPrice(totalAmount)} ${state.currency}</strong>
        `;
        if (elements.cartTotal) {
            elements.cartTotal.textContent = `${convertPrice(totalAmount)} ${state.currency}`;
        }
    };

    const renderCart = () => {
        if (!elements.cartItems) return;
        elements.cartItems.innerHTML = '';
        if (!state.cart.length) {
            elements.cartItems.innerHTML = '<p class="text-muted">Sepetiniz boş.</p>';
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
                    <button type="button" class="btn btn-link text-danger p-0" data-remove="${item.key}">Sil</button>
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
            toast.fire({ icon: 'info', title: 'Sepetiniz boş.' });
            return;
        }
        if (!state.tableId) {
            toast.fire({ icon: 'error', title: 'Masa bilgisi eksik.' });
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
            toast.fire({ icon: 'success', title: response.message || 'Siparişiniz alındı.' });
            clearCart();
            toggleCart(false);
            playAudio(elements.audioOrder);
            socket.emit('order:new', { table: state.tableName || `Masa ${state.tableId}`, table_id: state.tableId });
            await refreshOrderStatus();
        } catch (error) {
            toast.fire({ icon: 'error', title: error.message });
        }
    };

    const refreshOrderStatus = async () => {
        if (!state.tableId || !elements.orderStatusList) return;
        const data = await fetchJSON(`api/order-status.php?table_id=${state.tableId}`);
        state.orders = data.orders || [];
        renderOrderStatus();
    };

    const renderOrderStatus = () => {
        if (!elements.orderStatusList) return;
        elements.orderStatusList.innerHTML = '';
        if (!state.orders.length) {
            elements.orderStatusList.innerHTML = '<p class="text-muted">Henüz siparişiniz bulunmuyor.</p>';
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

    const updateExchangeRate = async () => {
        if (state.currency === state.baseCurrency) {
            state.exchangeRate = 1;
            return;
        }
        try {
            const data = await fetchJSON(`api/currency.php?from=${state.baseCurrency}&to=${state.currency}&amount=1`);
            const parseRate = (value) => {
                if (value === undefined || value === null) {
                    return null;
                }
                const numeric = parseFloat(String(value).replace(/,/g, ''));
                return Number.isNaN(numeric) || numeric <= 0 ? null : numeric;
            };
            const rate = parseRate(data.rate) ?? parseRate(data.primary) ?? parseRate(data.secondary);
            state.exchangeRate = rate ?? 1;
        } catch (error) {
            console.error('Kur çevrim hatası', error);
            state.exchangeRate = 1;
        }
        updateOverlayButton();
    };

    const bindEvents = () => {
        elements.currencySelect?.addEventListener('change', async (event) => {
            state.currency = event.target.value.toUpperCase();
            await updateExchangeRate();
            renderProducts();
            updateCartSummary();
            renderCart();
            updateOverlayButton();
            renderOrderStatus();
            updateMenuLocation(state.language, state.currency, true);
        });

        elements.languageSelect?.addEventListener('change', (event) => {
            state.language = event.target.value.toLowerCase();
            updateMenuLocation(state.language, state.currency, false);
        });

        elements.waiterButton?.addEventListener('click', async () => {
            if (!state.tableId) {
                toast.fire({ icon: 'error', title: 'Masa bilgisi bulunamadı.' });
                return;
            }
            try {
                await fetchJSON('api/waiter-calls.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ table_id: state.tableId }),
                });
                toast.fire({ icon: 'success', title: 'Garson çağrısı iletildi.' });
                socket.emit('waiter:call', { table: state.tableName || `Masa ${state.tableId}`, table_id: state.tableId });
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
                : [{ id: null, name: 'Standart', price: state.overlayProduct.price }];
            state.overlayVariant = variants[index];
            elements.overlayVariants.querySelectorAll('.overlay-variant').forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
            updateOverlayButton();
        });

        elements.searchInput?.addEventListener('input', () => renderProducts());
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
            return payload.table === state.tableName || payload.table === `Masa ${state.tableId}`;
        }
        return true;
    };

    const bindSocket = () => {
        socket.on('waiter:update', async (payload) => {
            if (!sameTable(payload)) {
                return;
            }
            toast.fire({ icon: 'info', title: `Garson durumu: ${payload.status}` });
            playAudio(elements.audioNotify);
            await refreshOrderStatus();
        });

        socket.on('waiter:call', (payload) => {
            if (!sameTable(payload)) {
                return;
            }
            toast.fire({ icon: 'info', title: 'Garson çağrınız iletildi.' });
            playAudio(elements.audioNotify);
        });

        socket.on('order:update', async (payload) => {
            if (!sameTable(payload)) {
                return;
            }
            toast.fire({ icon: 'info', title: `Sipariş ${payload.status}` });
            playAudio(elements.audioOrder);
            await refreshOrderStatus();
        });
    };

    const init = async () => {
        window.translationAddToCart = window.MENU_STATE?.addToCartText || 'Sepete Ekle';
        state.language = elements.languageSelect?.value || state.language;
        applyAudioSources();
        await updateExchangeRate();
        await loadMenu();
        await refreshOrderStatus();
        bindEvents();
        bindSocket();
    };

    return { init };
})();

document.addEventListener('DOMContentLoaded', () => MenuApp.init());
