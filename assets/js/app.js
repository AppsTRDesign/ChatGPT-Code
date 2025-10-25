if (window.Dropzone) {
    Dropzone.autoDiscover = false;
}

const escapeHtml = (value) => {
    if (value === null || value === undefined) {
        return '';
    }
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

const registerTurkishFont = (doc) => {
    if (!doc || !window.APP_FONTS || !window.APP_FONTS.DejaVuSans) {
        return;
    }
    const available = typeof doc.getFontList === 'function' ? doc.getFontList() : {};
    if (available.DejaVuSans) {
        return;
    }
    const fontFile = 'DejaVuSans.ttf';
    doc.addFileToVFS(fontFile, window.APP_FONTS.DejaVuSans);
    doc.addFont(fontFile, 'DejaVuSans', 'normal');
    doc.addFont(fontFile, 'DejaVuSans', 'bold');
};

const formatDateTime = (value) => {
    if (!value) {
        return '-';
    }
    const date = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
        return escapeHtml(value);
    }
    return date.toLocaleString('tr-TR');
};

const formatDateOnly = (value) => {
    if (!value) {
        return '-';
    }
    const normalised = value.length === 10 ? `${value}T00:00:00` : value.replace(' ', 'T');
    const date = new Date(normalised);
    if (Number.isNaN(date.getTime())) {
        return escapeHtml(value);
    }
    return date.toLocaleDateString('tr-TR');
};

const appConfig = window.APP_CONFIG || {};

const loadScript = (src) => new Promise((resolve, reject) => {
    const existing = Array.from(document.getElementsByTagName('script')).find((script) => script.src === src);
    if (existing) {
        if (existing.dataset.loaded === '1') {
            resolve();
            return;
        }
        existing.addEventListener('load', () => {
            existing.dataset.loaded = '1';
            resolve();
        }, { once: true });
        existing.addEventListener('error', () => reject(new Error(`Script yüklenemedi: ${src}`)), { once: true });
        return;
    }

    const script = document.createElement('script');
    script.src = src;
    script.async = true;
    script.onload = () => {
        script.dataset.loaded = '1';
        resolve();
    };
    script.onerror = () => reject(new Error(`Script yüklenemedi: ${src}`));
    document.head.appendChild(script);
});

const buildAbsoluteUrl = (path) => {
    if (!path) {
        return '';
    }
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }
    const base = appConfig.baseUrl || window.location.origin;
    return `${base.replace(/\/$/, '')}/${path.replace(/^\//, '')}`;
};

let firebaseAppPromise = null;

const firebaseProviderFactories = {
    google: () => new window.firebase.auth.GoogleAuthProvider(),
    facebook: () => new window.firebase.auth.FacebookAuthProvider(),
    twitter: () => new window.firebase.auth.TwitterAuthProvider(),
    github: () => new window.firebase.auth.GithubAuthProvider(),
    microsoft: () => new window.firebase.auth.OAuthProvider('microsoft.com'),
    apple: () => new window.firebase.auth.OAuthProvider('apple.com'),
    yahoo: () => new window.firebase.auth.OAuthProvider('yahoo.com'),
};

const ensureFirebaseAuth = async () => {
    if (!appConfig.firebase || !appConfig.firebase.enabled || !appConfig.firebase.config) {
        throw new Error('Firebase yapılandırması eksik.');
    }

    if (!firebaseAppPromise) {
        firebaseAppPromise = (async () => {
            await loadScript('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
            await loadScript('https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js');
            if (!window.firebase.apps.length) {
                window.firebase.initializeApp(appConfig.firebase.config);
            }
            return window.firebase;
        })();
    }

    return firebaseAppPromise;
};

const initFirebaseButtons = () => {
    if (!appConfig.firebase || !appConfig.firebase.enabled) {
        return;
    }

    document.querySelectorAll('[data-firebase-provider]').forEach((button) => {
        if (button.dataset.firebaseBound) {
            return;
        }
        button.dataset.firebaseBound = '1';
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            const providerKey = button.dataset.firebaseProvider;
            try {
                const firebase = await ensureFirebaseAuth();
                const providerFactory = firebaseProviderFactories[providerKey];
                if (!providerFactory) {
                    throw new Error('Desteklenmeyen sağlayıcı.');
                }
                button.disabled = true;
                const auth = firebase.auth();
                const provider = providerFactory();
                const result = await auth.signInWithPopup(provider);
                const idToken = await result.user.getIdToken();
                const response = await fetch(appConfig.firebase.endpoint || '/firebase-auth', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idToken, provider: providerKey }),
                });
                const data = await response.json();
                if (!response.ok || data.status !== 'ok') {
                    throw new Error(data.message || 'Giriş başarısız.');
                }
                window.location.href = data.redirect || '/client/dashboard';
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Giriş başarısız',
                    text: error.message || 'Sosyal giriş tamamlanamadı.',
                    confirmButtonColor: '#0d6efd',
                });
            } finally {
                button.disabled = false;
            }
        });
    });
};

const refreshTable = (tableId) => {
    if (!tableId || !window.jQuery) {
        return;
    }
    try {
        window.jQuery(`#${tableId}`).bootstrapTable('refresh');
    } catch (error) {
        console.warn('Tablo yenilenemedi', error);
    }
};

const subscriptionStatusMap = {
    active: { label: 'Aktif', class: 'bg-success' },
    pending: { label: 'İşleniyor', class: 'bg-secondary' },
    awaiting_payment: { label: 'Ödeme Bekliyor', class: 'bg-warning text-dark' },
    payment_missing: { label: 'Eksik Ödeme', class: 'bg-danger' },
    failed: { label: 'Başarısız', class: 'bg-danger' },
    rejected: { label: 'Reddedildi', class: 'bg-danger' },
    cancelled: { label: 'İptal Edildi', class: 'bg-secondary' },
};

const paymentStatusMap = {
    pending: { label: 'Bekliyor', class: 'bg-warning text-dark' },
    approved: { label: 'Onaylandı', class: 'bg-success' },
    rejected: { label: 'Reddedildi', class: 'bg-danger' },
};

