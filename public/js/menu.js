const menuApp = document.getElementById('menuApp');
const slug = menuApp?.dataset.slug;
const apiKey = menuApp?.dataset.apiKey || '';
const tableToken = menuApp?.dataset.tableToken || '';
const tableName = menuApp?.dataset.tableName || '';

const state = {
    restaurant: {},
    categories: [],
    filteredCategories: [],
    cart: [],
    orderNumber: null,
    orderStatus: null,
    table: tableToken ? { token: tableToken, name: tableName } : null,
    lang: 'tr',
    translations: {},
};

const socket = tableToken ? io('https://qrmenu.noasoft.org:4000', { auth: { tableToken } }) : null;

const playSound = (frequency = 880, duration = 0.4) => {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioContext();
        const oscillator = ctx.createOscillator();
        const gain = ctx.createGain();
        oscillator.type = 'triangle';
        oscillator.frequency.value = frequency;
        oscillator.connect(gain);
        gain.connect(ctx.destination);
        oscillator.start();
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + duration);
        oscillator.stop(ctx.currentTime + duration);
    } catch (error) {
        console.debug('Ses yürütülemedi', error);
    }
};

const fetchMenu = async () => {
    const params = new URLSearchParams();
    params.set('slug', slug);
    if (tableToken) params.set('token', tableToken);
    const response = await fetch(`/api/menu?${params.toString()}`, {
        headers: { 'X-API-KEY': apiKey },
    });
    const text = await response.text();
    const data = text ? JSON.parse(text) : {};
    if (!response.ok) {
        throw data;
    }
    state.restaurant = data.restaurant;
    state.table = data.table;
    state.categories = data.categories || [];
    state.filteredCategories = state.categories;
    renderMenu();
};

const t = (key) => state.translations[key] || key;

const loadTranslations = async (lang) => {
    const response = await fetch(`/public/lang/${lang}.json`);
    if (response.ok) {
        state.translations = await response.json();
        state.lang = lang;
    }
};

const renderLanguages = () => {
    const container = document.getElementById('languageSwitcher');
    if (!container) return;
    const languages = state.restaurant.supported_languages || ['tr', 'en'];
    container.innerHTML = languages.map((lang) => `
        <button class="btn btn-sm ${state.lang === lang ? 'active' : ''}" data-lang="${lang}">${lang.toUpperCase()}</button>
    `).join('');
    container.querySelectorAll('button').forEach((btn) => btn.addEventListener('click', async () => {
        await loadTranslations(btn.dataset.lang);
        renderMenu();
        Swal.fire(t('language_changed') || 'Dil güncellendi', '', 'info');
    }));
};

const renderMenu = () => {
    document.getElementById('restaurantName').textContent = state.restaurant.name || 'Restoran';
    document.getElementById('restaurantDescription').textContent = state.restaurant.description || '';
    const badge = document.getElementById('tableBadge');
    if (badge && state.table) {
        badge.textContent = `${state.restaurant.qr_table_prefix || 'Masa'} ${state.table.name || ''}`;
    }
    renderLanguages();
    renderFeatured();
    renderCategories();
    renderCart();
};

const renderFeatured = () => {
    const container = document.getElementById('featuredProducts');
    const featured = (state.categories[0]?.products || []).slice(0, 3);
    container.innerHTML = featured.map((product) => `
        <div class="featured-card">
            ${product.image_url ? `<img src="${product.image_url}" alt="${product.name}">` : ''}
            <h3 class="h6">${product.name}</h3>
            <p class="text-muted small">${product.description || ''}</p>
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold">${Number(product.price || 0).toFixed(2)} ${product.currency || state.restaurant.currency}</span>
                <button class="btn btn-sm btn-success" data-add="${product.id}">${t('add_to_cart') || 'Sepete Ekle'}</button>
            </div>
        </div>
    `).join('');
    container.querySelectorAll('button[data-add]').forEach((btn) => btn.addEventListener('click', () => addToCart(btn.dataset.add)));
};

