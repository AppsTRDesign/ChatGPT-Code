(function () {
    'use strict';

    const STORAGE_KEY = 'noasoft-webpush-token';

    const WebPushClient = {
        async init(options = {}) {
            this.baseUrl = options.baseUrl || '';
            this.apiKey = options.apiKey;
            this.vapidPublicKey = options.vapidPublicKey;
            this.pollingInterval = options.pollingInterval || 60000;
            this.serviceWorkerPath = options.serviceWorkerPath || '/sw.js';

            if (!this.apiKey) {
                console.warn('NoaSoft Web Push: apiKey gereklidir.');
                return;
            }

            if (!('serviceWorker' in navigator)) {
                console.warn('NoaSoft Web Push: Service worker desteklenmiyor.');
                return;
            }

            try {
                this.registration = await navigator.serviceWorker.register(this.serviceWorkerPath);
                await navigator.serviceWorker.ready;
                await this.ensureSubscription();
            } catch (error) {
                console.error('NoaSoft Web Push: Service worker kaydı başarısız.', error);
            }
        },

        async ensureSubscription() {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                console.warn('NoaSoft Web Push: Bildirim izni verilmedi.');
                return;
            }

            const registration = await navigator.serviceWorker.ready;
            let subscription = await registration.pushManager.getSubscription();

            if (!subscription && this.vapidPublicKey) {
                try {
                    subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(this.vapidPublicKey)
                    });
                } catch (error) {
                    console.error('NoaSoft Web Push: Push aboneliği oluşturulamadı.', error);
                }
            }

            const token = subscription ? btoa(subscription.endpoint) : localStorage.getItem(STORAGE_KEY) || crypto.randomUUID();
            localStorage.setItem(STORAGE_KEY, token);

            await this.syncServiceWorker(token);
            await this.registerToken(subscription, token);
            this.startInboxPolling(token);
        },

        async syncServiceWorker(token) {
            const registration = await navigator.serviceWorker.ready;
            const payload = {
                type: 'config',
                apiKey: this.apiKey,
                baseUrl: this.baseUrl,
                token
            };

            if (registration.active) {
                registration.active.postMessage(payload);
            }
        },

        async registerToken(subscription, token) {
            const body = {
                api_key: this.apiKey,
                token,
                endpoint: subscription ? subscription.endpoint : token,
                user_agent: navigator.userAgent,
                public_key: subscription?.keys?.p256dh,
                auth_token: subscription?.keys?.auth
            };

            try {
                await fetch(`${this.baseUrl}/api/tokens`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
            } catch (error) {
                console.error('NoaSoft Web Push: Token kaydedilemedi.', error);
            }
        },

        startInboxPolling(token) {
            if (!token) {
                return;
            }

            const poll = () => {
                fetch(`${this.baseUrl}/api/inbox`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ api_key: this.apiKey, token })
                })
                    .then((response) => response.json())
                    .then((data) => {
                        const notifications = data.notifications || [];
                        if (!notifications.length) {
                            return;
                        }

                        navigator.serviceWorker.ready.then((registration) => {
                            if (!registration.active) {
                                return;
                            }

                            notifications.forEach((notification) => {
                                registration.active.postMessage({
                                    type: 'show-notification',
                                    payload: {
                                        ...notification,
                                        api_key: this.apiKey,
                                        token
                                    }
                                });
                            });
                        });
                    })
                    .catch(() => {});
            };

            poll();
            clearInterval(this.poller);
            this.poller = setInterval(poll, this.pollingInterval);
        }
    };

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    window.NoaSoftWebPush = WebPushClient;

    if (window.NoaSoftWebPushConfig) {
        WebPushClient.init(window.NoaSoftWebPushConfig);
    }
})();