window.appHandlers = {
    dateTimeFormatter: (value) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        return `<span class="small">${formatDateTime(value)}</span>`;
    },
    packageResponseHandler: (response) => response,
    packagePriceFormatter: (value) => {
        const price = Number(value || 0);
        return `${price.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₺`;
    },
    packageStatusFormatter: (value) => {
        const active = value === true || value === 1 || value === '1';
        const label = active ? 'Aktif' : 'Pasif';
        const badge = active ? 'bg-success' : 'bg-secondary';
        return `<span class="badge rounded-pill ${badge}">${label}</span>`;
    },
    packageActionsFormatter: (value, row) => {
        const table = document.getElementById('packagesTable');
        const csrf = table ? table.dataset.csrf || '' : '';
        const toggleLabel = row.is_active ? 'Pasif Yap' : 'Aktif Yap';
        const id = encodeURIComponent(row.id);
        const buttons = [
            `<button type="button" class="btn btn-sm btn-outline-light" data-ajax-action data-url="/admin/package-toggle" data-id="${id}" data-table="packagesTable" data-csrf="${csrf}">${toggleLabel}</button>`,
            `<a href="/admin/package-edit?id=${id}" class="btn btn-sm btn-outline-primary">Düzenle</a>`,
            `<button type="button" class="btn btn-sm btn-outline-danger" data-ajax-action data-action-value="delete" data-url="/admin/package-delete" data-id="${id}" data-table="packagesTable" data-csrf="${csrf}" data-confirm="Paket silinecek. Onaylıyor musunuz?">Sil</button>`,
        ];
        return `<div class="d-flex flex-wrap gap-2 justify-content-end">${buttons.join('')}</div>`;
    },
    userResponseHandler: (response) => response,
    roleFormatter: (value, row) => {
        const role = value === 'admin' ? 'Admin' : 'Müşteri';
        const badgeClass = value === 'admin' ? 'bg-danger' : 'bg-info';
        return `<span class="badge rounded-pill ${badgeClass}">${escapeHtml(role)}</span>`;
    },
    userStatusFormatter: (value, row) => {
        const badges = [];
        if (row.login_blocked) {
            badges.push('<span class="badge rounded-pill bg-danger">Giriş Yasaklı</span>');
        } else if (!row.is_approved) {
            badges.push('<span class="badge rounded-pill bg-warning text-dark">Onay Bekliyor</span>');
        } else {
            badges.push('<span class="badge rounded-pill bg-success">Onaylı</span>');
        }

        if (row.email_verified) {
            badges.push('<span class="badge rounded-pill bg-info text-dark">E-posta Onaylı</span>');
        } else {
            badges.push('<span class="badge rounded-pill bg-secondary">E-posta Bekliyor</span>');
        }

        return `<div class="d-flex flex-wrap gap-1">${badges.join('')}</div>`;
    },
    userActionsFormatter: (value, row) => {
        const table = document.getElementById('usersTable');
        const csrf = table ? table.dataset.csrf || '' : '';
        const buttons = [];
        buttons.push(`<a href="/admin/user-edit?id=${encodeURIComponent(row.id)}" class="btn btn-sm btn-outline-primary">Düzenle</a>`);

        if (!row.self) {
            buttons.push(`<button type="button" class="btn btn-sm btn-outline-danger" data-ajax-action data-action-value="delete" data-url="/admin/user-delete" data-id="${row.id}" data-table="usersTable" data-csrf="${csrf}" data-confirm="Bu üyeyi silmek istediğinizden emin misiniz?">Sil</button>`);
        }

        return `<div class="d-flex flex-wrap gap-2 justify-content-end">${buttons.join('')}</div>`;
    },
    purchaseResponseHandler: (response) => response,
    paymentFormatter: (value) => (value === 'iyzico' ? 'Kredi Kartı (İyzico)' : 'Banka Havalesi'),
    purchaseStatusFormatter: (value) => {
        const status = subscriptionStatusMap[value] || { label: value, class: 'bg-secondary' };
        return `<span class="badge rounded-pill ${status.class}">${escapeHtml(status.label)}</span>`;
    },
    noteFormatter: (value) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        return `<span class="text-break">${escapeHtml(value).replace(/\n/g, '<br>')}</span>`;
    },
    purchaseErrorFormatter: (value, row) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        const note = escapeHtml(value).replace(/\n/g, '<br>');
        const time = row.last_error_at ? `<div class="small text-white-50 mt-1">${escapeHtml(formatDateTime(row.last_error_at))}</div>` : '';
        return `<span class="text-danger">${note}</span>${time}`;
    },
    purchaseDateFormatter: (value, row) => {
        const created = formatDateTime(row.created_at);
        const activated = row.activated_at ? formatDateTime(row.activated_at) : null;
        const expires = row.expires_at ? formatDateTime(row.expires_at) : null;
        let html = `<div class="small text-white-50">Talep: ${created}</div>`;
        if (activated) {
            html += `<div class="small text-white-50">Aktif: ${activated}</div>`;
        }
        if (expires) {
            html += `<div class="small text-white-50">Bitiş: ${expires}</div>`;
        }
        return html;
    },
    purchaseActionsFormatter: (value, row) => {
        const table = document.getElementById('purchasesTable');
        const csrf = table ? table.dataset.csrf || '' : '';
        const buttons = [];
        if (row.status !== 'active') {
            buttons.push(`<button type="button" class="btn btn-sm btn-primary" data-ajax-action data-action-value="activate" data-url="/admin/purchases" data-id="${row.id}" data-table="purchasesTable" data-csrf="${csrf}" data-confirm="Bu paketi onaylamak istediğinizden emin misiniz?">Onayla</button>`);
        }
        if (row.payment_method === 'bank' && row.status !== 'active') {
            buttons.push(`<button type="button" class="btn btn-sm btn-outline-light" data-ajax-action data-action-value="awaiting" data-url="/admin/purchases" data-id="${row.id}" data-table="purchasesTable" data-csrf="${csrf}">Ödeme Bekleniyor</button>`);
            buttons.push(`<button type="button" class="btn btn-sm btn-outline-warning" data-ajax-action data-action-value="missing" data-url="/admin/purchases" data-id="${row.id}" data-table="purchasesTable" data-csrf="${csrf}">Eksik Ödeme</button>`);
        }
        if (row.status !== 'rejected' && row.status !== 'cancelled' && row.status !== 'active') {
            buttons.push(`<button type="button" class="btn btn-sm btn-outline-danger" data-ajax-action data-action-value="reject" data-url="/admin/purchases" data-id="${row.id}" data-table="purchasesTable" data-csrf="${csrf}" data-confirm="Bu satın alımı reddetmek istediğinizden emin misiniz?">Reddet</button>`);
        }
        if (row.status === 'active') {
            buttons.push(`<button type="button" class="btn btn-sm btn-outline-light" data-ajax-action data-action-value="cancel" data-url="/admin/purchases" data-id="${row.id}" data-table="purchasesTable" data-csrf="${csrf}" data-confirm="Aktif paketi iptal etmek istediğinizden emin misiniz?">İptal Et</button>`);
        }
        if (!buttons.length) {
            return '<span class="text-white-50">-</span>';
        }
        return `<div class="d-flex flex-wrap gap-2 justify-content-end">${buttons.join('')}</div>`;
    },
    paymentsResponseHandler: (response) => response,
    paymentStatusFormatter: (value) => {
        const status = paymentStatusMap[value] || { label: value, class: 'bg-secondary' };
        return `<span class="badge rounded-pill ${status.class}">${escapeHtml(status.label)}</span>`;
    },
    paymentActionsFormatter: (value, row) => {
        if (row.status !== 'pending') {
            return '<span class="text-white-50">-</span>';
        }
        const table = document.getElementById('paymentsTable');
        const csrf = table ? table.dataset.csrf || '' : '';
        return `<div class="d-flex flex-wrap gap-2 justify-content-end">
            <button type="button" class="btn btn-sm btn-success" data-ajax-action data-action-value="approve" data-url="/admin/payments" data-id="${row.id}" data-table="paymentsTable" data-csrf="${csrf}">Onayla</button>
            <button type="button" class="btn btn-sm btn-outline-danger" data-ajax-action data-action-value="reject" data-url="/admin/payments" data-id="${row.id}" data-table="paymentsTable" data-csrf="${csrf}" data-confirm="Bu bildirimi reddetmek istediğinizden emin misiniz?">Reddet</button>
        </div>`;
    },
    usageResponseHandler: (response) => response,
    usageStatusFormatter: (value) => {
        const status = value === 'success'
            ? { label: 'Başarılı', class: 'bg-success' }
            : value === 'error'
                ? { label: 'Hata', class: 'bg-danger' }
                : { label: escapeHtml(value), class: 'bg-secondary' };
        return `<span class="badge rounded-pill ${status.class}">${escapeHtml(status.label)}</span>`;
    },
    clientUsageResponseHandler: (response) => response,
    clientUsageDateFormatter: (value) => formatDateOnly(value),
    clientTokenResponseHandler: (response) => response,
    clientTokenStatusFormatter: (value) => {
        const status = value === 'revoked'
            ? { label: 'Pasif', class: 'bg-danger' }
            : { label: 'Aktif', class: 'bg-success' };
        return `<span class="badge rounded-pill ${status.class}">${status.label}</span>`;
    },
    clientTokenValueFormatter: (value) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        return `<span class="d-inline-block w-100 text-break small font-monospace">${escapeHtml(value)}</span>`;
    },
    clientTokenDateFormatter: (value) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        return `<span class="small">${formatDateTime(value)}</span>`;
    },
    clientTokenActionsFormatter: (value, row) => {
        const table = document.getElementById('tokensTable');
        if (!table) {
            return '';
        }
        const csrf = table.dataset.csrf || '';
        const forms = [];
        if (row.status === 'revoked') {
            forms.push(`
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="${escapeHtml(csrf)}">
                    <input type="hidden" name="token_id" value="${escapeHtml(row.id)}">
                    <input type="hidden" name="action" value="restore">
                    <button type="submit" class="btn btn-sm btn-outline-success">Aktif Et</button>
                </form>
            `);
        } else {
            forms.push(`
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="${escapeHtml(csrf)}">
                    <input type="hidden" name="token_id" value="${escapeHtml(row.id)}">
                    <input type="hidden" name="action" value="revoke">
                    <button type="submit" class="btn btn-sm btn-outline-light" data-confirm="Token pasif edilsin mi?">Pasif Et</button>
                </form>
            `);
        }
        forms.push(`
            <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token" value="${escapeHtml(csrf)}">
                <input type="hidden" name="token_id" value="${escapeHtml(row.id)}">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Token tamamen silinecek. Onaylıyor musunuz?">Sil</button>
            </form>
        `);
        return `<div class="d-flex flex-wrap gap-2 justify-content-end">${forms.join('')}</div>`;
    },
    clientPurchaseHistoryResponse: (response) => response,
    clientPurchaseStatusFormatter: (value, row) => {
        const fallbackLabel = row && row.status_label ? row.status_label : value;
        const map = subscriptionStatusMap[value] || { label: fallbackLabel, class: 'bg-secondary' };
        const label = row && row.status_label ? row.status_label : map.label;
        return `<span class="badge rounded-pill ${map.class}">${escapeHtml(label)}</span>`;
    },
    clientPurchasePaymentFormatter: (value) => {
        if (value === 'iyzico') {
            return '<span class="badge bg-info text-dark">Kredi Kartı (İyzico)</span>';
        }
        if (value === 'bank') {
            return '<span class="badge bg-primary">Banka Havalesi</span>';
        }
        return `<span class="badge bg-secondary">${escapeHtml(value || '-')}</span>`;
    },
    clientPurchaseDateFormatter: (value) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        return `<span class="small">${formatDateTime(value)}</span>`;
    },
    clientQrHistoryResponseHandler: (response) => {
        if (window.clientQrHistoryConfig && window.clientQrHistoryConfig.totalElementId) {
            const el = document.getElementById(window.clientQrHistoryConfig.totalElementId);
            if (el) {
                el.textContent = typeof response.total === 'number' ? response.total : '-';
            }
        }
        return response;
    },
    clientQrPreviewFormatter: (value) => {
        if (!value) {
            return '<span class="text-white-50">Önizleme yok</span>';
        }
        return `<img src="${value}" alt="QR" class="img-thumbnail bg-dark border-0" style="max-width:72px;max-height:72px;">`;
    },
    clientQrActionsFormatter: (value, row) => {
        const table = document.getElementById('clientQrHistoryTable');
        const csrf = table ? table.dataset.csrf || '' : '';
        const downloadButtons = (row.downloads || []).map((file) => {
            const label = escapeHtml(file.label || file.format || 'İndir');
            const url = escapeHtml(file.url || '#');
            return `<a href="${url}" class="btn btn-sm btn-outline-primary" download>${label}</a>`;
        }).join('');
        const deleteButton = `<button type="button" class="btn btn-sm btn-outline-danger" data-ajax-action data-url="/client/qr-delete" data-id="${row.id}" data-table="clientQrHistoryTable" data-csrf="${csrf}" data-confirm="Bu kaydı silmek istiyor musunuz?">Sil</button>`;
        return `<div class="d-flex flex-wrap gap-2 justify-content-end">${downloadButtons}${deleteButton}</div>`;
    },
    adminQrHistoryResponseHandler: (response) => response,
    adminQrActionsFormatter: (value, row) => {
        const table = document.getElementById('adminQrHistoryTable');
        const csrf = table ? table.dataset.csrf || '' : '';
        const downloadButtons = (row.downloads || []).map((file) => {
            const label = escapeHtml(file.label || file.format || 'İndir');
            const url = escapeHtml(file.url || '#');
            return `<a href="${url}" class="btn btn-sm btn-outline-primary" download>${label}</a>`;
        }).join('');
        const deleteButton = `<button type="button" class="btn btn-sm btn-outline-danger" data-ajax-action data-url="/admin/qr-delete" data-id="${row.id}" data-table="adminQrHistoryTable" data-csrf="${csrf}" data-confirm="QR kaydını silmek istediğinizden emin misiniz?">Sil</button>`;
        return `<div class="d-flex flex-wrap gap-2 justify-content-end">${downloadButtons}${deleteButton}</div>`;
    },
    refererFormatter: (value) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        const url = escapeHtml(value);
        return `<a href="${url}" target="_blank" rel="noopener" class="link-light text-decoration-underline">${url}</a>`;
    },
    searchFormatter: (value, row) => {
        const engine = row.search_engine || (value && value.engine);
        const term = row.search_term || (value && value.term);
        if (!engine && !term) {
            return '<span class="text-white-50">-</span>';
        }
        if (engine && term) {
            return `<span class="small">${escapeHtml(engine)}: ${escapeHtml(term)}</span>`;
        }
        if (engine) {
            return `<span class="small">${escapeHtml(engine)}</span>`;
        }
        return `<span class="small">${escapeHtml(term)}</span>`;
    },
    urlFormatter: (value) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        const url = escapeHtml(value);
        return `<a href="${url}" target="_blank" rel="noopener" class="link-light text-decoration-underline">${url}</a>`;
    },
    webNotificationResponse: (response) => {
        const select = document.getElementById('notificationFilter');
        if (select && response && Array.isArray(response.filters)) {
            const current = select.value;
            const options = ['<option value="">Tüm Bildirimler</option>'];
            response.filters.forEach((item) => {
                const value = String(item.id);
                const selected = current === value ? ' selected' : '';
                options.push(`<option value="${value}"${selected}>${escapeHtml(item.title)}</option>`);
            });
            select.innerHTML = options.join('');
        }

        const rows = (response.rows || []).map((row) => ({
            ...row,
            targets: {
                languages: row.languages,
                platforms: row.platforms,
            },
        }));
        return { ...response, rows };
    },
    webNotificationTarget: (value, row) => {
        const parse = (raw) => {
            if (!raw) {
                return [];
            }
            try {
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [];
            }
        };

        const languages = parse(row.languages);
        const platforms = parse(row.platforms);
        const languageLabel = languages.length ? languages.map((code) => code.toUpperCase()).join(', ') : 'Tüm Diller';
        const platformLabel = platforms.length
            ? platforms.map((platform) => (platform === 'mobile' ? 'Mobil' : 'Masaüstü')).join(', ')
            : 'Tüm Platformlar';

        return `<div class="d-flex flex-column"><span>${escapeHtml(languageLabel)}</span><span class="text-white-50 small">${escapeHtml(platformLabel)}</span></div>`;
    },
    numberFormatter: (value) => {
        const numeric = Number(value || 0);
        return numeric ? numeric.toLocaleString('tr-TR') : '0';
    },
    notificationBreakdownParams: (params) => {
        const rangeSelect = document.getElementById('notificationBreakdownRange');
        const notificationFilter = document.getElementById('notificationFilter');
        const range = rangeSelect ? rangeSelect.value : 'weekly';
        const notificationId = notificationFilter && notificationFilter.value ? notificationFilter.value : '';
        return {
            ...params,
            range,
            notification_id: notificationId,
            mode: 'table',
        };
    },
    notificationBreakdownResponse: (response) => {
        if (response && response.summary) {
            updateNotificationBreakdownSummary(response.summary);
        }
        return response;
    },
    onlineHandler: (response) => {
        const rows = (response.rows || []).map((row) => ({
            ...row,
            search: { engine: row.search_engine, term: row.search_term },
        }));
        return { ...response, rows };
    },
    onlineQuery: (params) => {
        const windowSelect = document.getElementById('onlineWindow');
        const windowValue = windowSelect ? windowSelect.value : '5';
        return { ...params, window: windowValue };
    },
};

