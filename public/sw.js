self.addEventListener('install', (event) => {
    console.log('NoaSoft Web Push service worker installed');
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    console.log('NoaSoft Web Push service worker activated');
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'NoaSoft Web Push';
    const options = {
        body: data.body || '',
        icon: data.icon || '/assets/icons/push-icon.svg',
        data: {
            url: data.url || '/'
        }
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url;

    if (url) {
        event.waitUntil(
            self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
                for (const client of clientList) {
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }
                if (self.clients.openWindow) {
                    return self.clients.openWindow(url);
                }
            })
        );
    }
});
