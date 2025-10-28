(function () {
    'use strict';

    const appConfig = window.APP_CONFIG || {};
    const statsContainer = document.getElementById('adminStats');
    const chartCanvas = document.getElementById('adminUsageChart');
    if (!statsContainer || !chartCanvas) {
        return;
    }

    const rangeButtons = Array.from(document.querySelectorAll('[data-range]'));
    const exportButtons = Array.from(document.querySelectorAll('[data-export]'));
    let currentRange = 'daily';
    let usageSeries = {};
    let chartInstance = null;

    const formatBytes = (bytes) => {
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '0 B';
        }
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        const value = bytes / Math.pow(1024, index);
        return `${value.toFixed(value >= 10 || index === 0 ? 0 : 2)} ${units[index]}`;
    };

    const fetchJson = async (body) => {
        const response = await fetch(`${appConfig.baseUrl}/api/admin.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(Object.assign({}, body, { csrf_token: appConfig.csrfToken }))
        });
        if (!response.ok) {
            throw new Error('Sunucu isteği başarısız oldu');
        }
        const data = await response.json();
        if (data.status !== 'success') {
            throw new Error(data.message || 'Beklenmeyen bir hata oluştu');
        }
        return data.data;
    };

    const loadStats = async () => {
        try {
            const data = await fetchJson({ action: 'stats' });
            Object.entries(data).forEach(([key, value]) => {
                const el = statsContainer.querySelector(`[data-stat="${key}"]`);
                if (el) {
                    el.textContent = value;
                }
            });
        } catch (error) {
            console.error('İstatistikler alınamadı:', error);
        }
    };

    const loadUsageSeries = async () => {
        try {
            const data = await fetchJson({ action: 'usage-timeseries', ranges: ['daily', 'weekly', 'monthly', 'yearly'] });
            usageSeries = data || {};
            renderChart(currentRange);
        } catch (error) {
            console.error('Grafik verileri alınamadı:', error);
        }
    };

    const buildDataset = (range) => {
        const rows = usageSeries[range] || [];
        const labels = rows.map(row => row.label);
        const uploads = rows.map(row => Number(row.uploads || 0));
        const bytes = rows.map(row => Number(row.bytes || 0));
        return { rows, labels, uploads, bytes };
    };

    const renderChart = (range) => {
        const { labels, uploads, bytes } = buildDataset(range);
        const chartData = {
            labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Yükleme Sayısı',
                    data: uploads,
                    backgroundColor: 'rgba(99, 102, 241, 0.6)',
                    borderRadius: 10,
                },
                {
                    type: 'line',
                    label: 'Toplam Boyut (MB)',
                    data: bytes.map(value => Number((value / (1024 * 1024)).toFixed(2))),
                    borderColor: '#facc15',
                    backgroundColor: 'rgba(250, 204, 21, 0.3)',
                    tension: 0.3,
                    yAxisID: 'y1',
                }
            ]
        };

        const chartOptions = {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#f8f9ff' },
                    grid: { color: 'rgba(255, 255, 255, 0.08)' }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    ticks: { color: '#f8f9ff' },
                    grid: { drawOnChartArea: false }
                },
                x: {
                    ticks: { color: '#f8f9ff' },
                    grid: { color: 'rgba(255, 255, 255, 0.08)' }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#f8f9ff'
                    }
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            if (context.datasetIndex === 1) {
                                return `${context.dataset.label}: ${formatBytes(bytes[context.dataIndex] || 0)}`;
                            }
                            return `${context.dataset.label}: ${context.parsed.y}`;
                        }
                    }
                }
            }
        };

        if (chartInstance) {
            chartInstance.data = chartData;
            chartInstance.options = chartOptions;
            chartInstance.update();
        } else if (window.Chart) {
            chartInstance = new window.Chart(chartCanvas, {
                type: 'bar',
                data: chartData,
                options: chartOptions
            });
        }
    };

    const setActiveRangeButton = (range) => {
        rangeButtons.forEach(button => {
            if (button.dataset.range === range) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    };

    const exportCsv = (range) => {
        const { rows } = buildDataset(range);
        if (!rows.length) {
            Swal.fire({ icon: 'info', title: 'Veri yok', text: 'Seçili aralık için rapor bulunmuyor.' });
            return;
        }
        const header = ['Aralık', 'Yükleme Sayısı', 'Toplam Boyut (byte)'];
        const csvLines = [header.join(';')].concat(rows.map(row => [row.label, row.uploads, row.bytes].join(';')));
        const blob = new Blob([csvLines.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `admin-rapor-${range}.csv`;
        link.click();
        URL.revokeObjectURL(url);
    };

    const exportPdf = async (range) => {
        const { rows } = buildDataset(range);
        if (!rows.length) {
            Swal.fire({ icon: 'info', title: 'Veri yok', text: 'Seçili aralık için rapor bulunmuyor.' });
            return;
        }
        if (!window.jspdf || !window.jspdf.jsPDF) {
            Swal.fire({ icon: 'error', title: 'Eksik kütüphane', text: 'PDF çıktısı için jsPDF yüklenemedi.' });
            return;
        }
        const doc = new window.jspdf.jsPDF();
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(16);
        doc.text(`Depo raporu (${range})`, 14, 20);
        doc.setFontSize(11);
        let y = 32;
        rows.forEach((row) => {
            doc.text(`${row.label}: ${row.uploads} yükleme, ${formatBytes(Number(row.bytes || 0))}`, 14, y);
            y += 8;
            if (y > 280) {
                doc.addPage();
                y = 20;
            }
        });
        doc.save(`admin-rapor-${range}.pdf`);
    };

    rangeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const { range } = button.dataset;
            currentRange = range;
            setActiveRangeButton(range);
            renderChart(range);
        });
    });

    exportButtons.forEach(button => {
        button.addEventListener('click', () => {
            const format = button.dataset.export;
            if (format === 'csv') {
                exportCsv(currentRange);
            } else if (format === 'pdf') {
                exportPdf(currentRange);
            }
        });
    });

    setActiveRangeButton(currentRange);
    loadStats();
    loadUsageSeries();
})();