let usageChart;
let usageMetrics = [];
let dashboardTrafficChart;
let dashboardRevenueChart;
const dashboardState = {
    trafficPeriod: 'daily',
    revenuePeriod: 'daily',
};
let clientUsageChart;
let clientUsageMetrics = [];

let onlineChart;
let onlineMetrics = [];

let notificationChart;
let notificationMetrics = [];
const notificationQueue = [];
const notificationSeen = new Set();
const notificationEventCache = new Set();
const MAX_ACTIVE_NOTIFICATIONS = 3;
let notificationActiveCount = 0;
let notificationHost;


const updateUsageChart = (labels, data) => {
    const canvas = document.getElementById('usageChart');
    if (!canvas || !window.Chart) {
        return;
    }

    const chartData = {
        labels,
        datasets: [
            {
                label: 'Toplam İstek',
                data,
                backgroundColor: 'rgba(56, 189, 248, 0.65)',
                borderColor: '#38bdf8',
                borderWidth: 1.5,
                borderRadius: 8,
                maxBarThickness: 36,
            },
        ],
    };

    const maxValue = data.length ? Math.max(...data) : 0;
    const stepSize = maxValue <= 6 ? 1 : Math.ceil(maxValue / 5);

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: 8 },
        scales: {
            x: {
                ticks: { color: '#cbd5f5', maxRotation: 0, minRotation: 0, autoSkip: true },
                grid: { color: 'rgba(148, 163, 184, 0.08)', drawBorder: false },
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#cbd5f5',
                    callback: (value) => (Number.isInteger(value) ? value : ''),
                    stepSize,
                },
                grid: { color: 'rgba(148, 163, 184, 0.12)', drawBorder: false },
            },
        },
        plugins: {
            legend: {
                labels: { color: '#e2e8f0' },
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.88)',
                borderColor: 'rgba(56, 189, 248, 0.4)',
                borderWidth: 1,
                titleColor: '#f8fafc',
                bodyColor: '#f8fafc',
            },
        },
    };

    if (!usageChart) {
        usageChart = new Chart(canvas, {
            type: 'bar',
            data: chartData,
            options: chartOptions,
        });
    } else {
        usageChart.data = chartData;
        usageChart.options = chartOptions;
        usageChart.update();
    }
};

const updateOnlineChart = (labels, totals) => {
    const canvas = document.getElementById('onlineChart');
    if (!canvas || !window.Chart) {
        return;
    }

    const data = {
        labels,
        datasets: [
            {
                label: 'Aktif Oturum',
                data: totals,
                backgroundColor: 'rgba(168, 85, 247, 0.65)',
                borderColor: '#a855f7',
                borderWidth: 1.5,
                borderRadius: 8,
                maxBarThickness: 36,
            },
        ],
    };

    const maxValue = totals.length ? Math.max(...totals) : 0;
    const stepSize = maxValue <= 6 ? 1 : Math.ceil(maxValue / 5);

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: 8 },
        scales: {
            x: {
                ticks: { color: '#cbd5f5', maxRotation: 0, minRotation: 0, autoSkip: true },
                grid: { color: 'rgba(148, 163, 184, 0.08)', drawBorder: false },
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#cbd5f5',
                    callback: (value) => (Number.isInteger(value) ? value : ''),
                    stepSize,
                },
                grid: { color: 'rgba(148, 163, 184, 0.12)', drawBorder: false },
            },
        },
        plugins: {
            legend: { labels: { color: '#e2e8f0' } },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.88)',
                borderColor: 'rgba(168, 85, 247, 0.4)',
                borderWidth: 1,
                titleColor: '#f8fafc',
                bodyColor: '#f8fafc',
            },
        },
    };

    if (!onlineChart) {
        onlineChart = new Chart(canvas, { type: 'bar', data, options });
    } else {
        onlineChart.data = data;
        onlineChart.options = options;
        onlineChart.update();
    }
};

