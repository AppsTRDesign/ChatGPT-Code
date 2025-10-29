(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const container = document.getElementById('notificationList');
    const filterSelect = document.getElementById('notificationFilter');
    const searchInput = document.getElementById('notificationSearch');
    const pageInfo = document.getElementById('notificationPageInfo');
    const prevButton = document.getElementById('notificationPrev');
    const nextButton = document.getElementById('notificationNext');
    const template = document.getElementById('notificationTemplate');

    if (!container || !template) {
        return;
    }

    let notifications = [];
    const state = {
        page: 1,
        perPage: 6,
        status: filterSelect?.value || '',
        search: '',
    };
    let pagination = { page: 1, per_page: state.perPage, total: 0 };

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
        container.innerHTML = '';
        if (!notifications.length) {
            if (pageInfo) {
                pageInfo.textContent = '0 kayıt';
            }
            if (prevButton) {
                prevButton.disabled = true;
            }
            if (nextButton) {
                nextButton.disabled = true;
            }
            container.innerHTML = '<div class="col-12 text-center text-white-50 py-4">Bildirim bulunamadı.</div>';
            return;
        }
        notifications.forEach(item => {
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

        if (pageInfo) {
            const total = pagination.total || 0;
            if (!total) {
                pageInfo.textContent = '0 kayıt';
            } else {
                const start = (pagination.page - 1) * pagination.per_page + 1;
                const end = start + notifications.length - 1;
                pageInfo.textContent = `${start}-${end} / ${total}`;
            }
        }

        if (prevButton) {
            prevButton.disabled = pagination.page <= 1;
        }
        if (nextButton) {
            const totalPages = pagination.per_page ? Math.ceil((pagination.total || 0) / pagination.per_page) : 1;
            nextButton.disabled = pagination.page >= totalPages;
        }
    };

    const load = async () => {
        const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'list-payment-notifications',
                page: state.page,
                per_page: state.perPage,
                status: state.status,
                search: state.search,
                csrf_token: appConfig.csrfToken,
            })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Bildirimler alınamadı');
        }
        const payload = data.data || {};
        notifications = Array.isArray(payload.items) ? payload.items : [];
        const meta = payload.pagination || {};
        pagination = {
            page: meta.page ? Number(meta.page) : state.page,
            per_page: meta.per_page ? Number(meta.per_page) : state.perPage,
            total: meta.total ? Number(meta.total) : notifications.length,
        };
        state.perPage = pagination.per_page;
        if (!pagination.total) {
            state.page = 1;
            pagination.page = 1;
        }
        const totalPages = pagination.per_page ? Math.ceil((pagination.total || 0) / pagination.per_page) : 1;
        if (pagination.total && pagination.page > totalPages) {
            state.page = totalPages;
            pagination.page = totalPages;
            await load();
            return;
        }
        state.page = pagination.page;
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

    filterSelect?.addEventListener('change', () => {
        state.status = filterSelect.value || '';
        state.page = 1;
        load().catch(error => {
            console.error(error);
            container.innerHTML = '<div class="col-12 text-center text-danger py-4">Bildirimler yüklenemedi.</div>';
        });
    });

    if (searchInput) {
        let searchTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                state.search = searchInput.value || '';
                state.page = 1;
                load().catch(error => {
                    console.error(error);
                    container.innerHTML = '<div class="col-12 text-center text-danger py-4">Bildirimler yüklenemedi.</div>';
                });
            }, 300);
        });
    }

    prevButton?.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            load().catch(error => {
                console.error(error);
                container.innerHTML = '<div class="col-12 text-center text-danger py-4">Bildirimler yüklenemedi.</div>';
            });
        }
    });

    nextButton?.addEventListener('click', () => {
        const totalPages = pagination.per_page ? Math.ceil((pagination.total || 0) / pagination.per_page) : 1;
        if (state.page < totalPages) {
            state.page += 1;
            load().catch(error => {
                console.error(error);
                container.innerHTML = '<div class="col-12 text-center text-danger py-4">Bildirimler yüklenemedi.</div>';
            });
        }
    });

    load().catch(error => {
        console.error(error);
        container.innerHTML = '<div class="col-12 text-center text-danger py-4">Bildirimler yüklenemedi.</div>';
    });
})();
