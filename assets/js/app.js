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

const initOneSignalClient = () => {
    const config = appConfig.onesignal || {};
    if (!config.enabled || !config.appId || !appConfig.user) {
        return;
    }

    const storageKey = 'onesignal_prompt_last';
    const storage = (() => {
        try {
            return window.localStorage || null;
        } catch (error) {
            return null;
        }
    })();

    const shouldPrompt = () => {
        if (!storage) {
            return true;
        }
        const cooldownMs = 1000 * 60 * 60 * 12; // 12 hours
        const last = parseInt(storage.getItem(storageKey) || '0', 10);
        return Number.isNaN(last) || Date.now() - last > cooldownMs;
    };

    const markPrompt = () => {
        if (!storage) {
            return;
        }
        storage.setItem(storageKey, `${Date.now()}`);
    };

    window.OneSignalDeferred = window.OneSignalDeferred || [];
    window.OneSignalDeferred.push(async (OneSignal) => {
        try {
            const pushSupported = typeof OneSignal.Notifications.isPushSupported === 'function'
                ? await OneSignal.Notifications.isPushSupported()
                : true;
            if (!pushSupported) {
                return;
            }

            await OneSignal.init({
                appId: config.appId,
                notifyButton: { enable: false },
                allowLocalhostAsSecureOrigin: true,
                serviceWorkerConfig: {
                    path: config.workerPath || '/OneSignalSDKWorker.js',
                    scope: '/',
                    workerName: 'OneSignalSDKWorker.js',
                },
            });

            const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

            const ensureSubscribed = async () => {
                try {
                    const permissionState = await OneSignal.Notifications.permission;
                    if (permissionState !== 'granted') {
                        return false;
                    }
                    const isSubscribed = await OneSignal.User.PushSubscription.subscribed;
                    if (isSubscribed) {
                        return true;
                    }
                    await OneSignal.User.PushSubscription.subscribe();
                    return await OneSignal.User.PushSubscription.subscribed;
                } catch (error) {
                    console.warn('OneSignal subscribe failed', error);
                    return false;
                }
            };

            const resolveSubscriptionId = async (hintId = null) => {
                const attempts = 5;
                for (let index = 0; index < attempts; index += 1) {
                    if (hintId) {
                        return hintId;
                    }

                    try {
                        const id = await OneSignal.User.PushSubscription.id;
                        if (id) {
                            return id;
                        }
                    } catch (error) {
                        // ignore and retry using getId fallback
                    }

                    if (typeof OneSignal.User.PushSubscription.getId === 'function') {
                        try {
                            const id = await OneSignal.User.PushSubscription.getId();
                            if (id) {
                                return id;
                            }
                        } catch (error) {
                            // ignore and retry
                        }
                    }

                    await wait(200 * (index + 1));
                }

                return null;
            };

            const resolveSubscriptionPlatform = async (hintPlatform = null) => {
                if (hintPlatform) {
                    return hintPlatform;
                }

                try {
                    const token = await OneSignal.User.PushSubscription.token;
                    if (token && typeof token.type === 'string' && token.type) {
                        return token.type;
                    }
                } catch (error) {
                    // ignore token resolution issues
                }

                if (typeof OneSignal.User.PushSubscription.getToken === 'function') {
                    try {
                        const token = await OneSignal.User.PushSubscription.getToken();
                        if (token && typeof token.type === 'string' && token.type) {
                            return token.type;
                        }
                    } catch (error) {
                        // ignore token resolution issues
                    }
                }

                return 'web';
            };

            const register = async (hintId = null, hintPlatform = null) => {
                try {
                    const subscribed = await ensureSubscribed();
                    if (!subscribed) {
                        return;
                    }

                    const id = await resolveSubscriptionId(hintId);
                    if (!id) {
                        console.warn('OneSignal id unavailable');
                        return;
                    }

                    const platform = await resolveSubscriptionPlatform(hintPlatform);

                    const locale = (navigator.language || '').trim();
                    let language = '';
                    let country = '';
                    if (locale) {
                        const normalized = locale.replace('_', '-');
                        const parts = normalized.split('-');
                        language = parts[0] || '';
                        country = parts.length > 1 ? parts[1] || '' : '';
                    }

                    const payload = {
                        player_id: id,
                        platform,
                    };

                    if (language) {
                        payload.language = language.toLowerCase();
                    }

                    if (country) {
                        payload.country = country.toUpperCase();
                    }

                    const response = await fetch(config.registerEndpoint || '/client/onesignal-register', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload),
                    });

                    const raw = await response.text();
                    let data = null;
                    try {
                        data = raw ? JSON.parse(raw) : null;
                    } catch (error) {
                        data = null;
                    }

                    if (!response.ok || !data || data.status !== 'ok') {
                        console.warn('OneSignal register error', {
                            status: response.status,
                            payload: data || raw,
                        });
                    }
                } catch (error) {
                    console.warn('OneSignal register failed', error);
                }
            };

            const unregister = async (previousId) => {
                if (!previousId) {
                    return;
                }
                try {
                    await fetch(config.registerEndpoint || '/client/onesignal-register', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ player_id: '', previous: previousId }),
                    });
                } catch (error) {
                    console.warn('OneSignal unregister failed', error);
                }
            };

            OneSignal.User.PushSubscription.addEventListener('change', async (event) => {
                if (event && event.id) {
                    let platformHint = null;
                    if (event.subscription && event.subscription.token && event.subscription.token.type) {
                        platformHint = event.subscription.token.type;
                    }
                    await register(event.id, platformHint);
                } else if (event.previousId) {
                    await unregister(event.previousId);
                }
            });

            OneSignal.Notifications.addEventListener('permissionChange', async ({ permission }) => {
                if (permission === 'granted') {
                    await register();
                } else if (permission === 'denied') {
                    let previous = null;
                    try {
                        previous = await OneSignal.User.PushSubscription.id;
                    } catch (error) {
                        previous = null;
                    }
                    if (previous) {
                        await unregister(previous);
                    }
                }
            });

            const permission = await OneSignal.Notifications.permission;
            if (permission === 'granted') {
                await register();
                return;
            }

            if (permission === 'default' && shouldPrompt()) {
                markPrompt();
                if (window.Swal) {
                    const result = await Swal.fire({
                        icon: 'info',
                        title: 'Bildirimlere izin verin',
                        text: 'Güncellemeleri kaçırmamak için tarayıcı bildirimlerine izin verin.',
                        showCancelButton: true,
                        confirmButtonText: 'İzin ver',
                        cancelButtonText: 'Daha sonra',
                        confirmButtonColor: '#0d6efd',
                    });
                    if (!result.isConfirmed) {
                        return;
                    }
                }

                const outcome = await OneSignal.Notifications.requestPermission({ fallbackToSettings: true });
                if (outcome === 'granted') {
                    await register();
                }
            }
        } catch (error) {
            console.error('OneSignal başlangıç hatası', error);
        }
    });
};

