(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const container = document.getElementById('notificationList');
    const filterSelect = document.getElementById('notificationFilter');
    const template = document.getElementById('notificationTemplate');

    if (!container || !template) {
        return;
    }

    document.dispatchEvent(new CustomEvent('realtime:subscribe', { detail: { channel: 'transactions' } }));

    let notifications = [];

    const statusBadge = (status) => {
        switch (status) {
            case 'approved':
                return 'badge bg-success';
            case 'rejected':
                return 'badge bg-danger';
            case 'insufficient':
                return 'badge bg-warning text-dark';
            default:
                return 'badge bg-secondary';
        }
    };

    const render = () => {
        const filter = filterSelect?.value || '';
        container.innerHTML = '';
        const items = notifications.filter(item => !filter || item.status === filter);
        if (!items.length) {
            container.innerHTML = '<div class="col-12 text-center text-white-50 py-4">Bildirim bulunamadı.</div>';
            return;
        }
        items.forEach(item => {
            const clone = template.content.cloneNode(true);
            const root = clone.querySelector('.card');
            root.dataset.id = item.id;
            const userEl = clone.querySelector('[data-notify-user]');
            const packageEl = clone.querySelector('[data-notify-package]');
            const amountEl = clone.querySelector('[data-notify-amount]');
            const providerEl = clone.querySelector('[data-notify-provider]');
            const dateEl = clone.querySelector('[data-notify-date]');
            const statusEl = clone.querySelector('[data-notify-status]');
            const noteEl = clone.querySelector('[data-notify-note]');
            const filesEl = clone.querySelector('[data-notify-files]');

            userEl.textContent = `${item.user_name} (${item.user_email})`;
            packageEl.textContent = item.package_name || 'Paket bilinmiyor';
            amountEl.textContent = `${item.amount} ${item.currency}`;
            providerEl.textContent = item.provider === 'bank_transfer' ? 'Havale / EFT' : item.provider;
            dateEl.textContent = item.created_at || '';
            statusEl.className = statusBadge(item.status);
            statusEl.textContent = item.status_label;
            noteEl.textContent = item.note || 'Ek not girilmemiş.';

            if (Array.isArray(item.attachments) && item.attachments.length) {
                filesEl.innerHTML = item.attachments.map((file, index) => `<a class="btn btn-sm btn-outline-light" target="_blank" rel="noopener" href="${file.url}">Dekont ${index + 1}</a>`).join(' ');
            } else {
                filesEl.textContent = 'Dekont yok';
            }

            container.appendChild(clone);
        });
    };

    const load = async () => {
        const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'list-payment-notifications', csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Bildirimler alınamadı');
        }
        notifications = data.data || [];
        render();
    };

    container.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button) {
            return;
        }
        const card = button.closest('.card');
        if (!card) {
            return;
        }
        const id = Number(card.dataset.id);
        if (!id) {
            return;
        }
        let status = 'approved';
        if (button.dataset.action === 'reject') {
            status = 'rejected';
        } else if (button.dataset.action === 'insufficient') {
            status = 'insufficient';
        }
        update(id, status);
    });

    const update = async (id, status) => {
        try {
            const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'update-payment-notification', notification_id: id, status, csrf_token: appConfig.csrfToken })
            });
            const data = await response.json();
            if (data.status !== 'success') {
                throw new Error(data.message || 'Güncelleme başarısız');
            }
            await load();
            Swal.fire({ icon: 'success', title: 'Güncellendi', text: data.message || 'Bildirim güncellendi.' });
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
        }
    };

    filterSelect?.addEventListener('change', render);
    document.addEventListener('realtime:event', (event) => {
        if (event.detail?.channel === 'transactions') {
            load().catch(console.error);
        }
    });
    load().catch(error => {
        console.error(error);
        container.innerHTML = '<div class="col-12 text-center text-danger py-4">Bildirimler yüklenemedi.</div>';
    });
})();
