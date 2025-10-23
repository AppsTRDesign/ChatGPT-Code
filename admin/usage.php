<?php
require __DIR__ . '/header.php';
?>
<h1 class="h3 mb-4 d-flex flex-wrap align-items-center gap-3">API Kullanım Raporları</h1>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card p-4 h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Kullanım Grafiği</h2>
                <div class="d-flex flex-wrap gap-2">
                    <select class="form-select form-select-sm" id="usageRange">
                        <option value="daily">Günlük</option>
                        <option value="weekly">Haftalık</option>
                        <option value="monthly">Aylık</option>
                        <option value="yearly">Yıllık</option>
                    </select>
                    <button class="btn btn-sm btn-outline-light" data-export="pdf">PDF İndir</button>
                    <button class="btn btn-sm btn-outline-light" data-export="excel">Excel İndir</button>
                </div>
            </div>
            <canvas id="usageChart" height="220"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-4 h-100">
            <h2 class="h5">Özet</h2>
            <ul class="list-unstyled mb-0" id="usageSummary"></ul>
        </div>
    </div>
</div>

<div class="card p-4 mt-4">
    <table
        id="usageTable"
        class="table table-dark table-hover"
        data-toggle="table"
        data-url="/admin/data/usage"
        data-search="true"
        data-pagination="true"
        data-page-list="[25, 50, 100]"
        data-unique-id="id"
        data-response-handler="window.appHandlers.usageResponseHandler"
        data-mobile-responsive="true"
        data-locale="tr-TR"
    >
        <thead>
            <tr>
                <th data-field="username" data-sortable="true">Kullanıcı</th>
                <th data-field="endpoint" data-sortable="true">Endpoint</th>
                <th data-field="status" data-formatter="window.appHandlers.usageStatusFormatter" data-sortable="true">Durum</th>
                <th data-field="note" data-formatter="window.appHandlers.noteFormatter">Not</th>
                <th data-field="created_at" data-sortable="true">Tarih</th>
            </tr>
        </thead>
    </table>
</div>
<?php require __DIR__ . '/footer.php'; ?>