const initPushForms = () => {
    const form = document.querySelector('[data-push-form]');
    if (!form) {
        return;
    }

    const recipientsInput = form.querySelector('[data-push-recipients]');
    const audienceRadios = form.querySelectorAll('input[name="audience"]');
    const table = document.getElementById('pushRecipientsTable');
    const preselectUser = parseInt(form.dataset.preselectUser || '', 10);
    const tableCard = table ? table.closest('.card') : null;

    const updateRecipients = () => {
        if (!recipientsInput || !window.jQuery || !table) {
            return;
        }
        try {
            const selected = window.jQuery(table).bootstrapTable('getSelections').map((row) => row.id);
            recipientsInput.value = selected.join(',');
        } catch (error) {
            recipientsInput.value = '';
        }
    };

    const toggleAudience = () => {
        if (!tableCard) {
            return;
        }
        const selected = form.querySelector('input[name="audience"]:checked');
        if (selected && selected.value === 'selected') {
            tableCard.classList.remove('table-disabled');
        } else {
            tableCard.classList.add('table-disabled');
        }
    };

    audienceRadios.forEach((radio) => {
        radio.addEventListener('change', toggleAudience);
    });
    toggleAudience();

    if (window.jQuery && table) {
        const $table = window.jQuery(table);
        $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', updateRecipients);
        $table.on('load-success.bs.table', () => {
            updateRecipients();
            if (!Number.isNaN(preselectUser) && preselectUser > 0) {
                $table.bootstrapTable('checkBy', { field: 'id', values: [preselectUser] });
            }
        });
    }

    form.addEventListener('submit', () => {
        updateRecipients();
    });

    updateRecipients();
};

initOneSignalClient();

