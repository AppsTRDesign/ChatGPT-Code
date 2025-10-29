(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const tableBody = document.querySelector('#transactionTable tbody');
    const searchInput = document.getElementById('transactionSearch');
    const statusSelect = document.getElementById('transactionStatus');
    const pageInfo = document.getElementById('transactionPageInfo');
    const prevButton = document.getElementById('transactionPrev');
    const nextButton = document.getElementById('transactionNext');

    if (!tableBody) {
        return;
    }

    let transactions = [];
    const state = {
        page: 1,
        perPage: 10,
        status: statusSelect?.value || '',
        search: '',
    };
    let pagination = { page: 1, per_page: state.perPage, total: 0 };

    const statusBadge = (status) => {
        switch (status) {
            case 'paid':
                return '<span class="badge bg-success">Ödendi</span>';
            case 'failed':
                return '<span class="badge bg-danger">Başarısız</span>';
            case 'cancelled':
                return '<span class="badge bg-secondary">İptal</span>';
            default:
                return '<span class="badge bg-warning text-dark">Beklemede</span>';
        }
    };

    const providerLabel = (provider) => {
        switch (provider) {
            case 'iyzico':
                return 'Iyzico';
            case 'stripe':
                return 'Stripe';
            default:
                return 'Havale/EFT';
        }
    };

    const formatAmount = (amount, currency) => {
        const formatter = new Intl.NumberFormat('tr-TR', { style: 'currency', currency: currency || 'TRY' });
        return formatter.format(Number(amount || 0));
    };

    const fetchTransactions = async () => {
        const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'list-transactions',
                page: state.page,
                per_page: state.perPage,
                status: state.status,
                search: state.search,
                csrf_token: appConfig.csrfToken,
            })
        });
        if (!response.ok) {
            throw new Error('İşlemler alınamadı');
        }
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'İşlemler alınamadı');
        }
        const payload = data.data || {};
        transactions = Array.isArray(payload.items) ? payload.items : [];
        const meta = payload.pagination || {};
        pagination = {
            page: meta.page ? Number(meta.page) : state.page,
            per_page: meta.per_page ? Number(meta.per_page) : state.perPage,
            total: meta.total ? Number(meta.total) : transactions.length,
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
            await fetchTransactions();
            return;
        }
        state.page = pagination.page;
        renderTable();
    };

    const renderTable = () => {
        if (!transactions.length) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-white-50 py-4">Kayıt bulunamadı.</td></tr>';
            if (pageInfo) {
                pageInfo.textContent = '0 kayıt';
            }
            if (prevButton) {
                prevButton.disabled = true;
            }
            if (nextButton) {
                nextButton.disabled = true;
            }
            return;
        }

        tableBody.innerHTML = transactions.map(row => `
            <tr data-id="${row.id}">
                <td>#${row.id}</td>
                <td>
                    <div class="fw-semibold text-white">${row.user_name}</div>
                    <div class="text-white-50 small">${row.user_email}</div>
                </td>
                <td>${row.package_name || '—'}</td>
                <td>${formatAmount(row.amount, row.currency)}</td>
                <td>${providerLabel(row.provider)}</td>
                <td>${statusBadge(row.status)}</td>
                <td class="text-white-50 small">${row.created_at}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-light" data-action="mark-paid">Onayla</button>
                        <button type="button" class="btn btn-outline-warning" data-action="mark-failed">Başarısız</button>
                        <button type="button" class="btn btn-outline-secondary" data-action="mark-cancel">İptal</button>
                    </div>
                </td>
            </tr>
        `).join('');

        if (pageInfo) {
            const total = pagination.total || 0;
            const start = (pagination.page - 1) * pagination.per_page + 1;
            const end = start + transactions.length - 1;
            pageInfo.textContent = `${start}-${end} / ${total}`;
        }

        if (prevButton) {
            prevButton.disabled = pagination.page <= 1;
        }
        if (nextButton) {
            const totalPages = pagination.per_page ? Math.ceil((pagination.total || 0) / pagination.per_page) : 1;
            nextButton.disabled = pagination.page >= totalPages;
        }
    };

    const updateTransaction = async (id, status) => {
        const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'update-transaction', transaction_id: id, status, csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Güncelleme başarısız');
        }
        await fetchTransactions();
        Swal.fire({ icon: 'success', title: 'Güncellendi', text: data.message || 'İşlem durumu güncellendi.' });
    };

    tableBody.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button) {
            return;
        }
        const row = button.closest('tr[data-id]');
        if (!row) {
            return;
        }
        const id = Number(row.dataset.id);
        if (!id) {
            return;
        }
        const action = button.dataset.action;
        let status = null;
        if (action === 'mark-paid') {
            status = 'paid';
        } else if (action === 'mark-failed') {
            status = 'failed';
        } else if (action === 'mark-cancel') {
            status = 'cancelled';
        }
        if (!status) {
            return;
        }
        updateTransaction(id, status).catch(error => {
            Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
        });
    });

    if (searchInput) {
        let searchTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                state.search = searchInput.value || '';
                state.page = 1;
                fetchTransactions().catch(error => {
                    console.error(error);
                    tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">İşlemler yüklenemedi.</td></tr>';
                });
            }, 300);
        });
    }

    statusSelect?.addEventListener('change', () => {
        state.status = statusSelect.value || '';
        state.page = 1;
        fetchTransactions().catch(error => {
            console.error(error);
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">İşlemler yüklenemedi.</td></tr>';
        });
    });

    prevButton?.addEventListener('click', () => {
        if (state.page > 1) {
            state.page -= 1;
            fetchTransactions().catch(error => {
                console.error(error);
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">İşlemler yüklenemedi.</td></tr>';
            });
        }
    });

    nextButton?.addEventListener('click', () => {
        const totalPages = pagination.per_page ? Math.ceil((pagination.total || 0) / pagination.per_page) : 1;
        if (state.page < totalPages) {
            state.page += 1;
            fetchTransactions().catch(error => {
                console.error(error);
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">İşlemler yüklenemedi.</td></tr>';
            });
        }
    });

    fetchTransactions().catch(error => {
        console.error(error);
        tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">İşlemler yüklenemedi.</td></tr>';
    });
})();