const updateOnlineSummary = (rows) => {
    const container = document.getElementById('onlineSummary');
    if (!container) {
        return;
    }
    if (!rows.length) {
        container.innerHTML = '<li class="text-white-50">Veri bulunamadı.</li>';
        return;
    }
    const totals = rows.map((row) => Number(row.total || 0));
    const current = rows.length ? Number(rows[0].total || 0) : 0;
    const peak = totals.length ? Math.max(...totals) : 0;
    const sum = totals.reduce((acc, value) => acc + value, 0);
    const average = totals.length ? Math.round(sum / totals.length) : 0;
    container.innerHTML = `
        <li class="mb-2"><strong>Güncel Aktif:</strong> ${current}</li>
        <li class="mb-2"><strong>Ortalama:</strong> ${average}</li>
        <li class="mb-0"><strong>Zirve:</strong> ${peak}</li>
    `;
};

const loadOnlineMetrics = async (range) => {
    try {
        const response = await fetch(`/admin/data/online-metrics?range=${encodeURIComponent(range)}`, { headers: { Accept: 'application/json' } });
        const json = await response.json();
        onlineMetrics = json.rows || [];
        const labels = onlineMetrics.map((row) => row.label).reverse();
        const totals = onlineMetrics.map((row) => Number(row.total || 0)).reverse();
        updateOnlineChart(labels, totals);
        updateOnlineSummary(onlineMetrics);
    } catch (error) {
        console.error('Online metrikler yüklenemedi', error);
    }
};

const exportOnline = (format) => {
    if (!onlineMetrics.length) {
        Swal.fire({ icon: 'warning', title: 'İndirilecek veri bulunamadı.', confirmButtonColor: '#0d6efd' });
        return;
    }

    const rows = onlineMetrics.map((row) => [row.label, Number(row.total || 0)]);

    if (format === 'pdf' && window.jspdf && window.jspdf.jsPDF) {
        const doc = new window.jspdf.jsPDF({ orientation: 'landscape' });
        registerTurkishFont(doc);
        doc.setFont('DejaVuSans', 'bold');
        doc.setFontSize(16);
        doc.text('Canlı Ziyaretçi Raporu', 14, 18);
        doc.setFont('DejaVuSans', 'normal');
        doc.autoTable({
            head: [['Dönem', 'Aktif Oturum']],
            body: rows,
            startY: 26,
            styles: {
                font: 'DejaVuSans',
                fontStyle: 'normal',
                fillColor: [13, 17, 35],
                textColor: [241, 246, 249],
            },
            headStyles: {
                font: 'DejaVuSans',
                fontStyle: 'bold',
                fillColor: [168, 85, 247],
                textColor: 255,
            },
            alternateRowStyles: { fillColor: [24, 33, 58] },
        });
        doc.save('canli-ziyaretci-raporu.pdf');
        return;
    }

    if (format === 'excel' && window.XLSX) {
        const worksheet = window.XLSX.utils.aoa_to_sheet([
            ['Dönem', 'Aktif Oturum'],
            ...rows,
        ]);
        const workbook = window.XLSX.utils.book_new();
        window.XLSX.utils.book_append_sheet(workbook, worksheet, 'Rapor');
        window.XLSX.writeFile(workbook, 'canli-ziyaretci-raporu.xlsx');
    }
};

const updateNotificationChart = (rows) => {
    const canvas = document.getElementById('notificationChart');
    if (!canvas || !window.Chart) {
        return;
    }

    const labels = rows.map((row) => row.label).reverse();
    const delivered = rows.map((row) => Number(row.delivered || 0)).reverse();
    const clicked = rows.map((row) => Number(row.clicked || 0)).reverse();
    const dismissed = rows.map((row) => Number(row.dismissed || 0)).reverse();

    const datasets = [
        {
            label: 'Gösterildi',
            data: delivered,
            backgroundColor: 'rgba(56, 189, 248, 0.6)',
            borderColor: '#38bdf8',
            borderWidth: 1.5,
            borderRadius: 10,
            maxBarThickness: 32,
        },
        {
            label: 'Tıklandı',
            data: clicked,
            backgroundColor: 'rgba(16, 185, 129, 0.6)',
            borderColor: '#10b981',
            borderWidth: 1.5,
            borderRadius: 10,
            maxBarThickness: 32,
        },
        {
            label: 'Kapatıldı',
            data: dismissed,
            backgroundColor: 'rgba(250, 204, 21, 0.6)',
            borderColor: '#facc15',
            borderWidth: 1.5,
            borderRadius: 10,
            maxBarThickness: 32,
        },
    ];

    const maxValue = Math.max(
        ...(delivered.length ? delivered : [0]),
        ...(clicked.length ? clicked : [0]),
        ...(dismissed.length ? dismissed : [0]),
    );
    const stepSize = maxValue <= 6 ? 1 : Math.ceil(maxValue / 5);

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: 8 },
        scales: {
            x: {
                ticks: { color: '#cbd5f5', maxRotation: 0, minRotation: 0, autoSkip: true },
                grid: { color: 'rgba(148, 163, 184, 0.08)', drawBorder: false },
                stacked: true,
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#cbd5f5',
                    callback: (value) => (Number.isInteger(value) ? value : ''),
                    stepSize,
                },
                grid: { color: 'rgba(148, 163, 184, 0.12)', drawBorder: false },
                stacked: true,
            },
        },
        plugins: {
            legend: { labels: { color: '#e2e8f0' } },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.88)',
                borderColor: 'rgba(56, 189, 248, 0.4)',
                borderWidth: 1,
                titleColor: '#f8fafc',
                bodyColor: '#f8fafc',
            },
        },
    };

    const data = { labels, datasets };

    if (!notificationChart) {
        notificationChart = new Chart(canvas, { type: 'bar', data, options });
    } else {
        notificationChart.data = data;
        notificationChart.options = options;
        notificationChart.update();
    }
};

const updateNotificationSummary = (rows) => {
    const container = document.getElementById('notificationSummary');
    if (!container) {
        return;
    }

    if (!rows.length) {
        container.innerHTML = '<li class="text-white-50">Veri bulunamadı.</li>';
        return;
    }

    const totals = rows.reduce((acc, row) => ({
        delivered: acc.delivered + Number(row.delivered || 0),
        clicked: acc.clicked + Number(row.clicked || 0),
        dismissed: acc.dismissed + Number(row.dismissed || 0),
    }), { delivered: 0, clicked: 0, dismissed: 0 });

    const ctr = totals.delivered ? ((totals.clicked / totals.delivered) * 100).toFixed(1) : '0.0';
    const dismissRate = totals.delivered ? ((totals.dismissed / totals.delivered) * 100).toFixed(1) : '0.0';

    container.innerHTML = `
        <li class="mb-2"><strong>Toplam Gösterim:</strong> ${totals.delivered}</li>
        <li class="mb-2"><strong>Toplam Tıklama:</strong> ${totals.clicked}</li>
        <li class="mb-2"><strong>Kapatılma:</strong> ${totals.dismissed}</li>
        <li class="mb-0"><strong>Tıklanma Oranı:</strong> %${ctr} / <strong>Kapatma:</strong> %${dismissRate}</li>
    `;
};

function updateNotificationBreakdownSummary(summary) {
    const container = document.getElementById('notificationBreakdownSummary');
    if (!container) {
        return;
    }

    if (!summary) {
        container.innerHTML = '<li class="text-white-50">Veri bulunamadı.</li>';
        return;
    }

    const delivered = Number(summary.delivered || 0);
    const clicked = Number(summary.clicked || 0);
    const dismissed = Number(summary.dismissed || 0);
    const total = delivered + clicked + dismissed;
    const ctr = delivered ? ((clicked / delivered) * 100).toFixed(1) : '0.0';
    const dismissRate = delivered ? ((dismissed / delivered) * 100).toFixed(1) : '0.0';

    container.innerHTML = `
        <li class="mb-1"><strong>Gösterim:</strong> ${delivered.toLocaleString('tr-TR')}</li>
        <li class="mb-1"><strong>Tıklama:</strong> ${clicked.toLocaleString('tr-TR')} <span class="text-white-50">(%${ctr})</span></li>
        <li class="mb-1"><strong>Kapatma:</strong> ${dismissed.toLocaleString('tr-TR')} <span class="text-white-50">(%${dismissRate})</span></li>
        <li class="mb-0"><strong>Toplam Etkileşim:</strong> ${total.toLocaleString('tr-TR')}</li>
    `;
}

const refreshNotificationBreakdownTable = () => {
    if (!window.jQuery) {
        return;
    }
    try {
        window.jQuery('#notificationBreakdownTable').bootstrapTable('refresh', { silent: true });
    } catch (error) {
        console.warn('Bildirim dağılımı yenilenemedi', error);
    }
};

