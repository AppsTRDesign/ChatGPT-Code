(function () {
    'use strict';

    const WebPushClient = {
        init(options) {
            this.endpoint = options.endpoint;
            this.apiKey = options.apiKey;
            this.clientId = options.clientId;
            this.registerServiceWorker(options.serviceWorkerPath || '/sw.js');
        },
        registerServiceWorker(swPath) {
            if (!('serviceWorker' in navigator)) {
                return;
            }

            navigator.serviceWorker.register(swPath).then((registration) => {
                console.log('Service worker registered', registration);
            }).catch((error) => {
                console.error('Service worker registration failed', error);
            });
        }
    };

    window.NoaSoftWebPush = WebPushClient;
})();
