<?php include __DIR__ . '/../layout/header.php'; ?>
<section class="mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="h5 mb-1">Abonelikler</h1>
                <p class="text-muted mb-0">GeoIP2 ve DeviceDetector verileriyle token detaylarını inceleyin ve yönetim aksiyonları alın.</p>
            </div>
            <button class="btn btn-outline-primary mt-3 mt-sm-0" data-action="refresh" data-target="#client-subscriptions-table">Yenile</button>
        </div>
    </div>
</section>
<section>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle" id="client-subscriptions-table" data-source="/client/subscriptions/data" data-columns='["token","status","language","country","city","platform","browser","device_type","site","created_at","__actions"]'>
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Durum</th>
                        <th>Dil</th>
                        <th>Ülke</th>
                        <th>Şehir</th>
                        <th>Platform</th>
                        <th>Tarayıcı</th>
                        <th>Cihaz</th>
                        <th>Site</th>
                        <th>Kayıt</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const table = document.getElementById('client-subscriptions-table');
        if (!table) {
            return;
        }

        table.addEventListener('datatable.load', (event) => {
            const rows = event.detail || [];
            const tableRows = table.querySelectorAll('tbody tr');

            tableRows.forEach((tr, index) => {
                const data = rows[index];
                if (!data) {
                    return;
                }

                let actionCell = tr.querySelector('td[data-actions]');
                if (!actionCell) {
                    actionCell = document.createElement('td');
                    actionCell.setAttribute('data-actions', 'true');
                    tr.appendChild(actionCell);
                }

                actionCell.innerHTML = '';

                const button = document.createElement('button');
                const isActive = data.status === 'active';
                button.className = 'btn btn-sm ' + (isActive ? 'btn-outline-danger' : 'btn-outline-success');
                button.textContent = isActive ? 'Pasif Et' : 'Aktifleştir';
                button.addEventListener('click', () => {
                    fetch('/client/subscriptions/update-status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ id: data.id, status: isActive ? 'revoked' : 'active' })
                    })
                        .then((response) => response.json())
                        .then((payload) => {
                            if (payload.status === 'success') {
                                table.dispatchEvent(new Event('datatable.refresh'));
                            } else {
                                Swal.fire({ icon: 'error', title: 'Hata', text: payload.message || 'İşlem başarısız oldu.' });
                            }
                        });
                });

                actionCell.appendChild(button);
            });
        });
    });
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
