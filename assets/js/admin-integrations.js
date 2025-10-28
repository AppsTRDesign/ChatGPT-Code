(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const container = document.querySelector('.container');
    if (!container) {
        return;
    }

    const resultElements = {
        plesk: document.querySelector('[data-result="plesk"]'),
        ws: document.querySelector('[data-result="ws"]'),
    };

    const setResult = (key, message, type = 'info') => {
        const el = resultElements[key];
        if (!el) {
            return;
        }
        el.innerHTML = `<div class="alert alert-${type} border-0">${message}</div>`;
    };

    container.addEventListener('click', async (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button) {
            return;
        }
        const action = button.dataset.action;
        button.disabled = true;
        setResult(action === 'plesk-test' ? 'plesk' : 'ws', 'Test ediliyor...', 'secondary');
        try {
            const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action, csrf_token: appConfig.csrfToken })
            });
            const data = await response.json();
            if (data.status === 'success') {
                setResult(action === 'plesk-test' ? 'plesk' : 'ws', data.message || 'Bağlantı başarılı.', 'success');
            } else {
                throw new Error(data.message || 'Bağlantı başarısız.');
            }
        } catch (error) {
            setResult(action === 'plesk-test' ? 'plesk' : 'ws', error.message, 'danger');
        } finally {
            button.disabled = false;
        }
    });
})();
