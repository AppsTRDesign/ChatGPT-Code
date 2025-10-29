(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const summaryContainer = document.getElementById('clientSummary');
    const chartCanvas = document.getElementById('clientShareChart');
    const rangeContainer = document.getElementById('clientShareRanges');
    const locationList = document.getElementById('clientShareLocations');
    const deviceList = document.getElementById('clientShareDevices');
    const tableBody = document.querySelector('#clientShareRecent tbody');
    const recentSearchInput = document.getElementById('clientShareSearch');
    const recentPageInfo = document.getElementById('clientSharePageInfo');
    const recentPrevButton = document.getElementById('clientSharePrev');
    const recentNextButton = document.getElementById('clientShareNext');
    const exportButtons = document.querySelectorAll('[data-share-export]');
    let recentPage = 1;
    const recentPerPage = 8;

    const formatBytes = (bytes) => {
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '0 B';
        }
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        const value = bytes / Math.pow(1024, index);
        return `${value.toFixed(value >= 10 || index === 0 ? 0 : 2)} ${units[index]}`;
    };

    const loadSummary = async () => {
        if (!summaryContainer) {
            return;
        }
        try {
            const response = await fetch(`${appConfig.baseUrl}/api/client.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'dashboard', csrf_token: appConfig.csrfToken })
            });
            const data = await response.json();
            if (data.status !== 'success') {
                throw new Error(data.message || 'Özet verileri alınamadı');
            }
            const usage = data.data.usage || { total_files: 0, total_size: 0 };
            const pkg = data.data.package;
            const filesEl = summaryContainer.querySelector('[data-summary="total_files"]');
            const sizeEl = summaryContainer.querySelector('[data-summary="total_size"]');
            const packageEl = summaryContainer.querySelector('[data-summary="package_name"]');
            if (filesEl) {
                filesEl.textContent = usage.total_files ?? 0;
            }
            if (sizeEl) {
                sizeEl.textContent = `${(Number(usage.total_size || 0) / 1024 / 1024).toFixed(2)} MB`;
            }
            if (packageEl) {
                packageEl.textContent = pkg ? pkg.name : 'Seçilmedi';
            }
        } catch (error) {
            console.error(error);
        }
    };

    const shareSectionReady = chartCanvas && rangeContainer && locationList && deviceList && tableBody;
    let shareStats = { timeseries: {}, locations: [], devices: [], recent: { items: [], total: 0 } };
    let currentRange = 'daily';
    let chartInstance = null;

    const loadShareStats = async () => {
        if (!shareSectionReady) {
            return;
        }
        const response = await fetch(`${appConfig.baseUrl}/api/client.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'share-analytics', csrf_token: appConfig.csrfToken })
        });
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Paylaşım istatistikleri alınamadı');
        }
        shareStats = data.data || shareStats;
        recentPage = 1;
        renderShare();
    };

    const renderShare = () => {
        renderChart();
        renderLocations();
        renderDevices();
        renderRecent();
    };

    const renderChart = () => {
        if (!shareSectionReady || !window.Chart) {
            return;
        }
        const rows = shareStats.timeseries[currentRange] || [];
        const labels = rows.map((row) => row.label);
        const downloads = rows.map((row) => Number(row.downloads || 0));
        const bytes = rows.map((row) => Number(row.bytes || 0));
        const megabytes = bytes.map((value) => Number((value / (1024 * 1024)).toFixed(2)));

        const data = {
            labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'İndirme Sayısı',
                    data: downloads,
                    backgroundColor: 'rgba(147, 197, 253, 0.65)',
                    borderRadius: 8,
                    yAxisID: 'y'
                },
                {
                    type: 'bar',
                    label: 'Aktarılan Boyut (MB)',
                    data: megabytes,
                    backgroundColor: 'rgba(129, 230, 217, 0.65)',
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
                legend: { labels: { color: '#f8f9ff' } },
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
        if (!shareSectionReady) {
            return;
        }
        locationList.innerHTML = '';
        if (!shareStats.locations.length) {
            locationList.innerHTML = '<li class="list-group-item text-white-50">Veri yok</li>';
            return;
        }
        shareStats.locations.forEach((item) => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center text-white';
            li.innerHTML = `<span>${item.country} / ${item.city}</span><span class="badge bg-primary">${item.downloads}</span>`;
            locationList.appendChild(li);
        });
    };

    const renderDevices = () => {
        if (!shareSectionReady) {
            return;
        }
        deviceList.innerHTML = '';
        if (!shareStats.devices.length) {
            deviceList.innerHTML = '<li class="list-group-item text-white-50">Veri yok</li>';
            return;
        }
        shareStats.devices.forEach((device) => {
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
        const recent = shareStats.recent;
        if (Array.isArray(recent)) {
            return recent;
        }
        if (recent && Array.isArray(recent.items)) {
            return recent.items;
        }
        return [];
    };

    const renderRecent = () => {
        if (!shareSectionReady) {
            return;
        }
        const items = getRecentItems();
        const query = (recentSearchInput?.value || '').toLowerCase();
        const filtered = !query
            ? items
            : items.filter((item) => {
                const haystack = [
                    item.filename,
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
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-white-50 py-3">Son kayıt bulunamadı.</td></tr>';
        } else {
            tableBody.innerHTML = pageItems.map((item) => {
                const clicksInfo = `${item.clicks || 0}`;
                const uniqueInfo = `${item.unique_ips || 0}`;
                const location = item.top_location || 'Bilinmiyor';
                const browser = item.top_browser || '—';
                const language = item.top_language || 'Bilinmiyor';
                const device = item.top_device || '—';
                return `
                    <tr>
                        <td>
                            <div class="fw-semibold text-white">${item.filename || '—'}</div>
                            <div class="text-white-50 extra-small">${item.share_token ? 'Paylaşılan bağlantı' : 'Doğrudan erişim'}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">${clicksInfo}</div>
                            <div class="text-white-50 extra-small">${uniqueInfo} benzersiz IP</div>
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
        const rows = shareStats.timeseries[currentRange] || [];
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
        link.download = `paylasim-${currentRange}.csv`;
        link.click();
        URL.revokeObjectURL(url);
    };

    const exportPdf = () => {
        const rows = shareStats.timeseries[currentRange] || [];
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
        doc.save(`paylasim-${currentRange}.pdf`);
    };

    if (shareSectionReady) {
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
                if (button.dataset.shareExport === 'csv') {
                    exportCsv();
                } else if (button.dataset.shareExport === 'pdf') {
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
    }

    loadSummary().catch((error) => console.error(error));
    if (shareSectionReady) {
        loadShareStats().catch((error) => {
            console.error(error);
            Swal.fire({ icon: 'error', title: 'Hata', text: error.message });
        });
    }
})();
