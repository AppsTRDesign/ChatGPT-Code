(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const chartCanvas = document.getElementById('shareTimeseries');
    const rangesContainer = document.getElementById('timeseriesRanges');
    const exportButtons = document.querySelectorAll('[data-export]');
    const locationList = document.getElementById('locationBreakdown');
    const deviceList = document.getElementById('deviceBreakdown');
    const tableBody = document.querySelector('#shareRecentTable tbody');

    if (!chartCanvas || !rangesContainer || !locationList || !deviceList || !tableBody) {
        return;
    }

    let stats = {
        timeseries: {},
        locations: [],
        devices: [],
        recent: [],
    };
    let currentRange = 'daily';
    let chartInstance = null;

    const loadStats = async () => {
        const response = await fetch(`${appConfig.baseUrl}/api/client.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'share-analytics', csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Analitik verileri alınamadı');
        }
        stats = data.data || stats;
        renderAll();
    };

    const renderAll = () => {
        renderChart();
        renderLocations();
        renderDevices();
        renderRecent();
    };

    const renderChart = () => {
        const rows = stats.timeseries[currentRange] || [];
        const labels = rows.map(row => row.label);
        const downloads = rows.map(row => Number(row.downloads || 0));
        if (!window.Chart) {
            return;
        }
        const data = {
            labels,
            datasets: [
                {
                    label: 'İndirme',
                    data: downloads,
                    borderColor: '#38bdf8',
                    backgroundColor: 'rgba(56, 189, 248, 0.3)',
                    tension: 0.3,
                    fill: true,
                }
            ]
        };
        const options = {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#f8f9ff' } }
            },
            scales: {
                y: { ticks: { color: '#f8f9ff' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                x: { ticks: { color: '#f8f9ff' }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        };
        if (chartInstance) {
            chartInstance.data = data;
            chartInstance.options = options;
            chartInstance.update();
        } else {
            chartInstance = new window.Chart(chartCanvas, { type: 'line', data, options });
        }
    };

    const renderLocations = () => {
        locationList.innerHTML = '';
        if (!stats.locations.length) {
            locationList.innerHTML = '<li class="list-group-item bg-transparent text-white-50">Veri yok</li>';
            return;
        }
        stats.locations.forEach(location => {
            const li = document.createElement('li');
            li.className = 'list-group-item bg-transparent d-flex justify-content-between text-white';
            li.innerHTML = `<span>${location.country} / ${location.city}</span><span class="badge bg-primary">${location.downloads}</span>`;
            locationList.appendChild(li);
        });
    };

    const renderDevices = () => {
        deviceList.innerHTML = '';
        if (!stats.devices.length) {
            deviceList.innerHTML = '<li class="list-group-item bg-transparent text-white-50">Veri yok</li>';
            return;
        }
        stats.devices.forEach(device => {
            const li = document.createElement('li');
            li.className = 'list-group-item bg-transparent text-white';
            li.innerHTML = `
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="fw-semibold">${device.device_type}</div>
                        <div class="text-white-50 small">${device.platform || '—'} • ${device.os || '—'} • ${device.browser || '—'}</div>
                    </div>
                    <span class="badge bg-secondary">${device.downloads}</span>
                </div>`;
            deviceList.appendChild(li);
        });
    };

    const renderRecent = () => {
        tableBody.innerHTML = '';
        if (!stats.recent.length) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-white-50 py-3">Son kayıt bulunamadı.</td></tr>';
            return;
        }
        tableBody.innerHTML = stats.recent.slice(0, 50).map(item => {
            const location = [item.country, item.city].filter(Boolean).join(' / ') || 'Bilinmiyor';
            const device = item.device_type || '—';
            return `
                <tr>
                    <td>${item.filename || '—'}</td>
                    <td>${item.ip_address || '—'}</td>
                    <td>${location}</td>
                    <td>${device}</td>
                    <td>${item.browser || '—'}</td>
                    <td class="text-white-50 small">${item.created_at}</td>
                </tr>`;
        }).join('');
    };

    const exportCsv = () => {
        const rows = stats.timeseries[currentRange] || [];
        if (!rows.length) {
            Swal.fire({ icon: 'info', title: 'Veri yok', text: 'Seçili aralık için veri bulunamadı.' });
            return;
        }
        const header = ['Aralık', 'İndirme'];
        const csvRows = [header.join(';')].concat(rows.map(row => `${row.label};${row.downloads}`));
        const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `paylasim-${currentRange}.csv`;
        link.click();
        URL.revokeObjectURL(url);
    };

    const exportPdf = () => {
        const rows = stats.timeseries[currentRange] || [];
        if (!rows.length) {
            Swal.fire({ icon: 'info', title: 'Veri yok', text: 'Seçili aralık için veri bulunamadı.' });
            return;
        }
        if (!window.jspdf || !window.jspdf.jsPDF) {
            Swal.fire({ icon: 'error', title: 'Eksik kütüphane', text: 'PDF için jsPDF yüklenemedi.' });
            return;
        }
        const doc = new window.jspdf.jsPDF('p', 'mm', 'a4');
        doc.setFontSize(16);
        doc.text(`Paylaşım raporu (${currentRange})`, 14, 20);
        doc.setFontSize(11);
        let y = 32;
        rows.forEach(row => {
            doc.text(`${row.label}: ${row.downloads} indirme`, 14, y);
            y += 8;
            if (y > 280) {
                doc.addPage();
                y = 20;
            }
        });
        doc.save(`paylasim-${currentRange}.pdf`);
    };

    rangesContainer.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-range]');
        if (!button) {
            return;
        }
        currentRange = button.dataset.range;
        rangesContainer.querySelectorAll('button[data-range]').forEach(btn => btn.classList.toggle('active', btn === button));
        renderChart();
    });

    exportButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (button.dataset.export === 'csv') {
                exportCsv();
            } else if (button.dataset.export === 'pdf') {
                exportPdf();
            }
        });
    });

    loadStats().catch(error => {
        console.error(error);
        Swal.fire({ icon: 'error', title: 'Analitik alınamadı', text: error.message });
    });
})();
