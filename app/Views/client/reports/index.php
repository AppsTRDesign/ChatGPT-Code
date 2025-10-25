<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Günlük Etkileşim</h2>
                <button class="btn btn-outline-primary btn-sm" id="refresh-client-engagement">Yenile</button>
            </div>
            <div class="card-body">
                <canvas id="client-report-engagement" height="320"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">En Başarılı Şablonlar</h2>
                <button class="btn btn-outline-primary btn-sm" id="refresh-client-templates">Yenile</button>
            </div>
            <div class="card-body">
                <ul class="list-group" id="client-top-templates"></ul>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const engagementCanvas = document.getElementById('client-report-engagement');
        const topTemplatesList = document.getElementById('client-top-templates');
        let engagementChart;

        function loadEngagement() {
            fetch('/client/reports/engagement')
                .then((response) => response.json())
                .then((series) => {
                    const labels = series.map((item) => item.day);
                    const clicks = series.map((item) => Number(item.clicks || 0));
                    const opens = series.map((item) => Number(item.opens || 0));

                    if (engagementChart) {
                        engagementChart.destroy();
                    }

                    engagementChart = new Chart(engagementCanvas, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [
                                { label: 'Tıklama', data: clicks, backgroundColor: '#0a4d68' },
                                { label: 'Açılma', data: opens, backgroundColor: '#00b8a9' }
                            ]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                });
        }

        function loadTopTemplates() {
            fetch('/client/reports/templates')
                .then((response) => response.json())
                .then((items) => {
                    topTemplatesList.innerHTML = '';
                    if (!items.length) {
                        topTemplatesList.innerHTML = '<li class="list-group-item text-muted">Veri bulunamadı.</li>';
                        return;
                    }

                    items.forEach((item) => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item d-flex justify-content-between align-items-center';
                        li.innerHTML = `<span>${item.name}</span><span class="badge bg-primary">${item.usage_count} kullanım / ${item.clicks} tıklama</span>`;
                        topTemplatesList.appendChild(li);
                    });
                });
        }

        document.getElementById('refresh-client-engagement').addEventListener('click', loadEngagement);
        document.getElementById('refresh-client-templates').addEventListener('click', loadTopTemplates);

        loadEngagement();
        loadTopTemplates();
    });
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
