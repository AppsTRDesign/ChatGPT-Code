const MenuApp = (() => {
    const state = {
        categories: [],
        products: [],
        cart: [],
        currency: document.body.dataset.baseCurrency || 'TRY',
        baseCurrency: document.body.dataset.baseCurrency || 'TRY',
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
        orderModal: document.querySelector('#orderModal'),
        searchInput: document.querySelector('#searchMenu'),
    };

    const socket = io('https://qrmenu.noasoft.org:4000');

    const swalToast = Swal.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 3500,
    });

    const loadMenu = async () => {
        state.currency = elements.currencySelect?.value || state.currency;
        const response = await fetch('api/menu.php');
        const data = await response.json();
        state.categories = data.categories;
        state.products = data.products;
        renderCategories();
        renderProducts();
        updateCartSummary();
    };

    const renderCategories = () => {
        elements.categories.innerHTML = '';
        state.categories.forEach((category) => {
            const card = document.createElement('button');
            card.className = 'category-card';
            card.innerHTML = `
                <span class="badge">${category.icon || 'icon'}</span>
                <strong>${category.name}</strong>
            `;
            card.addEventListener('click', () => filterProducts(category.id));
            elements.categories.appendChild(card);
        });
    };

    const renderProducts = (categoryId = null) => {
        elements.products.innerHTML = '';
        const products = categoryId
            ? state.products.filter((product) => product.category_id === categoryId)
            : state.products;

        products
            .filter((product) => filterBySearch(product))
            .forEach((product) => {
                const card = document.createElement('div');
                card.className = 'product-card';
                card.innerHTML = `
                    <img src="${product.image}" alt="${product.name}" loading="lazy" />
                    <div>
                        <h3>${product.name}</h3>
                        <p>${product.description}</p>
                        <strong>${convertPrice(product.price)} ${state.currency}</strong>
                    </div>
                    <button type="button" data-product="${product.id}">
                        <span>+</span>
                        <span>Sepete Ekle</span>
                    </button>
                `;
                card.querySelector('button').addEventListener('click', () => addToCart(product));
                elements.products.appendChild(card);
            });
    };

    const addToCart = (product) => {
        const existing = state.cart.find((item) => item.id === product.id);
        if (existing) {
            existing.qty += 1;
        } else {
            state.cart.push({ ...product, qty: 1 });
        }
        updateCartSummary();
        swalToast.fire({ icon: 'success', title: `${product.name} sepete eklendi` });
    };

    const updateCartSummary = () => {
        const totalQty = state.cart.reduce((total, item) => total + item.qty, 0);
        const totalAmount = state.cart.reduce((total, item) => total + item.price * item.qty, 0);
        if (elements.cartSummary) {
            elements.cartSummary.innerHTML = `
                <span>${totalQty} ürün</span>
                <strong>${convertPrice(totalAmount)} ${state.currency}</strong>
            `;
        }
    };

    const filterProducts = (categoryId) => {
        renderProducts(categoryId);
    };

    const convertPrice = (price) => {
        const converted = price * state.exchangeRate;
        return converted.toFixed(2);
    };

    const filterBySearch = (product) => {
        if (!elements.searchInput) {
            return true;
        }
        const query = elements.searchInput.value.trim().toLowerCase();
        if (!query) {
            return true;
        }
        return (
            product.name.toLowerCase().includes(query) ||
            product.description.toLowerCase().includes(query)
        );
    };

    const updateExchangeRate = async () => {
        if (state.currency === state.baseCurrency) {
            state.exchangeRate = 1;
            return;
        }
        try {
            const response = await fetch(`api/currency.php?from=${state.baseCurrency}&to=${state.currency}&amount=1`);
            const data = await response.json();
            const primary = parseFloat(String(data.primary).replace(/,/g, ''));
            if (!Number.isNaN(primary)) {
                state.exchangeRate = primary;
            }
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
        });

        elements.languageSelect?.addEventListener('change', (event) => {
            const lang = event.target.value;
            const searchParams = new URLSearchParams(window.location.search);
            searchParams.set('lang', lang);
            window.location.search = searchParams.toString();
        });

        elements.waiterButton?.addEventListener('click', () => {
            socket.emit('waiter:call', { table: new URLSearchParams(window.location.search).get('table') || 'GENEL' });
            swalToast.fire({ icon: 'success', title: 'Garson çağrıldı' });
        });

        socket.on('waiter:update', (payload) => {
            const audio = new Audio('assets/vendor/sounds/notification.mp3');
            audio.play();
            swalToast.fire({ icon: 'info', title: `Garson durumu: ${payload.status}` });
        });

        socket.on('order:update', (payload) => {
            const audio = new Audio('assets/vendor/sounds/order.mp3');
            audio.play();
            swalToast.fire({ icon: 'info', title: `Sipariş ${payload.status}` });
        });

        elements.searchInput?.addEventListener('input', () => {
            renderProducts();
        });
    };

    const init = async () => {
        await updateExchangeRate();
        await loadMenu();
        bindEvents();
    };

    return { init };
})();

document.addEventListener('DOMContentLoaded', () => MenuApp.init());
