(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const tableBody = document.querySelector('#transactionTable tbody');
    const searchInput = document.getElementById('transactionSearch');
    const statusSelect = document.getElementById('transactionStatus');

    if (!tableBody) {
        return;
    }

    let transactions = [];

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
            body: JSON.stringify({ action: 'list-transactions', csrf_token: appConfig.csrfToken })
        });
        if (!response.ok) {
            throw new Error('İşlemler alınamadı');
        }
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'İşlemler alınamadı');
        }
        transactions = data.data || [];
        renderTable();
    };

    const renderTable = () => {
        const query = (searchInput?.value || '').toLowerCase();
        const status = statusSelect?.value || '';
        const rows = transactions.filter(item => {
            const matchesStatus = !status || item.status === status;
            if (!matchesStatus) {
                return false;
            }
            if (!query) {
                return true;
            }
            const haystack = `${item.user_name} ${item.user_email} ${item.package_name} ${item.provider}`.toLowerCase();
            return haystack.includes(query);
        });

        if (!rows.length) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-white-50 py-4">Kayıt bulunamadı.</td></tr>';
            return;
        }

        tableBody.innerHTML = rows.map(row => `
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

    searchInput?.addEventListener('input', renderTable);
    statusSelect?.addEventListener('change', renderTable);

    fetchTransactions().catch(error => {
        console.error(error);
        tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">İşlemler yüklenemedi.</td></tr>';
    });
})();