const exportNotificationBreakdown = async (format) => {
    const rangeSelect = document.getElementById('notificationBreakdownRange');
    const notificationFilter = document.getElementById('notificationFilter');
    const params = new URLSearchParams({
        mode: 'export',
        range: rangeSelect ? rangeSelect.value : 'weekly',
        sort: 'clicked',
        order: 'DESC',
    });

    if (notificationFilter && notificationFilter.value) {
        params.set('notification_id', notificationFilter.value);
    }

    try {
        const response = await fetch(`/admin/data/web-notification-breakdown.php?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        const rows = Array.isArray(data.rows) ? data.rows : [];

        if (!rows.length) {
            Swal.fire({ icon: 'warning', title: 'İndirilecek veri bulunamadı.', confirmButtonColor: '#0d6efd' });
            return;
        }

        const normalized = rows.map((row) => {
            const delivered = Number(row.delivered || 0);
            const clicked = Number(row.clicked || 0);
            const dismissed = Number(row.dismissed || 0);
            const total = Number(row.total || delivered + clicked + dismissed);
            return {
                country: row.country || 'Bilinmiyor',
                city: row.city || '-',
                language: row.language ? String(row.language).toLocaleUpperCase('tr-TR') : '-',
                platform: row.platform || 'Genel',
                delivered,
                clicked,
                dismissed,
                total,
            };
        });

        if (format === 'pdf' && window.jspdf && window.jspdf.jsPDF) {
            const doc = new window.jspdf.jsPDF({ orientation: 'landscape' });
            registerTurkishFont(doc);
            doc.setFont('DejaVuSans', 'bold');
            doc.setFontSize(16);
            doc.text('Web Bildirim Dağılımı', 14, 18);
            doc.setFont('DejaVuSans', 'normal');
            doc.autoTable({
                head: [['Ülke', 'Şehir', 'Dil', 'Platform', 'Gösterim', 'Tıklama', 'Kapatma', 'Toplam']],
                body: normalized.map((row) => [
                    row.country,
                    row.city,
                    row.language,
                    row.platform,
                    row.delivered.toLocaleString('tr-TR'),
                    row.clicked.toLocaleString('tr-TR'),
                    row.dismissed.toLocaleString('tr-TR'),
                    row.total.toLocaleString('tr-TR'),
                ]),
                startY: 26,
                styles: {
                    font: 'DejaVuSans',
                    fontStyle: 'normal',
                    fillColor: [13, 17, 35],
                    textColor: [241, 246, 249],
                },
                headStyles: {
                    font: 'DejaVuSans',
                    fontStyle: 'bold',
                    fillColor: [56, 189, 248],
                    textColor: 20,
                },
                alternateRowStyles: { fillColor: [24, 33, 58] },
            });
            doc.save('web-bildirim-dagilim.pdf');
            return;
        }

        if (format === 'excel' && window.XLSX) {
            const rowsForExcel = normalized.map((row) => [
                row.country,
                row.city,
                row.language,
                row.platform,
                row.delivered,
                row.clicked,
                row.dismissed,
                row.total,
            ]);
            const worksheet = window.XLSX.utils.aoa_to_sheet([
                ['Ülke', 'Şehir', 'Dil', 'Platform', 'Gösterim', 'Tıklama', 'Kapatma', 'Toplam'],
                ...rowsForExcel,
            ]);
            const workbook = window.XLSX.utils.book_new();
            window.XLSX.utils.book_append_sheet(workbook, worksheet, 'Dağılım');
            window.XLSX.writeFile(workbook, 'web-bildirim-dagilim.xlsx');
            return;
        }

        Swal.fire({ icon: 'warning', title: 'Dışa aktarma desteklenmiyor.', confirmButtonColor: '#0d6efd' });
    } catch (error) {
        console.error('Bildirim dağılımı dışa aktarılamadı', error);
        Swal.fire({ icon: 'error', title: 'Dışa aktarma başarısız.', confirmButtonColor: '#0d6efd' });
    }
};

const localizeTableRefreshButtons = () => {
    document.querySelectorAll('.bootstrap-table button[name="refresh"]').forEach((button) => {
        if (!button) {
            return;
        }
        if (!button.dataset.localized) {
            button.dataset.localized = '1';
            button.innerHTML = '<span class="refresh-icon" aria-hidden="true">⟳</span><span>Yenile</span>';
        }
        button.classList.remove('btn-secondary');
        button.classList.add('btn-outline-light', 'd-inline-flex', 'align-items-center', 'gap-2');
    });
};

if (window.jQuery) {
    window.jQuery(document).on('post-body.bs.table post-header.bs.table load-success.bs.table', localizeTableRefreshButtons);
}

const loadNotificationMetrics = async (range, notificationId) => {
    const params = new URLSearchParams({ range });
    if (notificationId) {
        params.set('notification_id', notificationId);
    }

    try {
        const response = await fetch(`/admin/data/web-notification-metrics?${params.toString()}`, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        notificationMetrics = data.rows || [];
        updateNotificationChart(notificationMetrics);
        updateNotificationSummary(notificationMetrics);
    } catch (error) {
        console.error('Bildirim metrikleri yüklenemedi', error);
    }
};

const exportNotificationMetrics = (format) => {
    if (!notificationMetrics.length) {
        Swal.fire({ icon: 'warning', title: 'İndirilecek veri bulunamadı.', confirmButtonColor: '#0d6efd' });
        return;
    }

    const rows = notificationMetrics.map((row) => [
        row.label,
        Number(row.delivered || 0),
        Number(row.clicked || 0),
        Number(row.dismissed || 0),
    ]);

    if (format === 'pdf' && window.jspdf && window.jspdf.jsPDF) {
        const doc = new window.jspdf.jsPDF({ orientation: 'landscape' });
        registerTurkishFont(doc);
        doc.setFont('DejaVuSans', 'bold');
        doc.setFontSize(16);
        doc.text('Web Bildirim Raporu', 14, 18);
        doc.setFont('DejaVuSans', 'normal');
        doc.autoTable({
            head: [['Dönem', 'Gösterildi', 'Tıklandı', 'Kapatıldı']],
            body: rows,
            startY: 26,
            styles: {
                font: 'DejaVuSans',
                fontStyle: 'normal',
                fillColor: [13, 17, 35],
                textColor: [241, 246, 249],
            },
            headStyles: {
                font: 'DejaVuSans',
                fontStyle: 'bold',
                fillColor: [56, 189, 248],
                textColor: 20,
            },
            alternateRowStyles: { fillColor: [24, 33, 58] },
        });
        doc.save('web-bildirim-raporu.pdf');
        return;
    }

    if (format === 'excel' && window.XLSX) {
        const worksheet = window.XLSX.utils.aoa_to_sheet([
            ['Dönem', 'Gösterildi', 'Tıklandı', 'Kapatıldı'],
            ...rows,
        ]);
        const workbook = window.XLSX.utils.book_new();
        window.XLSX.utils.book_append_sheet(workbook, worksheet, 'Rapor');
        window.XLSX.writeFile(workbook, 'web-bildirim-raporu.xlsx');
    }
};

const detectClientLanguage = () => {
    const language = navigator.language || navigator.userLanguage || '';
    return language ? language.toLowerCase() : '';
};

const detectClientPlatform = () => {
    const ua = (navigator.userAgent || '').toLowerCase();
    return /android|iphone|ipad|ipod|mobile/.test(ua) ? 'mobile' : 'desktop';
};

const getNotificationHost = () => {
    if (notificationHost && document.body.contains(notificationHost)) {
        return notificationHost;
    }
    notificationHost = document.createElement('div');
    notificationHost.className = 'inline-notification-host';
    document.body.appendChild(notificationHost);
    return notificationHost;
};

const removeNotificationElement = (element) => {
    const finalize = () => {
        if (notificationActiveCount > 0) {
            notificationActiveCount -= 1;
        }
        if (notificationHost && !notificationHost.childElementCount) {
            notificationHost.remove();
            notificationHost = null;
        }
        if (notificationQueue.length) {
            renderNotificationQueue();
        }
    };

    if (!element) {
        finalize();
        return;
    }

    element.classList.add('closing');
    setTimeout(() => {
        if (element.parentElement) {
            element.parentElement.removeChild(element);
        }
        finalize();
    }, 220);
};

const recordNotificationEvent = async (notification, action) => {
    const key = `${notification.id}:${action}`;
    if (notificationEventCache.has(key)) {
        return;
    }
    notificationEventCache.add(key);

    try {
        await fetch('/client/data/notification-event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                id: notification.id,
                action,
                language: detectClientLanguage(),
                platform: detectClientPlatform(),
            }),
        });
    } catch (error) {
        console.warn('Bildirim olayı kaydedilemedi', error);
    }
};

const buildNotificationElement = (notification) => {
    const container = document.createElement('div');
    container.className = 'inline-notification shadow-lg';
    container.setAttribute('role', 'status');
    container.dataset.notificationId = notification.id;

    const header = document.createElement('div');
    header.className = 'inline-notification-header';

    if (notification.logo) {
        const logo = document.createElement('img');
        logo.src = notification.logo;
        logo.alt = 'Logo';
        logo.className = 'inline-notification-logo';
        header.appendChild(logo);
    }

    const titleWrap = document.createElement('div');
    titleWrap.className = 'inline-notification-title';
    titleWrap.textContent = notification.title;
    header.appendChild(titleWrap);

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'btn-close btn-close-white';
    closeButton.setAttribute('aria-label', 'Kapat');
    header.appendChild(closeButton);

    container.appendChild(header);

    if (notification.image) {
        const imageWrap = document.createElement('div');
        imageWrap.className = 'inline-notification-image';
        const img = document.createElement('img');
        img.src = notification.image;
        img.alt = notification.title;
        img.loading = 'lazy';
        img.decoding = 'async';
        img.addEventListener('load', () => {
            const width = img.naturalWidth || 0;
            const height = img.naturalHeight || 0;
            imageWrap.classList.remove('portrait', 'landscape', 'square');
            if (width && height) {
                const ratio = width / height;
                if (ratio > 1.2) {
                    imageWrap.classList.add('landscape');
                } else if (ratio < 0.8) {
                    imageWrap.classList.add('portrait');
                } else {
                    imageWrap.classList.add('square');
                }
            }
        });
        imageWrap.appendChild(img);
        container.appendChild(imageWrap);
    }

    const body = document.createElement('div');
    body.className = 'inline-notification-body';
    body.innerText = notification.message;
    container.appendChild(body);

    if (notification.url) {
        const actions = document.createElement('div');
        actions.className = 'inline-notification-actions';
        const link = document.createElement('a');
        link.href = notification.url;
        link.target = '_blank';
        link.rel = 'noopener';
        link.className = 'btn btn-sm btn-primary';
        link.textContent = 'Detayları Gör';
        link.addEventListener('click', () => {
            recordNotificationEvent(notification, 'clicked');
            removeNotificationElement(container);
        });
        actions.appendChild(link);
        container.appendChild(actions);
    }

    closeButton.addEventListener('click', () => {
        recordNotificationEvent(notification, 'dismissed');
        removeNotificationElement(container);
    });

    container.addEventListener('mouseenter', () => {
        container.classList.add('hover');
    });
    container.addEventListener('mouseleave', () => {
        container.classList.remove('hover');
    });

    return container;
};

function renderNotificationQueue() {
    if (!notificationQueue.length) {
        return;
    }

    const host = getNotificationHost();
    while (notificationQueue.length && notificationActiveCount < MAX_ACTIVE_NOTIFICATIONS) {
        const notification = notificationQueue.shift();
        const element = buildNotificationElement(notification);
        notificationActiveCount += 1;
        host.appendChild(element);
        requestAnimationFrame(() => {
            element.classList.add('visible');
        });
    }
}

const enqueueNotifications = (items) => {
    items.forEach((item) => {
        if (!item || typeof item.id === 'undefined') {
            return;
        }
        if (notificationSeen.has(item.id)) {
            return;
        }
        notificationSeen.add(item.id);
        notificationQueue.push(item);
    });
    renderNotificationQueue();
};

const pollNotifications = async () => {
    try {
        const language = detectClientLanguage();
        const platform = detectClientPlatform();
        const params = new URLSearchParams();
        if (language) {
            params.set('lang', language);
        }
        if (platform) {
            params.set('platform', platform);
        }
        const response = await fetch(`/client/data/notifications${params.toString() ? `?${params.toString()}` : ''}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        const data = await response.json();
        if (data && data.status === 'ok' && Array.isArray(data.notifications)) {
            enqueueNotifications(data.notifications);
        }
    } catch (error) {
        console.warn('Bildirimler alınamadı', error);
    }
};

const startNotificationPolling = () => {
    if (!window.fetch) {
        return;
    }

    const loop = async () => {
        await pollNotifications();
        setTimeout(loop, 45000);
    };

    setTimeout(loop, 3000);
};

const startHeartbeat = () => {
    if (!window.fetch || !window.APP_CONFIG || !window.APP_CONFIG.csrf) {
        return;
    }
    const csrf = window.APP_CONFIG.csrf;
    const path = window.location.pathname || '';
    const endpoint = path.startsWith('/admin') ? '/admin/ping.php' : '/client/ping.php';

    const ping = async () => {
        try {
            await fetch(endpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
            });
        } catch (error) {
            console.warn('Heartbeat başarısız', error);
        }
    };

    ping();
    setInterval(ping, 60000);
};