const renderCategories = () => {
    const container = document.getElementById('menuCategories');
    const categories = state.filteredCategories;
    container.innerHTML = categories.map((category) => `
        <div class="category-block">
            <div class="category-header">
                <div>
                    <h3 class="h5 mb-1">${category.name}</h3>
                    <p class="text-muted small mb-0">${category.description || ''}</p>
                </div>
                ${category.image_url ? `<img src="${category.image_url}" alt="${category.name}">` : ''}
            </div>
            <div>
                ${(category.products || []).map((product) => `
                    <div class="product-line">
                        ${product.image_url ? `<img src="${product.image_url}" alt="${product.name}">` : '<div></div>'}
                        <div>
                            <div class="fw-semibold">${product.name}</div>
                            <div class="text-muted small">${product.description || ''}</div>
                        </div>
                        <div class="product-actions d-flex flex-column align-items-end">
                            <span class="fw-semibold">${Number(product.price || 0).toFixed(2)} ${product.currency || state.restaurant.currency}</span>
                            <button class="btn btn-sm btn-success mt-2" data-add="${product.id}"><i class="bi bi-plus"></i></button>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `).join('');
    container.querySelectorAll('button[data-add]').forEach((btn) => btn.addEventListener('click', () => addToCart(btn.dataset.add)));
};

const addToCart = (productId) => {
    const product = state.categories.flatMap((category) => category.products || []).find((p) => String(p.id) === String(productId));
    if (!product) return;
    const existing = state.cart.find((item) => item.id === product.id);
    if (existing) {
        existing.quantity += 1;
    } else {
        state.cart.push({ id: product.id, name: product.name, price: Number(product.price || 0), quantity: 1 });
    }
    playSound(940, 0.2);
    renderCart();
};

const renderCart = () => {
    const container = document.getElementById('cartItems');
    if (!state.cart.length) {
        container.innerHTML = `<p class="text-muted">${t('cart_empty') || 'Sepetiniz boş'}</p>`;
    } else {
        container.innerHTML = state.cart.map((item) => `
            <div class="cart-item">
                <div>
                    <div class="fw-semibold">${item.name}</div>
                    <small class="text-muted">${item.quantity} x ${item.price.toFixed(2)}</small>
                </div>
                <div class="quantity" data-id="${item.id}">
                    <button type="button" data-action="decrease">-</button>
                    <span class="px-2">${item.quantity}</span>
                    <button type="button" data-action="increase">+</button>
                </div>
            </div>
        `).join('');
    }
    const total = state.cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    document.getElementById('cartTotal').textContent = `${total.toFixed(2)} ${state.restaurant.currency || 'TRY'}`;
    const cartPanel = document.getElementById('cartPanel');
    cartPanel.querySelectorAll('.quantity button').forEach((btn) => btn.addEventListener('click', () => updateQuantity(btn.closest('.quantity').dataset.id, btn.dataset.action)));
    updateOrderStatusText();
};

const updateQuantity = (id, action) => {
    const item = state.cart.find((product) => String(product.id) === String(id));
    if (!item) return;
    if (action === 'increase') item.quantity += 1;
    if (action === 'decrease') {
        item.quantity -= 1;
        if (item.quantity <= 0) {
            state.cart = state.cart.filter((p) => p.id !== item.id);
        }
    }
    renderCart();
};

const submitOrder = async () => {
    if (!state.cart.length) {
        Swal.fire('Uyarı', 'Sepetiniz boş', 'warning');
        return;
    }
    if (!tableToken) {
        Swal.fire('Uyarı', 'Masa doğrulanamadı', 'warning');
        return;
    }
    const payload = {
        slug,
        table_token: tableToken,
        items: state.cart,
        total_amount: state.cart.reduce((sum, item) => sum + item.price * item.quantity, 0),
        customer_note: document.getElementById('customerNote').value,
        locale: state.lang,
    };
    try {
        const response = await fetch('/api/menu/order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-KEY': apiKey,
            },
            body: JSON.stringify(payload),
        });
        const text = await response.text();
        const data = text ? JSON.parse(text) : {};
        if (!response.ok) {
            throw data;
        }
        state.orderNumber = data.order_number;
        state.orderStatus = 'pending';
        state.cart = [];
        document.getElementById('customerNote').value = '';
        renderCart();
        playSound(1100, 0.3);
        Swal.fire('Teşekkürler', 'Siparişiniz alındı', 'success');
        document.getElementById('downloadReceipt').classList.add('d-none');
    } catch (error) {
        Swal.fire('Hata', error.error || 'Sipariş gönderilemedi', 'error');
    }
};

const waitForOrderUpdates = () => {
    if (!socket) return;
    socket.on('connect', () => {
        socket.emit('registerTable', { tableToken });
    });
    socket.on('order:status', (payload) => {
        if (payload.table_token !== tableToken) return;
        state.orderStatus = payload.status;
        playSound(payload.status === 'ready' ? 1250 : 780, 0.4);
        updateOrderStatusText();
        if (['ready', 'completed', 'cancelled'].includes(payload.status)) {
            document.getElementById('downloadReceipt').classList.toggle('d-none', !['ready', 'completed'].includes(payload.status));
        }
        toastStatus(payload.status);
    });
};

const toastStatus = (status) => {
    const messages = {
        preparing: 'Siparişiniz hazırlanıyor',
        ready: 'Siparişiniz hazırlandı',
        completed: 'Afiyet olsun! Sipariş tamamlandı',
        cancelled: 'Siparişiniz iptal edildi',
    };
    Swal.fire({
        toast: true,
        position: 'top-end',
        timer: 2600,
        showConfirmButton: false,
        icon: status === 'cancelled' ? 'error' : 'info',
        title: messages[status] || status,
    });
};

const updateOrderStatusText = () => {
    const el = document.getElementById('orderStatusText');
    if (!el) return;
    if (!state.orderNumber) {
        el.textContent = '';
        return;
    }
    const label = {
        pending: 'Siparişiniz alınmıştır',
        preparing: 'Siparişiniz hazırlanıyor',
        ready: 'Siparişiniz servis için hazır',
        completed: 'Sipariş tamamlandı',
        cancelled: 'Sipariş iptal edildi',
    }[state.orderStatus || 'pending'];
    el.textContent = `${label} (#${state.orderNumber})`;
};

const filterMenu = (query) => {
    const term = query.toLowerCase();
    if (!term) {
        state.filteredCategories = state.categories;
        renderCategories();
        return;
    }
    state.filteredCategories = state.categories.map((category) => ({
        ...category,
        products: (category.products || []).filter((product) => product.name.toLowerCase().includes(term) || (product.description || '').toLowerCase().includes(term)),
    })).filter((category) => category.products.length);
    renderCategories();
};

const callWaiter = async () => {
    if (!tableToken) {
        Swal.fire('Uyarı', 'Masa doğrulanamadı', 'warning');
        return;
    }
    try {
        const response = await fetch('/api/menu/waiter-call', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-KEY': apiKey,
            },
            body: JSON.stringify({ slug, table_token: tableToken }),
        });
        const text = await response.text();
        const data = text ? JSON.parse(text) : {};
        if (!response.ok) {
            throw data;
        }
        playSound(660, 0.4);
        Swal.fire('Garson çağrıldı', 'En kısa sürede masanızda olacaktır.', 'success');
    } catch (error) {
        Swal.fire('Hata', error.error || 'Garson çağrılamadı', 'error');
    }
};

