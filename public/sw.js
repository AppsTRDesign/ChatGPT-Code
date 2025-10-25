const NoaSoftState = {
    apiKey: null,
    baseUrl: '',
    token: null,
    displayed: new Set()
};

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('message', (event) => {
    const data = event.data || {};
    if (data.type === 'config') {
        NoaSoftState.apiKey = data.apiKey || NoaSoftState.apiKey;
        NoaSoftState.baseUrl = data.baseUrl || NoaSoftState.baseUrl;
        NoaSoftState.token = data.token || NoaSoftState.token;
    }

    if (data.type === 'show-notification' && data.payload) {
        event.waitUntil(showNotification(data.payload));
    }
});

self.addEventListener('push', (event) => {
    const payload = event.data ? event.data.json() : {};
    event.waitUntil(showNotification(payload));
});

self.addEventListener('notificationclick', (event) => {
    const data = event.notification.data || {};
    event.notification.close();

    if (data.url) {
        event.waitUntil(
            self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
                for (const client of clients) {
                    if (client.url === data.url && 'focus' in client) {
                        return client.focus();
                    }
                }
                if (self.clients.openWindow) {
                    return self.clients.openWindow(data.url);
                }
            })
        );
    }

    sendReceipt('clicked', data);
});

self.addEventListener('notificationclose', (event) => {
    const data = event.notification.data || {};
    sendReceipt('closed', data);
});

async function showNotification(payload) {
    const id = payload.notification_id || payload.id;
    if (id && NoaSoftState.displayed.has(id)) {
        return;
    }

    if (id) {
        NoaSoftState.displayed.add(id);
    }

    const title = payload.title || 'NoaSoft Web Push';
    const options = {
        body: payload.message || payload.body || '',
        icon: payload.icon || '/assets/icons/push-icon.svg',
        badge: payload.badge || '/assets/icons/push-icon.svg',
        tag: id ? `noasoft-${id}` : undefined,
        requireInteraction: payload.requireInteraction || false,
        data: {
            url: payload.target_url || payload.url || '/',
            notification_id: id,
            token: payload.token || NoaSoftState.token,
            api_key: payload.api_key || NoaSoftState.apiKey
        }
    };

    if (payload.expires_at) {
        options.timestamp = Date.parse(payload.expires_at);
    }

    return self.registration.showNotification(title, options);
}

function sendReceipt(eventType, data) {
    if (!data.notification_id || !NoaSoftState.baseUrl) {
        return;
    }

    const payload = {
        api_key: data.api_key || NoaSoftState.apiKey,
        notification_id: data.notification_id,
        token: data.token || NoaSoftState.token,
        event: eventType
    };

    if (!payload.api_key || !payload.token) {
        return;
    }

    fetch(`${NoaSoftState.baseUrl}/api/receipts`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).catch(() => {});
}
