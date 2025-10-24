<?php
require __DIR__ . '/header.php';

use App\Helpers;

$csrf = Helpers::csrfToken();
?>
<h1 class="h3 mb-4">Canlı Ziyaretçiler</h1>
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card h-100 p-4">
            <h2 class="h5 mb-3">Aktif Trafik Özeti</h2>
            <ul class="list-unstyled mb-0" id="onlineSummary">
                <li class="text-white-50">Veri yükleniyor...</li>
            </ul>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100 p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <h2 class="h5 mb-0">Zaman Bazlı Yoğunluk</h2>
                <div class="d-flex align-items-center gap-2">
                    <label for="onlineRange" class="form-label mb-0 me-2">Aralık</label>
                    <select class="form-select form-select-sm w-auto" id="onlineRange">
                        <option value="daily">Son 24 Saat</option>
                        <option value="weekly">Son 7 Gün</option>
                        <option value="monthly">Son 30 Gün</option>
                        <option value="yearly">Son 12 Ay</option>
                    </select>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-light" data-online-export="pdf">PDF</button>
                        <button type="button" class="btn btn-outline-light" data-online-export="excel">Excel</button>
                    </div>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="onlineChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>
<div class="card p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <label for="onlineWindow" class="form-label mb-0">Aktiflik Penceresi</label>
            <select class="form-select form-select-sm w-auto" id="onlineWindow">
                <option value="5">Son 5 Dakika</option>
                <option value="10">Son 10 Dakika</option>
                <option value="30">Son 30 Dakika</option>
            </select>
        </div>
        <div class="text-white-50 small">Liste yalnızca seçilen zaman penceresindeki aktif oturumları içerir.</div>
    </div>
    <div class="table-responsive">
        <table
            id="onlineTable"
            class="table table-dark table-hover align-middle"
            data-toggle="table"
            data-url="/admin/data/online"
            data-search="true"
            data-pagination="true"
            data-page-list="[10,25,50]"
            data-side-pagination="server"
            data-response-handler="window.appHandlers.onlineHandler"
            data-query-params="window.appHandlers.onlineQuery"
            data-sort-name="last_seen"
            data-sort-order="desc"
            data-mobile-responsive="true"
            data-locale="tr-TR"
            data-csrf="<?= $csrf ?>"
        >
            <thead>
            <tr>
                <th data-field="username" data-sortable="true">Kullanıcı</th>
                <th data-field="platform" data-sortable="true">Platform</th>
                <th data-field="country" data-sortable="true">Ülke</th>
                <th data-field="city" data-sortable="true">Şehir</th>
                <th data-field="ip" data-sortable="true">IP</th>
                <th data-field="referer" data-formatter="window.appHandlers.refererFormatter">Kaynak</th>
                <th data-field="search" data-formatter="window.appHandlers.searchFormatter">Arama</th>
                <th data-field="last_url" data-formatter="window.appHandlers.urlFormatter">Son Sayfa</th>
                <th data-field="last_seen" data-sortable="true" data-formatter="window.appHandlers.dateTimeFormatter">Son Görülme</th>
            </tr>
            </thead>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
