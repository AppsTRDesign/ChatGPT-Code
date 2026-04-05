(function () {
    'use strict';

    const cfg = window.APP_CONFIG || {};
    const csrf = cfg.csrf || '';
    const toastEl = document.getElementById('appToast');
    const toastBody = document.getElementById('toastBody');
    const bsToast = toastEl ? new bootstrap.Toast(toastEl) : null;

    const showToast = (message) => {
        if (!toastBody || !bsToast || !message) return;
        toastBody.textContent = message;
        bsToast.show();
    };

    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = Number(value || 0).toLocaleString('tr-TR');
        }
    };

    const refreshState = async () => {
        const response = await fetch('/api/state', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) return;
        const payload = await response.json();
        if (!payload.ok || !payload.data || !payload.data.player) return;

        const player = payload.data.player;
        setText('treasury', player.treasury);
        setText('population', player.population);
        setText('soldiers', player.soldiers);
        setText('influence', player.influence);
    };

    const doAction = async (action, amount = 10) => {
        const fd = new FormData();
        fd.append('_csrf', csrf);
        if (action === 'train') {
            fd.append('amount', String(amount));
        }

        const response = await fetch(`/api/action/${action}`, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        const payload = await response.json();
        showToast(payload.message || 'İşlem tamamlandı.');
        await refreshState();
    };

    document.querySelectorAll('.action-btn').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                const action = button.dataset.action;
                const amountInput = document.getElementById('trainAmount');
                const amount = amountInput ? Number(amountInput.value || 10) : 10;
                await doAction(action, amount);
            } catch (error) {
                showToast('İşlem sırasında bir hata oluştu.');
            }
        });
    });

    if (cfg.toastMessage) {
        showToast(cfg.toastMessage);
    }

    refreshState().catch(() => null);
    setInterval(() => {
        refreshState().catch(() => null);
    }, 15000);
})();
