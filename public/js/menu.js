const menuApp = document.getElementById('menuApp');
const slug = menuApp?.dataset.slug;
let cart = [];
let currentLang = 'tr';
if (menuApp?.dataset.apiKey) {
    localStorage.setItem('qrmenu_api_key', menuApp.dataset.apiKey);
}


const t = (key) => {
    const translations = window.translations || {};
    return translations[key] || key;
};

const loadTranslations = async (lang) => {
    const res = await fetch(`/public/lang/${lang}.json`);
    if (res.ok) {
        window.translations = await res.json();
        currentLang = lang;
    }
};

const renderCart = () => {
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    cartItems.innerHTML = cart.length ? cart.map(item => `
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <div>
                <strong>${item.name}</strong>
                <div class="small text-muted">${item.quantity} x ${item.price.toFixed(2)}</div>
            </div>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-secondary" data-action="decrease" data-id="${item.id}">-</button>
                <button class="btn btn-sm btn-outline-secondary" data-action="increase" data-id="${item.id}">+</button>
            </div>
        </div>
    `).join('') : '<p class="text-muted">Sepet boş</p>';
    const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    cartTotal.textContent = `${total.toFixed(2)} TRY`;
};

const fetchMenu = async () => {
    const res = await fetch(`/api/menu?slug=${slug}`, {
        headers: { 'X-API-KEY': menuApp?.dataset.apiKey || '' }
    });
    if (!res.ok) {
        Swal.fire('Hata', 'Menü yüklenemedi', 'error');
        return;
    }
    const data = await res.json();
    document.getElementById('restaurantName').textContent = data.restaurant.name;
    document.getElementById('restaurantDescription').textContent = data.restaurant.description || '';
    const menuCategories = document.getElementById('menuCategories');
    menuCategories.innerHTML = data.categories.map(category => `
        <div class="card">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">${category.name}</h2>
            </div>
            <div class="card-body">
                ${(category.products || []).map(product => `
                    <div class="product-item">
                        <div class="d-flex align-items-center">
                            ${product.image_url ? `<img src="${product.image_url}" alt="${product.name}">` : ''}
                            <div>
                                <div class="fw-semibold">${product.name}</div>
                                <div class="text-muted small">${product.description || ''}</div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold">${Number(product.price).toFixed(2)} TRY</div>
                            <button class="btn btn-sm btn-primary mt-2" data-product='${JSON.stringify(product)}'>Sepete Ekle</button>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `).join('');

    menuCategories.querySelectorAll('button[data-product]').forEach(btn => {
        btn.addEventListener('click', () => {
            const product = JSON.parse(btn.dataset.product);
            const existing = cart.find(item => item.id === product.id);
            if (existing) {
                existing.quantity += 1;
            } else {
                cart.push({ id: product.id, name: product.name, price: Number(product.price), quantity: 1 });
            }
            renderCart();
        });
    });

    document.getElementById('cartItems').addEventListener('click', (e) => {
        if (e.target.dataset.action) {
            const item = cart.find(i => i.id == e.target.dataset.id);
            if (!item) return;
            if (e.target.dataset.action === 'increase') item.quantity += 1;
            if (e.target.dataset.action === 'decrease') {
                item.quantity -= 1;
                if (item.quantity <= 0) cart = cart.filter(i => i.id != item.id);
            }
            renderCart();
        }
    });
};

const submitOrder = async () => {
    if (!cart.length) {
        Swal.fire('Uyarı', 'Sepetiniz boş', 'warning');
        return;
    }
    const tableNumber = document.getElementById('tableNumber').value;
    if (!tableNumber) {
        Swal.fire('Uyarı', 'Masa numarası girin', 'warning');
        return;
    }
    const payload = {
        slug,
        table_number: tableNumber,
        customer_note: document.getElementById('customerNote').value,
        items: cart,
        total_amount: cart.reduce((sum, item) => sum + item.price * item.quantity, 0),
        locale: currentLang
    };
    const res = await fetch('/api/menu/order', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-API-KEY': menuApp?.dataset.apiKey || ''
        },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) {
        Swal.fire('Hata', data.error || 'Sipariş gönderilemedi', 'error');
        return;
    }
    Swal.fire('Teşekkürler', data.message, 'success');
    cart = [];
    renderCart();
};

menuApp && loadTranslations('tr').then(() => fetchMenu());

document.getElementById('submitOrder')?.addEventListener('click', submitOrder);

document.querySelectorAll('#languageSwitcher button').forEach(btn => {
    btn.addEventListener('click', async () => {
        await loadTranslations(btn.dataset.lang);
        Swal.fire('Bilgi', `Dil ${btn.dataset.lang.toUpperCase()} olarak ayarlandı`, 'info');
    });
});