const updateUsageSummary = (rows) => {
    const container = document.getElementById('usageSummary');
    if (!container) {
        return;
    }
    if (!rows.length) {
        container.innerHTML = '<li class="text-white-50">Veri bulunamadı.</li>';
        return;
    }

    const totals = rows.map((row) => Number(row.total || 0));
    const total = totals.reduce((sum, value) => sum + value, 0);
    const peak = Math.max(...totals);
    const average = Math.round(total / totals.length);
    const last = totals[0];

    container.innerHTML = `
        <li class="mb-2"><strong>Toplam İstek:</strong> ${total}</li>
        <li class="mb-2"><strong>Güncel Dönem:</strong> ${last}</li>
        <li class="mb-2"><strong>Ortalama:</strong> ${average}</li>
        <li class="mb-0"><strong>Zirve:</strong> ${peak}</li>
    `;
};

const loadUsageMetrics = async (range) => {
    try {
        const response = await fetch(`/admin/data/usage-metrics?range=${encodeURIComponent(range)}`, {
            headers: { Accept: 'application/json' },
        });
        const json = await response.json();
        usageMetrics = json.rows || [];
        const labels = usageMetrics.map((row) => row.label).reverse();
        const values = usageMetrics.map((row) => Number(row.total || 0)).reverse();
        updateUsageChart(labels, values);
        updateUsageSummary(usageMetrics);
    } catch (error) {
        console.error('Kullanım metrikleri yüklenemedi', error);
    }
};

const exportUsage = (format) => {
    if (!usageMetrics.length) {
        Swal.fire({ icon: 'warning', title: 'İndirilecek veri bulunamadı.', confirmButtonColor: '#0d6efd' });
        return;
    }

    const rows = usageMetrics.map((row) => [row.label, Number(row.total || 0)]);

    if (format === 'pdf' && window.jspdf && window.jspdf.jsPDF) {
        const doc = new window.jspdf.jsPDF({ orientation: 'landscape' });
        registerTurkishFont(doc);
        doc.setFont('DejaVuSans', 'bold');
        doc.setFontSize(16);
        doc.text('API Kullanım Raporu', 14, 18);
        doc.setFont('DejaVuSans', 'normal');
        doc.autoTable({
            head: [['Dönem', 'Toplam İstek']],
            body: rows,
            startY: 26,
            styles: {
                font: 'DejaVuSans',
                fontStyle: 'normal',
                fillColor: [13, 17, 35],
                textColor: [241, 246, 249],
            },
            headStyles: {
                font: 'DejaVuSans',
                fontStyle: 'bold',
                fillColor: [13, 110, 253],
                textColor: 255,
            },
            alternateRowStyles: {
                fillColor: [24, 33, 58],
            },
        });
        doc.save('api-raporu.pdf');
        return;
    }

    if (format === 'excel' && window.XLSX) {
        const worksheet = window.XLSX.utils.aoa_to_sheet([
            ['Dönem', 'Toplam İstek'],
            ...rows,
        ]);
        const workbook = window.XLSX.utils.book_new();
        window.XLSX.utils.book_append_sheet(workbook, worksheet, 'Rapor');
        window.XLSX.writeFile(workbook, 'api-raporu.xlsx');
    }
};

const renderClientUsageChart = (rows) => {
    const canvas = document.getElementById('clientUsageChart');
    if (!canvas || !window.Chart) {
        return;
    }

    const labels = rows.map((row) => row.label).reverse();
    const totals = rows.map((row) => Number(row.total || 0)).reverse();

    const data = {
        labels,
        datasets: [
            {
                label: 'Toplam İstek',
                data: totals,
                borderColor: '#60a5fa',
                backgroundColor: 'rgba(96, 165, 250, 0.18)',
                fill: true,
                tension: 0.35,
            },
        ],
    };

    const maxValue = totals.length ? Math.max(...totals) : 0;
    const stepSize = maxValue <= 6 ? 1 : Math.ceil(maxValue / 5);

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        layout: { padding: 6 },
        elements: {
            point: { radius: 4, hoverRadius: 6 },
            line: { borderWidth: 3, borderCapStyle: 'round' },
        },
        plugins: {
            legend: { labels: { color: '#e2e8f0', font: { family: 'Inter, "Segoe UI", sans-serif', size: 13 } } },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.88)',
                borderColor: 'rgba(96, 165, 250, 0.4)',
                borderWidth: 1,
                titleColor: '#f8fafc',
                bodyColor: '#f8fafc',
            },
        },
        scales: {
            x: { ticks: { color: '#cbd5f5', maxRotation: 0, minRotation: 0, autoSkip: true }, grid: { color: 'rgba(148, 163, 184, 0.16)', drawBorder: false } },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#cbd5f5',
                    callback: (value) => (Number.isInteger(value) ? value : ''),
                    stepSize: stepSize,
                },
                grid: { color: 'rgba(148, 163, 184, 0.12)', drawBorder: false },
            },
        },
    };

    if (!clientUsageChart) {
        clientUsageChart = new Chart(canvas, {
            type: 'line',
            data,
            options,
        });
    } else {
        clientUsageChart.data = data;
        clientUsageChart.options = options;
        clientUsageChart.update();
    }
};

