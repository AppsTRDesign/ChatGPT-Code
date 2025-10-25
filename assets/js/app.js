window.WebPush = (function () {
    const charts = new Map();

    function initFlash() {
        document.querySelectorAll('.flash-container').forEach((holder) => {
            const messages = JSON.parse(holder.dataset.flash || '[]');
            messages.forEach((message) => {
                Swal.fire({
                    toast: true,
                    icon: message.type,
                    title: message.message,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true,
                    background: '#e7f7fb',
                    color: '#023047'
                });
            });
            holder.remove();
        });
    }

    function initDataTables() {
        document.querySelectorAll('table.datatable').forEach((table) => {
            if ($(table).hasClass('dataTable')) {
                return;
            }
            $(table).DataTable({
                responsive: true,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json'
                }
            });
        });
    }

    function initDropzone() {
        if (typeof Dropzone === 'undefined') {
            return;
        }
        Dropzone.autoDiscover = false;
        document.querySelectorAll('[data-dropzone] .dropzone').forEach((element) => {
            const form = element.closest('form');
            const dz = new Dropzone(element, {
                url: form?.getAttribute('action') || '#',
                maxFiles: 1,
                clickable: true,
                addRemoveLinks: true,
                dictDefaultMessage: element.dataset.dropzoneMessage || 'Dosya yüklemek için bırakın',
            });
            if (form) {
                form.dropzone = dz;
            }
        });
    }

    function initCharts() {
        document.querySelectorAll('[data-chart] canvas').forEach((canvas) => {
            const dataset = canvas.closest('[data-chart]');
            const config = dataset ? dataset.dataset.chart : null;
            if (!config) {
                return;
            }
            const parsed = JSON.parse(config);
            if (charts.has(canvas.id)) {
                charts.get(canvas.id).destroy();
            }
            charts.set(canvas.id, new Chart(canvas, parsed));
        });
    }

    function registerRefreshButtons() {
        document.querySelectorAll('[data-refresh]').forEach((button) => {
            button.addEventListener('click', () => {
                const targetSelector = button.dataset.refresh;
                const target = document.querySelector(targetSelector);
                if (!target) {
                    return;
                }
                fetch(button.dataset.url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then((response) => response.json())
                    .then((payload) => {
                        target.dispatchEvent(new CustomEvent('refresh', { detail: payload }));
                    })
                    .catch((error) => console.error('Refresh error', error));
            });
        });
    }

    function initTemplatePreview() {
        document.querySelectorAll('[data-templates]').forEach((container) => {
            const templates = JSON.parse(container.dataset.templates || '[]');
            const select = container.querySelector('[data-template-selector]');
            const preview = container.querySelector('#templatePreview');
            if (!select || !preview) {
                return;
            }
            select.addEventListener('change', () => {
                const selected = templates.find((template) => String(template.id) === select.value);
                preview.innerHTML = selected ? selected.html : 'Bir şablon seçerek önizleyebilirsiniz.';
            });
        });
    }

    function initTokenActions() {
        document.querySelectorAll('[data-generate-token]').forEach((button) => {
            button.addEventListener('click', () => {
                const url = button.dataset.url || '/api/tokens';
                fetch(url, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then((response) => response.json())
                    .then((payload) => {
                        if (payload.token) {
                            Swal.fire('Başarılı', 'Yeni token oluşturuldu', 'success');
                            const table = document.querySelector('#tokenTable');
                            if (table) {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td class="text-break"><code>${payload.token.token}</code></td>
                                    <td><span class="badge-soft">Aktif</span></td>
                                    <td>${payload.token.created_at}</td>`;
                                table.prepend(row);
                            }
                        }
                    })
                    .catch(() => Swal.fire('Hata', 'Token oluşturulamadı', 'error'));
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initFlash();
        initDataTables();
        initDropzone();
        initCharts();
        registerRefreshButtons();
        initTemplatePreview();
        initTokenActions();
    });

    return {
        initFlash,
        initDataTables,
        initDropzone,
        initCharts,
        initTemplatePreview,
        initTokenActions,
    };
})();
