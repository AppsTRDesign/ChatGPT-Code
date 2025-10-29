(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const chartCanvas = document.getElementById('adminShareChart');
    const rangeContainer = document.getElementById('adminShareRanges');
    const locationList = document.getElementById('adminShareLocations');
    const deviceList = document.getElementById('adminShareDevices');
    const tableBody = document.querySelector('#adminShareRecent tbody');
    const recentSearchInput = document.getElementById('adminShareSearch');
    const recentPageInfo = document.getElementById('adminSharePageInfo');
    const recentPrevButton = document.getElementById('adminSharePrev');
    const recentNextButton = document.getElementById('adminShareNext');
    const exportButtons = document.querySelectorAll('[data-admin-share-export]');

    if (!chartCanvas || !rangeContainer || !locationList || !deviceList || !tableBody) {
        return;
    }

    let analytics = { timeseries: {}, locations: [], devices: [], recent: { items: [], total: 0 } };
    let chartInstance = null;
    let currentRange = 'daily';
    let recentPage = 1;
    const recentPerPage = 10;

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
        recentPage = 1;
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

    const getRecentItems = () => {
        const recent = analytics.recent;
        if (Array.isArray(recent)) {
            return recent;
        }
        if (recent && Array.isArray(recent.items)) {
            return recent.items;
        }
        return [];
    };

    const renderRecent = () => {
        const items = getRecentItems();
        const query = (recentSearchInput?.value || '').toLowerCase();
        const filtered = !query
            ? items
            : items.filter((item) => {
                const haystack = [
                    item.filename,
                    item.user_name,
                    item.top_browser,
                    item.top_language,
                    item.top_device,
                    item.top_location,
                ].map((value) => (value || '').toString().toLowerCase()).join(' ');
                return haystack.includes(query);
            });

        const totalPages = filtered.length ? Math.ceil(filtered.length / recentPerPage) : 1;
        recentPage = Math.min(Math.max(1, recentPage), totalPages);
        const startIndex = filtered.length ? (recentPage - 1) * recentPerPage : 0;
        const pageItems = filtered.slice(startIndex, startIndex + recentPerPage);

        if (!pageItems.length) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-white-50 py-3">Son kayıt bulunamadı.</td></tr>';
        } else {
            tableBody.innerHTML = pageItems.map((item) => {
                const location = item.top_location || 'Bilinmiyor';
                const browser = item.top_browser || '—';
                const language = item.top_language || 'Bilinmiyor';
                const device = item.top_device || '—';
                const clicks = `${item.clicks || 0}`;
                const uniqueIps = `${item.unique_ips || 0}`;
                return `
                    <tr>
                        <td>${item.filename || '—'}</td>
                        <td>${item.user_name || '—'}</td>
                        <td>
                            <div class="fw-semibold">${clicks}</div>
                            <div class="text-white-50 extra-small">${uniqueIps} benzersiz IP</div>
                        </td>
                        <td>${browser}</td>
                        <td>${language}</td>
                        <td>${device}</td>
                        <td>${location}</td>
                        <td class="text-white-50 extra-small">${item.last_access || '—'}</td>
                    </tr>`;
            }).join('');
        }

        if (recentPageInfo) {
            if (!filtered.length) {
                recentPageInfo.textContent = '0 kayıt';
            } else {
                const start = startIndex + 1;
                const end = startIndex + pageItems.length;
                const totalText = filtered.length !== items.length ? `${filtered.length} (Toplam ${items.length})` : `${filtered.length}`;
                recentPageInfo.textContent = `${start}-${end} / ${totalText}`;
            }
        }
        if (recentPrevButton) {
            recentPrevButton.disabled = recentPage <= 1 || !filtered.length;
        }
        if (recentNextButton) {
            recentNextButton.disabled = recentPage >= totalPages || !filtered.length;
        }
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

    if (recentSearchInput) {
        let searchTimer;
        recentSearchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                recentPage = 1;
                renderRecent();
            }, 250);
        });
    }

    recentPrevButton?.addEventListener('click', () => {
        if (recentPage > 1) {
            recentPage -= 1;
            renderRecent();
        }
    });

    recentNextButton?.addEventListener('click', () => {
        recentPage += 1;
        renderRecent();
    });

    loadAnalytics().catch((error) => {
        console.error(error);
        Swal.fire({ icon: 'error', title: 'Analitik alınamadı', text: error.message });
    });
})();
