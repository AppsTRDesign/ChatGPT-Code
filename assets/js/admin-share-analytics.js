(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const chartCanvas = document.getElementById('adminShareChart');
    const rangeContainer = document.getElementById('adminShareRanges');
    const locationList = document.getElementById('adminShareLocations');
    const deviceList = document.getElementById('adminShareDevices');
    const tableBody = document.querySelector('#adminShareRecent tbody');
    const exportButtons = document.querySelectorAll('[data-admin-share-export]');

    if (!chartCanvas || !rangeContainer || !locationList || !deviceList || !tableBody) {
        return;
    }

    let analytics = { timeseries: {}, locations: [], devices: [], recent: [] };
    let chartInstance = null;
    let currentRange = 'daily';

    const formatBytes = (bytes) => {
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '0 B';
        }
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        const value = bytes / Math.pow(1024, index);
        return `${value.toFixed(value >= 10 || index === 0 ? 0 : 2)} ${units[index]}`;
    };

    const loadAnalytics = async () => {
        const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'share-analytics', csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Analitik verileri alınamadı');
        }
        analytics = data.data || analytics;
        renderAll();
    };

    const renderAll = () => {
        renderChart();
        renderLocations();
        renderDevices();
        renderRecent();
    };

    const renderChart = () => {
        const rows = analytics.timeseries[currentRange] || [];
        const labels = rows.map((row) => row.label);
        const downloads = rows.map((row) => Number(row.downloads || 0));
        const bytes = rows.map((row) => Number(row.bytes || 0));
        const megabytes = bytes.map((value) => Number((value / (1024 * 1024)).toFixed(2)));

        if (!window.Chart) {
            return;
        }

        const data = {
            labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'İndirme Sayısı',
                    data: downloads,
                    backgroundColor: 'rgba(56, 189, 248, 0.65)',
                    borderRadius: 8,
                    yAxisID: 'y'
                },
                {
                    type: 'bar',
                    label: 'Aktarılan Boyut (MB)',
                    data: megabytes,
                    backgroundColor: 'rgba(250, 204, 21, 0.65)',
                    borderRadius: 8,
                    yAxisID: 'y1'
                }
            ]
        };

        const options = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#f8f9ff' },
                    grid: { color: 'rgba(255, 255, 255, 0.12)' }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    ticks: { color: '#f8f9ff' },
                    grid: { drawOnChartArea: false }
                },
                x: {
                    ticks: { color: '#f8f9ff' },
                    grid: { color: 'rgba(255, 255, 255, 0.12)' }
                }
            },
            plugins: {
                legend: {
                    labels: { color: '#f8f9ff' }
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            if (context.dataset.yAxisID === 'y1') {
                                return `${context.dataset.label}: ${formatBytes(bytes[context.dataIndex] || 0)}`;
                            }
                            return `${context.dataset.label}: ${context.parsed.y}`;
                        }
                    }
                }
            }
        };

        if (chartInstance) {
            chartInstance.data = data;
            chartInstance.options = options;
            chartInstance.update();
        } else {
            chartInstance = new window.Chart(chartCanvas, { type: 'bar', data, options });
        }
    };

    const renderLocations = () => {
        locationList.innerHTML = '';
        if (!analytics.locations.length) {
            locationList.innerHTML = '<li class="list-group-item text-white-50">Veri yok</li>';
            return;
        }
        analytics.locations.forEach((item) => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center text-white';
            li.innerHTML = `<span>${item.country} / ${item.city}</span><span class="badge bg-primary">${item.downloads}</span>`;
            locationList.appendChild(li);
        });
    };

    const renderDevices = () => {
        deviceList.innerHTML = '';
        if (!analytics.devices.length) {
            deviceList.innerHTML = '<li class="list-group-item text-white-50">Veri yok</li>';
            return;
        }
        analytics.devices.forEach((device) => {
            const li = document.createElement('li');
            li.className = 'list-group-item text-white';
            li.innerHTML = `
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="fw-semibold">${device.device_type}</div>
                        <div class="text-white-50 extra-small">${device.platform || '—'} • ${device.os || '—'} • ${device.browser || '—'}</div>
                    </div>
                    <span class="badge bg-secondary">${device.downloads}</span>
                </div>`;
            deviceList.appendChild(li);
        });
    };

    const renderRecent = () => {
        tableBody.innerHTML = '';
        if (!analytics.recent.length) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-white-50 py-3">Son kayıt bulunamadı.</td></tr>';
            return;
        }
        tableBody.innerHTML = analytics.recent.map((item) => {
            const location = [item.country, item.city].filter(Boolean).join(' / ') || 'Bilinmiyor';
            const device = item.device_type || '—';
            return `
                <tr>
                    <td>${item.filename || '—'}</td>
                    <td>${item.user_name || '—'}</td>
                    <td>${item.ip_address || '—'}</td>
                    <td>${location}</td>
                    <td>${device}</td>
                    <td>${item.browser || '—'}</td>
                    <td class="text-white-50 extra-small">${item.created_at}</td>
                </tr>`;
        }).join('');
    };

    const exportCsv = () => {
        const rows = analytics.timeseries[currentRange] || [];
        if (!rows.length) {
            Swal.fire({ icon: 'info', title: 'Veri yok', text: 'Seçili aralık için kayıt bulunamadı.' });
            return;
        }
        const header = ['Aralık', 'İndirme Sayısı', 'Toplam Boyut (byte)'];
        const csvRows = [header.join(';')].concat(rows.map((row) => `${row.label};${row.downloads};${row.bytes}`));
        const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `admin-paylasim-${currentRange}.csv`;
        link.click();
        URL.revokeObjectURL(url);
    };

    const exportPdf = () => {
        const rows = analytics.timeseries[currentRange] || [];
        if (!rows.length) {
            Swal.fire({ icon: 'info', title: 'Veri yok', text: 'Seçili aralık için kayıt bulunamadı.' });
            return;
        }
        if (!window.jspdf || !window.jspdf.jsPDF) {
            Swal.fire({ icon: 'error', title: 'Eksik kütüphane', text: 'PDF çıktısı için jsPDF yüklenemedi.' });
            return;
        }
        const doc = new window.jspdf.jsPDF('p', 'mm', 'a4');
        doc.setFontSize(16);
        doc.text(`Paylaşım raporu (${currentRange})`, 14, 20);
        doc.setFontSize(11);
        let y = 32;
        rows.forEach((row) => {
            doc.text(`${row.label}: ${row.downloads} indirme, ${formatBytes(Number(row.bytes || 0))}`, 14, y);
            y += 8;
            if (y > 280) {
                doc.addPage();
                y = 20;
            }
        });
        doc.save(`admin-paylasim-${currentRange}.pdf`);
    };

    rangeContainer.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-range]');
        if (!button) {
            return;
        }
        currentRange = button.dataset.range;
        rangeContainer.querySelectorAll('button[data-range]').forEach((btn) => btn.classList.toggle('active', btn === button));
        renderChart();
    });

    exportButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (button.dataset.adminShareExport === 'csv') {
                exportCsv();
            } else if (button.dataset.adminShareExport === 'pdf') {
                exportPdf();
            }
        });
    });

    loadAnalytics().catch((error) => {
        console.error(error);
        Swal.fire({ icon: 'error', title: 'Analitik alınamadı', text: error.message });
    });
})();