const updateClientUsageSummary = (summary) => {
    const container = document.getElementById('clientUsageSummary');
    if (!container) {
        return;
    }

    if (!summary || (Number(summary.total) === 0 && Number(summary.peak) === 0 && Number(summary.latest) === 0)) {
        container.innerHTML = '<li class="text-white-50">Henüz kullanım verisi yok.</li>';
        return;
    }

    const rows = [];
    rows.push(`<li class="mb-2"><strong>Toplam İstek:</strong> ${Number(summary.total || 0)}</li>`);
    rows.push(`<li class="mb-2"><strong>Son Dönem:</strong> ${Number(summary.latest || 0)}</li>`);
    rows.push(`<li class="mb-2"><strong>Günlük Ortalama:</strong> ${Number(summary.average || 0)}</li>`);
    rows.push(`<li class="mb-0"><strong>Zirve:</strong> ${Number(summary.peak || 0)}</li>`);
    container.innerHTML = rows.join('');
};

const loadClientUsageMetrics = async () => {
    if (!window.clientUsageConfig || !window.clientUsageConfig.endpoint) {
        return;
    }

    try {
        const response = await fetch(window.clientUsageConfig.endpoint, { headers: { Accept: 'application/json' } });
        const json = await response.json();
        clientUsageMetrics = json.rows || [];
        renderClientUsageChart(clientUsageMetrics);
        updateClientUsageSummary(json.summary || null);
    } catch (error) {
        console.error('Kullanıcı kullanım verileri yüklenemedi', error);
        updateClientUsageSummary(null);
    }
};

const renderDashboardTraffic = (series) => {
    const canvas = document.getElementById('dashboardTrafficChart');
    if (!canvas || !window.Chart) {
        return;
    }

    const labels = Array.isArray(series?.labels) ? series.labels : [];
    const usage = Array.isArray(series?.usage) ? series.usage : [];
    const registrations = Array.isArray(series?.registrations) ? series.registrations : [];

    const data = {
        labels,
        datasets: [
            {
                label: 'API İstekleri',
                data: usage,
                borderColor: '#38bdf8',
                backgroundColor: 'rgba(56, 189, 248, 0.2)',
                fill: true,
                tension: 0.35,
            },
            {
                label: 'Yeni Üyeler',
                data: registrations,
                borderColor: '#a855f7',
                backgroundColor: 'rgba(168, 85, 247, 0.18)',
                fill: true,
                tension: 0.35,
            },
        ],
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#e2e8f0',
                },
            },
        },
        scales: {
            x: {
                ticks: { color: '#cbd5f5' },
                grid: { color: 'rgba(148, 163, 184, 0.12)' },
            },
            y: {
                ticks: { color: '#cbd5f5' },
                grid: { color: 'rgba(148, 163, 184, 0.12)' },
                beginAtZero: true,
                precision: 0,
            },
        },
    };

    if (!dashboardTrafficChart) {
        dashboardTrafficChart = new Chart(canvas, {
            type: 'line',
            data,
            options,
        });
    } else {
        dashboardTrafficChart.data = data;
        dashboardTrafficChart.options = options;
        dashboardTrafficChart.update();
    }
};

const renderDashboardRevenue = (series) => {
    const canvas = document.getElementById('dashboardRevenueChart');
    if (!canvas || !window.Chart) {
        return;
    }

    const labels = Array.isArray(series?.labels) ? series.labels : [];
    const totals = Array.isArray(series?.totals) ? series.totals : [];

    const data = {
        labels,
        datasets: [
            {
                label: 'Onaylı Gelir (₺)',
                data: totals,
                backgroundColor: 'rgba(34, 197, 94, 0.6)',
                borderColor: '#22c55e',
                borderWidth: 1.5,
            },
        ],
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                ticks: { color: '#cbd5f5' },
                grid: { color: 'rgba(148, 163, 184, 0.08)' },
            },
            y: {
                ticks: { color: '#cbd5f5' },
                grid: { color: 'rgba(148, 163, 184, 0.12)' },
                beginAtZero: true,
            },
        },
        plugins: {
            legend: {
                labels: { color: '#e2e8f0' },
            },
        },
    };

    if (!dashboardRevenueChart) {
        dashboardRevenueChart = new Chart(canvas, {
            type: 'bar',
            data,
            options,
        });
    } else {
        dashboardRevenueChart.data = data;
        dashboardRevenueChart.options = options;
        dashboardRevenueChart.update();
    }
};

const updateDashboardSummary = (summary) => {
    const container = document.getElementById('dashboardSummary');
    if (!container) {
        return;
    }

    if (!summary) {
        container.innerHTML = '<li class="text-white-50">Özet verisi bulunamadı.</li>';
        return;
    }

    const rows = [];
    if (typeof summary.totalRevenue !== 'undefined') {
        rows.push(`<li class="mb-2"><strong>Toplam Onaylı Gelir:</strong> ${Number(summary.totalRevenue).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₺</li>`);
    }
    if (typeof summary.pendingPurchases !== 'undefined') {
        rows.push(`<li class="mb-2"><strong>Bekleyen Satın Alım:</strong> ${Number(summary.pendingPurchases)}</li>`);
    }
    if (typeof summary.failedPurchases !== 'undefined') {
        rows.push(`<li class="mb-2"><strong>Başarısız Ödeme:</strong> ${Number(summary.failedPurchases)}</li>`);
    }
    if (typeof summary.newClients7 !== 'undefined') {
        rows.push(`<li class="mb-0"><strong>Son 7 Gün Yeni Üye:</strong> ${Number(summary.newClients7)}</li>`);
    }

    container.innerHTML = rows.join('') || '<li class="text-white-50">Özet verisi bulunamadı.</li>';
};

const getDashboardEndpoint = () => {
    if (window.dashboardConfig && window.dashboardConfig.endpoint) {
        return window.dashboardConfig.endpoint;
    }
    return '/admin/data/dashboard-metrics';
};

const setDashboardRangeLabel = (chart, label) => {
    const elementId = chart === 'traffic' ? 'trafficRangeLabel' : 'revenueRangeLabel';
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = label || '';
    }
};

const fetchDashboardData = async (chart, period, format = 'json') => {
    const endpoint = getDashboardEndpoint();
    if (!endpoint) {
        throw new Error('Gösterge paneli uç noktası tanımlı değil');
    }

    const params = new URLSearchParams();
    if (chart) {
        params.set('chart', chart);
    }
    if (period) {
        params.set('period', period);
    }
    if (format && format !== 'json') {
        params.set('format', format);
    }

    const headers = {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: format === 'json' ? 'application/json' : 'application/octet-stream',
    };

    const response = await fetch(`${endpoint}?${params.toString()}`, { headers });
    if (!response.ok) {
        throw new Error(`Status ${response.status}`);
    }

    if (format === 'json') {
        return response.json();
    }

    return response;
};

const loadDashboardSummary = async () => {
    try {
        const json = await fetchDashboardData('summary', null, 'json');
        updateDashboardSummary(json.summary || json);
    } catch (error) {
        console.error('Özet verileri yüklenemedi', error);
    }
};

const loadDashboardChart = async (chart) => {
    const period = chart === 'traffic' ? dashboardState.trafficPeriod : dashboardState.revenuePeriod;
    try {
        const data = await fetchDashboardData(chart, period, 'json');
        if (chart === 'traffic') {
            renderDashboardTraffic(data);
        } else {
            renderDashboardRevenue(data);
        }
        if (data?.rangeLabel) {
            setDashboardRangeLabel(chart, data.rangeLabel);
        }
    } catch (error) {
        console.error(`Grafik verileri yüklenemedi (${chart})`, error);
    }
};

const parseFilename = (response, fallback) => {
    const header = response.headers.get('X-Filename');
    if (header) {
        return header;
    }
    const disposition = response.headers.get('Content-Disposition');
    if (disposition) {
        const match = /filename\*?=(?:UTF-8'')?"?([^";]+)/i.exec(disposition);
        if (match && match[1]) {
            try {
                return decodeURIComponent(match[1]);
            } catch (error) {
                return match[1];
            }
        }
    }
    return fallback;
};

const exportDashboardChart = async (chart, format) => {
    const period = chart === 'traffic' ? dashboardState.trafficPeriod : dashboardState.revenuePeriod;
    try {
        const response = await fetchDashboardData(chart, period, format);
        const blob = await response.blob();
        const filename = parseFilename(response, `${chart}-${period}.${format === 'excel' ? 'csv' : 'pdf'}`);
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error(`Grafik dışa aktarma işlemi başarısız (${chart}, ${format})`, error);
        Swal.fire({
            icon: 'error',
            title: 'Dışa aktarma başarısız',
            text: 'Dosya indirilemedi. Lütfen daha sonra tekrar deneyin.',
        });
    }
};