const downloadReceipt = async () => {
    if (!state.orderNumber) return;
    try {
        const params = new URLSearchParams();
        params.set('slug', slug);
        params.set('order_number', state.orderNumber);
        const response = await fetch(`/api/menu/order-status?${params.toString()}`, {
            headers: { 'X-API-KEY': apiKey },
        });
        const text = await response.text();
        const data = text ? JSON.parse(text) : {};
        if (!response.ok) {
            throw data;
        }
        generateCustomerReceipt(data.order);
    } catch (error) {
        Swal.fire('Hata', error.error || 'Adisyon hazırlanamadı', 'error');
    }
};

const generateCustomerReceipt = (order) => {
    const doc = new jspdf.jsPDF();
    doc.setFontSize(14);
    doc.text(state.restaurant.name || 'Restoran', 105, 20, { align: 'center' });
    doc.setFontSize(10);
    doc.text(`Masa: ${order.table_number}`, 20, 32);
    doc.text(`Sipariş #: ${order.order_number}`, 20, 40);
    doc.text(`Durum: ${order.status}`, 20, 48);
    let y = 60;
    (order.items || []).forEach((item) => {
        doc.text(`${item.quantity || 1} x ${item.name}`, 20, y);
        doc.text(`${Number(item.price || 0).toFixed(2)}`, 190, y, { align: 'right' });
        y += 8;
    });
    y += 4;
    doc.text(`Toplam: ${Number(order.total_amount).toFixed(2)} ${order.currency || state.restaurant.currency}`, 20, y);
    y += 12;
    doc.text('Afiyet olsun! NoaSoft QR Menü sistemi.', 105, y, { align: 'center' });
    doc.save(`siparis-${order.order_number}.pdf`);
};

const initEvents = () => {
    document.getElementById('submitOrder').addEventListener('click', submitOrder);
    document.getElementById('waiterCall').addEventListener('click', callWaiter);
    document.getElementById('menuSearch').addEventListener('input', (e) => filterMenu(e.target.value));
    document.getElementById('downloadReceipt').addEventListener('click', downloadReceipt);
};

const bootstrapMenu = async () => {
    try {
        await loadTranslations('tr');
        await fetchMenu();
        initEvents();
        waitForOrderUpdates();
    } catch (error) {
        Swal.fire('Hata', error.error || 'Menü yüklenemedi', 'error');
    }
};

bootstrapMenu();