const initOneSignalSyncButton = () => {
    const button = document.querySelector('[data-onesignal-sync]');
    if (!button) {
        return;
    }

    const originalLabel = button.innerHTML;

    button.addEventListener('click', async () => {
        if (button.disabled) {
            return;
        }

        const csrf = button.dataset.csrf || '';
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Eşitleniyor...';

        try {
            const response = await fetch('/admin/onesignal-sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ csrf_token: csrf }),
            });

            let data = null;
            try {
                data = await response.json();
            } catch (error) {
                data = null;
            }

            const success = !!(data && data.success && response.ok);
            const message = (data && data.message) || (success ? 'OneSignal aboneleri eşitlendi.' : 'Eşitleme sırasında hata oluştu.');

            if (window.Swal) {
                Swal.fire({ icon: success ? 'success' : 'error', title: message, confirmButtonColor: '#0d6efd' });
            }

            if (success) {
                refreshTable('pushRecipientsTable');
            }
        } catch (error) {
            console.error('OneSignal eşitleme hatası', error);
            if (window.Swal) {
                Swal.fire({ icon: 'error', title: 'OneSignal cihazları alınamadı.', confirmButtonColor: '#0d6efd' });
            }
        } finally {
            button.disabled = false;
            button.innerHTML = originalLabel;
        }
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
    pushRecipientsHandler: (response) => response,
    pushRecipientRowStyle: (row) => {
        if (row && row.guest) {
            return {
                classes: 'table-row-guest',
            };
        }
        return {};
    },
    pushRecipientNameFormatter: (value, row) => {
        if (!row) {
            return '<span class="text-white-50">-</span>';
        }
        const name = value || 'Ziyaretçi';
        if (row.guest) {
            return `<span class="badge bg-secondary text-uppercase">${escapeHtml(name)}</span>`;
        }
        return escapeHtml(name);
    },
    pushRecipientEmailFormatter: (value, row) => {
        if (!value) {
            return row && row.guest ? '<span class="text-white-50">-</span>' : '<span class="text-white-50">-</span>';
        }
        return `<span class="small">${escapeHtml(value)}</span>`;
    },
    pushRecipientPlatformFormatter: (value) => {
        if (!value) {
            return '<span class="text-white-50">-</span>';
        }
        const label = value.toString().toLowerCase();
        const map = {
            web: 'Web',
            chrome: 'Chrome',
            firefox: 'Firefox',
            safari: 'Safari',
        };
        return `<span class="badge bg-info text-dark">${escapeHtml(map[label] || value)}</span>`;
    },
    pushRecipientLocaleFormatter: (value, row) => {
        const language = (row && row.language) ? row.language.toString().toLowerCase() : '';
        const country = (row && row.country) ? row.country.toString().toUpperCase() : '';
        if (!language && !country) {
            return '<span class="text-white-50">-</span>';
        }
        const parts = [];
        if (language) {
            parts.push(language);
        }
        if (country) {
            parts.push(country);
        }
        return `<span class="small">${escapeHtml(parts.join(' / '))}</span>`;
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
    audienceFormatter: (value) => {
        if (value === 'all') {
            return '<span class="badge bg-primary">Tüm Üyeler</span>';
        }
        if (value === 'selected') {
            return '<span class="badge bg-info text-dark">Seçili Üyeler</span>';
        }
        return `<span class="badge bg-secondary">${escapeHtml(value || '-')}</span>`;
    },
    pushEventFormatter: (value) => {
        const map = {
            delivered: { label: 'Gönderildi', class: 'bg-primary' },
            viewed: { label: 'Görüntülendi', class: 'bg-success' },
            clicked: { label: 'Tıklandı', class: 'bg-warning text-dark' },
        };
        const info = map[value] || { label: value || '-', class: 'bg-secondary' };
        return `<span class="badge rounded-pill ${info.class}">${escapeHtml(info.label)}</span>`;
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
    webPushHandler: (response) => {
        updateWebPushEventOptions(response.rows || []);
        return response;
    },
    webPushEventsHandler: (response) => {
        const rows = (response.rows || []).map((row) => ({
            ...row,
            search: { engine: row.search_engine, term: row.search_term },
        }));
        return { ...response, rows };
    },
    webPushEventsQuery: (params) => {
        const select = document.getElementById('webPushEventFilter');
        const campaignId = select ? select.value : '';
        return { ...params, campaign_id: campaignId || '' };
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
let clientUsageChart;
let clientUsageMetrics = [];

const updateWebPushEventOptions = (() => {
    const campaigns = new Map();
    return (rows = []) => {
        rows.forEach((row) => {
            if (row && row.id) {
                campaigns.set(String(row.id), row.title || `#${row.id}`);
            }
        });
        const select = document.getElementById('webPushEventFilter');
        if (!select) {
            return;
        }
        const previous = select.value;
        const fragment = document.createDocumentFragment();
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Tümü';
        fragment.appendChild(defaultOption);
        Array.from(campaigns.entries())
            .sort((a, b) => a[1].localeCompare(b[1], 'tr'))
            .forEach(([id, title]) => {
                const option = document.createElement('option');
                option.value = id;
                option.textContent = title;
                fragment.appendChild(option);
            });
        select.innerHTML = '';
        select.appendChild(fragment);
        if (previous && campaigns.has(previous)) {
            select.value = previous;
        }
    };
})();

let webPushChart;
let webPushMetrics = [];
let onlineChart;
let onlineMetrics = [];
const displayedCampaigns = new Set();

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

const updateWebPushChart = (labels, delivered, viewed, clicked) => {
    const canvas = document.getElementById('webPushChart');
    if (!canvas || !window.Chart) {
        return;
    }

    const datasets = [
        {
            label: 'Gönderildi',
            data: delivered,
            backgroundColor: 'rgba(59, 130, 246, 0.65)',
            borderColor: '#3b82f6',
            borderWidth: 1.5,
            borderRadius: 8,
            maxBarThickness: 36,
        },
        {
            label: 'Görüntülendi',
            data: viewed,
            backgroundColor: 'rgba(34, 197, 94, 0.65)',
            borderColor: '#22c55e',
            borderWidth: 1.5,
            borderRadius: 8,
            maxBarThickness: 36,
        },
        {
            label: 'Tıklandı',
            data: clicked,
            backgroundColor: 'rgba(250, 204, 21, 0.65)',
            borderColor: '#facc15',
            borderWidth: 1.5,
            borderRadius: 8,
            maxBarThickness: 36,
        },
    ];

    const maxValue = [...delivered, ...viewed, ...clicked].reduce((acc, val) => Math.max(acc, val || 0), 0);
    const stepSize = maxValue <= 6 ? 1 : Math.ceil(maxValue / 5);

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: 8 },
        scales: {
            x: {
                stacked: false,
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
                borderColor: 'rgba(96, 165, 250, 0.4)',
                borderWidth: 1,
                titleColor: '#f8fafc',
                bodyColor: '#f8fafc',
            },
        },
    };

    if (!webPushChart) {
        webPushChart = new Chart(canvas, { type: 'bar', data: { labels, datasets }, options });
    } else {
        webPushChart.data = { labels, datasets };
        webPushChart.options = options;
        webPushChart.update();
    }
};

const updateWebPushSummary = (rows) => {
    const container = document.getElementById('webPushSummary');
    if (!container) {
        return;
    }
    if (!rows.length) {
        container.innerHTML = '<li class="text-white-50">Veri bulunamadı.</li>';
        return;
    }
    const totals = rows.reduce((acc, row) => {
        acc.delivered += Number(row.delivered || 0);
        acc.viewed += Number(row.viewed || 0);
        acc.clicked += Number(row.clicked || 0);
        return acc;
    }, { delivered: 0, viewed: 0, clicked: 0 });
    const campaigns = rows.length;
    container.innerHTML = `
        <li class="mb-2"><strong>Kampanya Sayısı:</strong> ${campaigns}</li>
        <li class="mb-2"><strong>Gönderilen:</strong> ${totals.delivered}</li>
        <li class="mb-2"><strong>Görüntülenen:</strong> ${totals.viewed}</li>
        <li class="mb-0"><strong>Tıklanan:</strong> ${totals.clicked}</li>
    `;
};

const loadWebPushMetrics = async (range) => {
    try {
        const response = await fetch(`/admin/data/web-push-metrics?range=${encodeURIComponent(range)}`, { headers: { Accept: 'application/json' } });
        const json = await response.json();
        webPushMetrics = json.rows || [];
        const labels = webPushMetrics.map((row) => row.label).reverse();
        const delivered = webPushMetrics.map((row) => Number(row.delivered || 0)).reverse();
        const viewed = webPushMetrics.map((row) => Number(row.viewed || 0)).reverse();
        const clicked = webPushMetrics.map((row) => Number(row.clicked || 0)).reverse();
        updateWebPushChart(labels, delivered, viewed, clicked);
        updateWebPushSummary(webPushMetrics);
    } catch (error) {
        console.error('Web push metrikleri yüklenemedi', error);
    }
};

const exportWebPush = (format) => {
    if (!webPushMetrics.length) {
        Swal.fire({ icon: 'warning', title: 'İndirilecek veri bulunamadı.', confirmButtonColor: '#0d6efd' });
        return;
    }

    const rows = webPushMetrics.map((row) => [row.label, Number(row.delivered || 0), Number(row.viewed || 0), Number(row.clicked || 0)]);

    if (format === 'pdf' && window.jspdf && window.jspdf.jsPDF) {
        const doc = new window.jspdf.jsPDF({ orientation: 'landscape' });
        registerTurkishFont(doc);
        doc.setFont('DejaVuSans', 'bold');
        doc.setFontSize(16);
        doc.text('Web Push Performans Raporu', 14, 18);
        doc.setFont('DejaVuSans', 'normal');
        doc.autoTable({
            head: [['Dönem', 'Gönderildi', 'Görüntülendi', 'Tıklandı']],
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
                fillColor: [59, 130, 246],
                textColor: 255,
            },
            alternateRowStyles: { fillColor: [24, 33, 58] },
        });
        doc.save('web-push-raporu.pdf');
        return;
    }

    if (format === 'excel' && window.XLSX) {
        const worksheet = window.XLSX.utils.aoa_to_sheet([
            ['Dönem', 'Gönderildi', 'Görüntülendi', 'Tıklandı'],
            ...rows,
        ]);
        const workbook = window.XLSX.utils.book_new();
        window.XLSX.utils.book_append_sheet(workbook, worksheet, 'Rapor');
        window.XLSX.writeFile(workbook, 'web-push-raporu.xlsx');
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
    const current = totals[totals.length - 1];
    const peak = Math.max(...totals);
    const sum = totals.reduce((acc, value) => acc + value, 0);
    const average = Math.round(sum / totals.length);
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

const sendPushEvent = async (campaignId, event, extra = {}) => {
    try {
        const config = window.APP_CONFIG || {};
        const csrf = config.csrf || '';
        await fetch('/client/push-event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ campaign_id: campaignId, event, ...extra }),
        });
    } catch (error) {
        console.error('Push etkinliği kaydedilemedi', error);
    }
};

const createToastContainer = () => {
    let container = document.getElementById('webPushToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'webPushToastContainer';
        container.className = 'web-push-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
    }
    return container;
};

const displayWebPushToast = (campaign) => {
    if (!campaign || displayedCampaigns.has(campaign.id)) {
        return;
    }
    displayedCampaigns.add(campaign.id);

    const container = createToastContainer();
    const toast = document.createElement('div');
    toast.className = 'toast show web-push-toast align-items-center text-bg-dark border-0 shadow-lg';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.dataset.bsAutohide = 'false';

    const image = campaign.image_url ? `<img src="${campaign.image_url}" alt="" class="rounded me-3 web-push-thumb">` : '';
    const actionButton = campaign.target_url
        ? `<button type="button" class="btn btn-sm btn-primary mt-3" data-action="open">Detayları Gör</button>`
        : '';

    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <div class="d-flex align-items-center gap-3">
                    ${image}
                    <div>
                        <h6 class="fw-semibold mb-1">${escapeHtml(campaign.title)}</h6>
                        <p class="mb-0 small text-white-50">${escapeHtml(campaign.message)}</p>
                        ${actionButton}
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Kapat"></button>
        </div>
    `;

    container.appendChild(toast);

    if (window.bootstrap && window.bootstrap.Toast) {
        const toastInstance = new window.bootstrap.Toast(toast, { autohide: true, delay: 12000 });
        toastInstance.show();
    }

    const referer = window.location.href;
    setTimeout(() => {
        sendPushEvent(campaign.id, 'viewed', { referer });
    }, 1000);

    toast.addEventListener('click', (event) => {
        const target = event.target;
        if (target && target.matches('[data-action="open"]') && campaign.target_url) {
            event.preventDefault();
            sendPushEvent(campaign.id, 'clicked', { referer });
            window.open(campaign.target_url, '_blank', 'noopener');
        }
    });

    toast.addEventListener('hidden.bs.toast', () => {
        setTimeout(() => {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
        }, 200);
    });
};

const initWebPushPolling = () => {
    if (!window.fetch || !window.APP_CONFIG) {
        return;
    }

    const poll = async () => {
        try {
            const response = await fetch('/client/data/push-poll', { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                throw new Error(`Status ${response.status}`);
            }
            const json = await response.json();
            (json.campaigns || []).forEach((campaign) => displayWebPushToast(campaign));
        } catch (error) {
            console.error('Web push bildirimleri alınamadı', error);
        } finally {
            setTimeout(poll, 60000);
        }
    };

    setTimeout(poll, 3000);
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

const renderDashboardTraffic = (labels, usage, registrations) => {
    const canvas = document.getElementById('dashboardTrafficChart');
    if (!canvas || !window.Chart) {
        return;
    }

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

const renderDashboardRevenue = (labels, totals) => {
    const canvas = document.getElementById('dashboardRevenueChart');
    if (!canvas || !window.Chart) {
        return;
    }

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

const loadDashboardMetrics = async () => {
    if (!window.dashboardConfig || !window.dashboardConfig.endpoint) {
        return;
    }

    try {
        const response = await fetch(window.dashboardConfig.endpoint, { headers: { Accept: 'application/json' } });
        const json = await response.json();
        if (json.usage && json.registrations) {
            const labels = json.usage.map((row) => row.label);
            const usageData = json.usage.map((row) => Number(row.total || 0));
            const registrationData = json.registrations.map((row) => Number(row.total || 0));
            renderDashboardTraffic(labels, usageData, registrationData);
        }
        if (json.revenue) {
            const labels = json.revenue.map((row) => row.label);
            const totals = json.revenue.map((row) => Number(row.total || 0));
            renderDashboardRevenue(labels, totals);
        }
        updateDashboardSummary(json.summary || null);
    } catch (error) {
        console.error('Gösterge paneli verileri yüklenemedi', error);
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
    initWebPushPolling();
    initFirebaseButtons();
    initPushForms();
    initOneSignalSyncButton();

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

    if (window.Dropzone) {
        document.querySelectorAll('.dropzone[data-dropzone-url]').forEach((element) => {
            if (element.dataset.dropzoneInitialized) {
                return;
            }

            element.dataset.dropzoneInitialized = '1';
            const url = element.dataset.dropzoneUrl;
            const type = element.dataset.dropzoneType || 'logo';
            const csrf = element.dataset.dropzoneCsrf || '';
            const accepted = type === 'favicon'
                ? 'image/png,image/x-icon,image/svg+xml'
                : 'image/png,image/jpeg,image/svg+xml';
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
                const previewSelector = element.dataset.dropzonePreview;
                const storedValue = (data.relative || data.path || '').replace(/^\//, '');

                if (inputSelector) {
                    const target = document.querySelector(inputSelector);
                    if (target) {
                        target.value = storedValue;
                    }
                }

                if (previewSelector) {
                    const preview = document.querySelector(previewSelector);
                    if (preview) {
                        const urlValue = data.url || data.path || data.relative || '';
                        const absolute = buildAbsoluteUrl(urlValue);
                        if (absolute) {
                            preview.innerHTML = `<img src="${escapeHtml(absolute)}" class="img-fluid rounded" alt="">`;
                        } else {
                            preview.innerHTML = '<span class="text-white-50 small">Görsel hazır.</span>';
                        }
                    }
                }

                const notify = () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Görsel güncellendi',
                        confirmButtonColor: '#0d6efd',
                    });
                };

                if (refresh) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Görsel güncellendi',
                        confirmButtonColor: '#0d6efd',
                    }).then(() => window.location.reload());
                } else {
                    notify();
                    dz.removeFile(file);
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

    const webPushRange = document.getElementById('webPushRange');
    if (webPushRange) {
        loadWebPushMetrics(webPushRange.value);
        webPushRange.addEventListener('change', () => loadWebPushMetrics(webPushRange.value));
    }

    document.querySelectorAll('[data-web-push-export]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            exportWebPush(button.dataset.webPushExport);
        });
    });

    const webPushEventFilter = document.getElementById('webPushEventFilter');
    if (webPushEventFilter && window.jQuery) {
        webPushEventFilter.addEventListener('change', () => {
            window.jQuery('#webPushEventsTable').bootstrapTable('refresh');
        });
    }

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

    loadDashboardMetrics();
    loadClientUsageMetrics();
});
