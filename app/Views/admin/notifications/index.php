<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Bildirim Filtreleri</h2>
            </div>
            <div class="card-body">
                <form id="notification-filter-form">
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select">
                            <option value="">Tümü</option>
                            <option value="queued">Kuyrukta</option>
                            <option value="sending">Gönderiliyor</option>
                            <option value="sent">Gönderildi</option>
                            <option value="failed">Hatalı</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Başlık İçerir</label>
                        <input type="text" name="title" class="form-control" placeholder="Arama...">
                    </div>
                    <button type="button" class="btn btn-primary w-100" id="apply-notification-filter">Filtrele</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent">
                <h2 class="h5 mb-0">Bildirim Özeti</h2>
            </div>
            <div class="card-body">
                <p class="text-muted small">En güncel bildirim kayıtları aşağıdaki tabloda listelenir. Servis worker dönüşleri geldikçe durum alanı güncellenir.</p>
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="stat-tile">
                            <span class="label">Gönderildi</span>
                            <strong id="notif-sent">0</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-tile">
                            <span class="label">Kuyrukta</span>
                            <strong id="notif-queued">0</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-tile">
                            <span class="label">Hatalı</span>
                            <strong id="notif-failed">0</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-tile">
                            <span class="label">Toplam</span>
                            <strong id="notif-total">0</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<section class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Bildirim Kayıtları</h2>
        <button class="btn btn-outline-primary btn-sm" data-action="refresh" data-target="#admin-notifications-table">Yenile</button>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table class="table table-striped align-middle" id="admin-notifications-table" data-source="/admin/notifications/data" data-columns='["title","status","recipient_count","open_count","click_count","created_at"]'>
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Durum</th>
                        <th>Alıcı</th>
                        <th>Açılma</th>
                        <th>Tıklama</th>
                        <th>Oluşturma</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const table = document.getElementById('admin-notifications-table');
        const filterForm = document.getElementById('notification-filter-form');

        const counters = {
            sent: document.getElementById('notif-sent'),
            queued: document.getElementById('notif-queued'),
            failed: document.getElementById('notif-failed'),
            total: document.getElementById('notif-total')
        };

        if (table) {
            table.addEventListener('datatable.load', (event) => {
                const rows = event.detail || [];
                const summary = rows.reduce((acc, row) => {
                    acc.total += 1;
                    if (row.status && acc[row.status] !== undefined) {
                        acc[row.status] += 1;
                    }
                    return acc;
                }, { total: 0, sent: 0, queued: 0, failed: 0 });

                Object.entries(summary).forEach(([key, value]) => {
                    if (counters[key]) {
                        counters[key].textContent = value;
                    }
                });
            });
        }

        document.getElementById('apply-notification-filter').addEventListener('click', () => {
            if (!table) {
                return;
            }

            const params = new URLSearchParams(new FormData(filterForm)).toString();
            table.setAttribute('data-source', `/admin/notifications/data?${params}`);
            table.dispatchEvent(new Event('datatable.refresh'));
        });
    });
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
