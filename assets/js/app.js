(function () {
    'use strict';

    const logoutButtons = document.querySelectorAll('[data-action="logout"]');
    logoutButtons.forEach((button) => {
        button.addEventListener('click', () => {
            axios.post('/logout')
                .then((response) => {
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata',
                        text: 'Çıkış işlemi başarısız oldu.'
                    });
                });
        });
    });

    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (!loginForm.checkValidity()) {
                loginForm.classList.add('was-validated');
                return;
            }

            const formData = new FormData(loginForm);
            const payload = new URLSearchParams(formData);
            axios.post('/login', payload)
                .then((response) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı',
                        text: 'Yönlendiriliyorsunuz...'
                    }).then(() => {
                        window.location.href = response.data.redirect;
                    });
                })
                .catch((error) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata',
                        text: error.response?.data?.message || 'Giriş başarısız'
                    });
                });
        });
    }

    document.querySelectorAll('[data-action="refresh"]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.getAttribute('data-target');
            if (!target) {
                return;
            }

            if (target.includes('chart')) {
                initializeCharts();
                return;
            }

            const tableElement = document.getElementById(target);
            if (tableElement) {
                const source = tableElement.getAttribute('data-source');
                if (source) {
                    axios.get(source)
                        .then((response) => {
                            populateTable(tableElement, response.data || []);
                        })
                        .catch(() => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Hata',
                                text: 'Veri alınamadı.'
                            });
                        });
                }
            }
        });
    });

    document.querySelectorAll('table[data-source]').forEach((table) => {
        const source = table.getAttribute('data-source');
        axios.get(source)
            .then((response) => {
                populateTable(table, response.data || []);
                new DataTable(table);
            })
            .catch(() => {
                // Taslak aşaması, hata yutuluyor
            });
    });

    initializeCharts();

    function populateTable(table, data) {
        const tbody = table.querySelector('tbody');
        if (!tbody) {
            return;
        }

        tbody.innerHTML = '';
        data.forEach((row) => {
            const tr = document.createElement('tr');
            Object.values(row).forEach((value) => {
                const td = document.createElement('td');
                td.textContent = value;
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
    }

    function initializeCharts() {
        const adminChartCanvas = document.getElementById('admin-activity-chart');
        if (adminChartCanvas && !adminChartCanvas.dataset.initialized) {
            new Chart(adminChartCanvas, {
                type: 'line',
                data: {
                    labels: ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cts', 'Paz'],
                    datasets: [{
                        label: 'Gönderilen Bildirim',
                        data: [12, 19, 8, 25, 16, 22, 30],
                        fill: true,
                        borderColor: '#00b8a9',
                        backgroundColor: 'rgba(0, 184, 169, 0.2)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            adminChartCanvas.dataset.initialized = 'true';
        }

        const clientChartCanvas = document.getElementById('client-performance-chart');
        if (clientChartCanvas && !clientChartCanvas.dataset.initialized) {
            new Chart(clientChartCanvas, {
                type: 'bar',
                data: {
                    labels: ['Hafta 1', 'Hafta 2', 'Hafta 3', 'Hafta 4'],
                    datasets: [{
                        label: 'Tıklama Oranı (%)',
                        data: [4.5, 5.2, 6.1, 7.4],
                        backgroundColor: '#0a4d68'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            clientChartCanvas.dataset.initialized = 'true';
        }
    }
})();