const submitAjaxAction = async (button) => {
    const url = button.dataset.url;
    const tableId = button.dataset.table;
    const actionValue = button.dataset.actionValue;
    const csrf = button.dataset.csrf || (tableId && document.getElementById(tableId)?.dataset.csrf) || '';
    const id = button.dataset.id;

    if (!url || !id) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('id', id);
    if (csrf) {
        formData.append('csrf_token', csrf);
    }
    if (actionValue && actionValue !== 'delete') {
        formData.append('action', actionValue);
    }

    const confirmMessage = button.dataset.confirm;
    const proceed = async () => {
        button.disabled = true;
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            });
            let json = null;
            try {
                json = await response.json();
            } catch (error) {
                json = null;
            }
            const success = json?.status === 'success' || response.ok;
            const message = json?.message || (success ? 'İşlem tamamlandı.' : 'İşlem gerçekleştirilemedi.');
            Swal.fire({ icon: success ? 'success' : 'error', title: message, confirmButtonColor: '#0d6efd' });
            if (success) {
                refreshTable(tableId);
            }
        } catch (error) {
            console.error('İşlem sırasında hata oluştu', error);
            Swal.fire({ icon: 'error', title: 'İşlem sırasında bir hata oluştu.', confirmButtonColor: '#0d6efd' });
        } finally {
            button.disabled = false;
        }
    };

    if (confirmMessage) {
        Swal.fire({
            title: confirmMessage,
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet',
            cancelButtonText: 'Vazgeç',
        }).then((result) => {
            if (result.isConfirmed) {
                proceed();
            }
        });
    } else {
        proceed();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    localizeTableRefreshButtons();

    const flash = document.querySelector('[data-flash-message]');
    if (flash) {
        const { type, message } = flash.dataset;
        Swal.fire({
            icon: type || 'success',
            title: message,
            confirmButtonColor: '#0d6efd',
        });
    }

    startHeartbeat();
    initFirebaseButtons();
    startNotificationPolling();

    document.querySelectorAll('[data-confirm]').forEach((element) => {
        if (element.dataset.confirmInitialized) {
            return;
        }
        if (element.hasAttribute('data-ajax-action')) {
            return;
        }
        element.dataset.confirmInitialized = '1';
        element.addEventListener('click', (event) => {
            const message = element.dataset.confirm || 'Emin misiniz?';
            event.preventDefault();
            Swal.fire({
                title: message,
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Evet',
                cancelButtonText: 'Vazgeç',
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                if (element.tagName === 'A' && element.getAttribute('href')) {
                    window.location.href = element.getAttribute('href');
                    return;
                }

                const form = element.closest('form');
                if (form) {
                    form.submit();
                }
            });
        });
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-ajax-action]');
        if (!button) {
            return;
        }
        event.preventDefault();
        submitAjaxAction(button);
    });

    document.querySelectorAll('[data-refresh-table]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const target = button.dataset.refreshTable;
            if (target && window.jQuery) {
                window.jQuery(target).bootstrapTable('refresh');
            }
        });
    });

    if (window.Dropzone) {
        document.querySelectorAll('.dropzone[data-dropzone-url]').forEach((element) => {
            if (element.dataset.dropzoneInitialized) {
                return;
            }

            element.dataset.dropzoneInitialized = '1';
            const url = element.dataset.dropzoneUrl;
            const type = element.dataset.dropzoneType || 'logo';
            const csrf = element.dataset.dropzoneCsrf || '';
            let accepted = 'image/png,image/jpeg,image/svg+xml';
            if (type === 'favicon') {
                accepted = 'image/png,image/x-icon,image/svg+xml';
            }
            const message = element.dataset.dropzoneMessage || 'Dosyayı sürükleyip bırakın veya tıklayın';

            const dz = new Dropzone(element, {
                url,
                paramName: 'file',
                maxFiles: 1,
                acceptedFiles: accepted,
                addRemoveLinks: true,
                dictDefaultMessage: message,
                timeout: 180000,
            });

            dz.on('maxfilesexceeded', (file) => {
                dz.removeAllFiles();
                dz.addFile(file);
            });

            dz.on('sending', (file, xhr, formData) => {
                formData.append('type', type);
                if (csrf) {
                    formData.append('csrf_token', csrf);
                }
            });

            dz.on('success', (file, response) => {
                let data = response;
                if (typeof response === 'string') {
                    try {
                        data = JSON.parse(response);
                    } catch (error) {
                        data = null;
                    }
                }

                if (!data || data.status !== 'success') {
                    const errorMessage = data && data.message ? data.message : 'Dosya yüklenemedi.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Yükleme başarısız',
                        text: errorMessage,
                        confirmButtonColor: '#0d6efd',
                    });
                    return;
                }

                const refresh = element.dataset.dropzoneRefresh === '1';
                const inputSelector = element.dataset.dropzoneInput;
                const storedValue = (data.relative || data.path || '').replace(/^\//, '');

                if (inputSelector) {
                    const target = document.querySelector(inputSelector);
                    if (target) {
                        target.value = storedValue;
                    }
                }

                const notify = () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Görsel güncellendi',
                        confirmButtonColor: '#0d6efd',
                    });
                };

                const keepPreview = type === 'notification';

                if (refresh) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Görsel güncellendi',
                        confirmButtonColor: '#0d6efd',
                    }).then(() => window.location.reload());
                } else {
                    notify();
                    if (!keepPreview) {
                        dz.removeFile(file);
                    } else if (file.previewElement) {
                        file.previewElement.classList.add('dz-success');
                    }
                }
            });

            dz.on('removedfile', () => {
                if (type === 'notification') {
                    const inputSelector = element.dataset.dropzoneInput;
                    if (inputSelector) {
                        const target = document.querySelector(inputSelector);
                        if (target) {
                            target.value = '';
                        }
                    }
                }
            });

            dz.on('error', (file, message) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Yükleme başarısız',
                    text: typeof message === 'string' ? message : 'Dosya yüklenemedi.',
                    confirmButtonColor: '#0d6efd',
                });
            });
        });
    }

    document.querySelectorAll('[data-purchase-form]').forEach((form) => {
        const select = form.querySelector('[data-payment-select]');
        const bankInfo = form.querySelector('[data-bank-info]');
        const hidden = form.querySelector('input[type="hidden"][name="payment_method"]');
        if (!bankInfo) {
            return;
        }

        const toggle = () => {
            let method = '';
            if (select && !select.disabled) {
                method = select.value;
            } else if (hidden) {
                method = hidden.value;
            } else if (select && select.dataset.default) {
                method = select.dataset.default;
            }
            if (method === 'bank') {
                bankInfo.removeAttribute('hidden');
            } else {
                bankInfo.setAttribute('hidden', 'hidden');
            }
        };

        toggle();
        if (select) {
            select.addEventListener('change', toggle);
        }
    });

    const usageRange = document.getElementById('usageRange');
    if (usageRange) {
        loadUsageMetrics(usageRange.value);
        usageRange.addEventListener('change', () => loadUsageMetrics(usageRange.value));
    }

    document.querySelectorAll('[data-export]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            exportUsage(button.dataset.export);
        });
    });

    const onlineRange = document.getElementById('onlineRange');
    if (onlineRange) {
        loadOnlineMetrics(onlineRange.value);
        onlineRange.addEventListener('change', () => loadOnlineMetrics(onlineRange.value));
    }

    document.querySelectorAll('[data-online-export]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            exportOnline(button.dataset.onlineExport);
        });
    });

    const onlineWindow = document.getElementById('onlineWindow');
    if (onlineWindow && window.jQuery) {
        onlineWindow.addEventListener('change', () => {
            window.jQuery('#onlineTable').bootstrapTable('refresh');
        });
    }

    const notificationRange = document.getElementById('notificationRange');
    const notificationFilter = document.getElementById('notificationFilter');
    if (notificationRange) {
        const load = () => {
            const selectedId = notificationFilter && notificationFilter.value ? Number(notificationFilter.value) : null;
            loadNotificationMetrics(notificationRange.value, selectedId || undefined);
        };
        load();
        notificationRange.addEventListener('change', load);
        if (notificationFilter) {
            notificationFilter.addEventListener('change', () => {
                load();
                refreshNotificationBreakdownTable();
            });
        }
    }

    const notificationBreakdownRange = document.getElementById('notificationBreakdownRange');
    if (notificationBreakdownRange) {
        notificationBreakdownRange.addEventListener('change', () => {
            refreshNotificationBreakdownTable();
        });
    }

    if (document.getElementById('notificationBreakdownTable')) {
        refreshNotificationBreakdownTable();
    }

    document.querySelectorAll('[data-notification-export]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            exportNotificationMetrics(button.dataset.notificationExport);
        });
    });

    document.querySelectorAll('[data-notification-breakdown-export]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            exportNotificationBreakdown(button.dataset.notificationBreakdownExport);
        });
    });

    document.querySelectorAll('[data-chart-export]').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            const chart = button.dataset.chart;
            const format = button.dataset.chartExport;
            if (!chart || !format) {
                return;
            }
            button.disabled = true;
            try {
                await exportDashboardChart(chart, format);
            } finally {
                button.disabled = false;
            }
        });
    });

    const trafficRange = document.getElementById('trafficRange');
    if (trafficRange) {
        dashboardState.trafficPeriod = trafficRange.value || dashboardState.trafficPeriod;
        trafficRange.addEventListener('change', () => {
            dashboardState.trafficPeriod = trafficRange.value || 'daily';
            loadDashboardChart('traffic');
        });
    }

    const revenueRange = document.getElementById('revenueRange');
    if (revenueRange) {
        dashboardState.revenuePeriod = revenueRange.value || dashboardState.revenuePeriod;
        revenueRange.addEventListener('change', () => {
            dashboardState.revenuePeriod = revenueRange.value || 'daily';
            loadDashboardChart('revenue');
        });
    }

    if (document.getElementById('dashboardSummary')) {
        loadDashboardSummary();
    }

    if (document.getElementById('dashboardTrafficChart')) {
        loadDashboardChart('traffic');
    }

    if (document.getElementById('dashboardRevenueChart')) {
        loadDashboardChart('revenue');
    }

    loadClientUsageMetrics();
});
