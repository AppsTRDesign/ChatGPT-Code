const MenuApp = (() => {
    const state = {
        categories: [],
        products: [],
        cart: [],
        baseCurrency: document.body.dataset.baseCurrency || 'TRY',
        currency: document.body.dataset.currentCurrency || document.body.dataset.baseCurrency || 'TRY',
        exchangeRate: 1,
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
    };

    const socket = io('https://qrmenu.noasoft.org:4000');

    const toast = Swal.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 3200,
    });

    const loadMenu = async () => {
        const response = await fetch('api/menu.php');
        const data = await response.json();
        state.categories = data.categories || [];
        state.products = data.products || [];
        renderCategories();
        renderProducts();
        updateCartSummary();
    };

    const renderCategories = () => {
        if (!elements.categories) return;
        elements.categories.innerHTML = '';
        state.categories.forEach((category) => {
            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'category-card';
            card.innerHTML = `
                <span class="badge">${category.icon || '🍽️'}</span>
                <strong>${category.name}</strong>
            `;
            card.addEventListener('click', () => renderProducts(category.id));
            elements.categories.appendChild(card);
        });
    };

    const renderProducts = (categoryId = null) => {
        if (!elements.products) return;
        elements.products.innerHTML = '';
        const products = categoryId
            ? state.products.filter((product) => Number(product.category_id) === Number(categoryId))
            : state.products;

        products
            .filter(filterBySearch)
            .forEach((product) => {
                const card = document.createElement('div');
                card.className = 'product-card';
                card.innerHTML = `
                    <img src="${product.image || 'assets/vendor/demo/coffee-1.png'}" alt="${product.name}" loading="lazy" />
                    <div>
                        <h3>${product.name}</h3>
                        <p>${product.description || ''}</p>
                        <strong>${convertPrice(product.price)} ${state.currency}</strong>
                    </div>
                    <button type="button" data-product="${product.id}">
                        <span>+</span>
                        <span>${window.translationAddToCart || 'Sepete Ekle'}</span>
                    </button>
                `;
                card.querySelector('button').addEventListener('click', () => addToCart(product));
                elements.products.appendChild(card);
            });
    };

    const filterBySearch = (product) => {
        if (!elements.searchInput) return true;
        const query = elements.searchInput.value.trim().toLowerCase();
        if (!query) return true;
        return (
            product.name.toLowerCase().includes(query) ||
            (product.description || '').toLowerCase().includes(query)
        );
    };

    const addToCart = (product) => {
        const existing = state.cart.find((item) => item.id === product.id);
        if (existing) {
            existing.qty += 1;
        } else {
            state.cart.push({ ...product, qty: 1 });
        }
        updateCartSummary();
        toast.fire({ icon: 'success', title: `${product.name} sepete eklendi.` });
    };

    const updateCartSummary = () => {
        if (!elements.cartSummary) return;
        const totalQty = state.cart.reduce((total, item) => total + item.qty, 0);
        const totalAmount = state.cart.reduce((total, item) => total + Number(item.price) * item.qty, 0);
        elements.cartSummary.innerHTML = `
            <span>${totalQty} ürün</span>
            <strong>${convertPrice(totalAmount)} ${state.currency}</strong>
        `;
    };

    const convertPrice = (price) => (price * state.exchangeRate).toFixed(2);

    const updateExchangeRate = async () => {
        if (state.currency === state.baseCurrency) {
            state.exchangeRate = 1;
            return;
        }
        try {
            const response = await fetch(`api/currency.php?from=${state.baseCurrency}&to=${state.currency}&amount=1`);
            const data = await response.json();
            const rate = parseFloat(String(data.primary).replace(/,/g, ''));
            state.exchangeRate = Number.isNaN(rate) ? 1 : rate;
        } catch (error) {
            console.error('Kur çevrim hatası', error);
            state.exchangeRate = 1;
        }
    };

    const bindEvents = () => {
        elements.currencySelect?.addEventListener('change', async (event) => {
            state.currency = event.target.value;
            await updateExchangeRate();
            renderProducts();
            updateCartSummary();
            const params = new URLSearchParams(window.location.search);
            params.set('currency', state.currency);
            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
        });

        elements.languageSelect?.addEventListener('change', (event) => {
            const params = new URLSearchParams(window.location.search);
            params.set('lang', event.target.value);
            window.location.search = params.toString();
        });

        elements.waiterButton?.addEventListener('click', () => {
            socket.emit('waiter:call', { table: new URLSearchParams(window.location.search).get('table') || 'GENEL' });
            toast.fire({ icon: 'success', title: 'Garson çağrıldı.' });
        });

        socket.on('waiter:update', (payload) => {
            const audio = new Audio('assets/vendor/sounds/notification.mp3');
            audio.play();
            toast.fire({ icon: 'info', title: `Garson durumu: ${payload.status}` });
        });

        socket.on('order:update', (payload) => {
            const audio = new Audio('assets/vendor/sounds/order.mp3');
            audio.play();
            toast.fire({ icon: 'info', title: `Sipariş ${payload.status}` });
        });

        elements.searchInput?.addEventListener('input', () => renderProducts());
    };

    const init = async () => {
        window.translationAddToCart = window.MENU_STATE?.addToCartText || 'Sepete Ekle';
        await updateExchangeRate();
        await loadMenu();
        bindEvents();
    };

    return { init };
})();

document.addEventListener('DOMContentLoaded', () => MenuApp.init());
