(function ($) {
    'use strict';

    const translations = (window.NoaSoftQR && window.NoaSoftQR.translations) || {};
    const locale = (window.NoaSoftQR && window.NoaSoftQR.locale) || 'en';
    const settings = (window.NoaSoftQR && window.NoaSoftQR.settings) || {};
    const primaryColor = settings.primary_color || '#2563eb';
    const accentColor = settings.accent_color || '#0ea5e9';

    function t(key) {
        if (translations[locale] && translations[locale][key]) {
            return translations[locale][key];
        }
        if (translations.en && translations.en[key]) {
            return translations.en[key];
        }
        return key;
    }

    function toast(message, type = 'success') {
        let container = $('.noasoft-toast-container');
        if (!container.length) {
            container = $('<div/>', { class: 'noasoft-toast-container' }).appendTo('body');
        }
        const toastEl = $('<div/>', {
            class: `toast align-items-center text-bg-${type} border-0 shadow-lg show mb-2`,
            role: 'alert'
        }).append(
            $('<div/>', { class: 'd-flex' }).append(
                $('<div/>', { class: 'toast-body', text: message }),
                $('<button/>', { type: 'button', class: 'btn-close btn-close-white me-2 m-auto', 'data-bs-dismiss': 'toast', 'aria-label': 'Close' })
            )
        );
        container.append(toastEl);
        setTimeout(() => {
            toastEl.fadeOut(300, () => toastEl.remove());
        }, 6000);
    }

    function renderDynamicFields(wrapper, type) {
        const fields = {
            url: [
                { name: 'url', label: t('field_url'), type: 'url' }
            ],
            text: [
                { name: 'text_content', label: t('field_text'), type: 'textarea' }
            ],
            email: [
                { name: 'email_address', label: t('field_email'), type: 'email' }
            ],
            phone: [
                { name: 'phone_number', label: t('field_phone'), type: 'tel' }
            ],
            sms: [
                { name: 'sms_number', label: t('field_sms_number'), type: 'tel' },
                { name: 'sms_message', label: t('field_sms_message'), type: 'textarea' }
            ],
            whatsapp: [
                { name: 'whatsapp_number', label: t('field_whatsapp_number'), type: 'tel' },
                { name: 'whatsapp_message', label: t('field_whatsapp_message'), type: 'textarea' }
            ],
            wifi: [
                { name: 'wifi_ssid', label: t('field_wifi_ssid'), type: 'text' },
                { name: 'wifi_password', label: t('field_wifi_password'), type: 'text' },
                { name: 'wifi_encryption', label: t('field_wifi_encryption'), type: 'text' }
            ],
            location: [
                { name: 'location_lat', label: t('field_location_lat'), type: 'text' },
                { name: 'location_lng', label: t('field_location_lng'), type: 'text' },
                { name: 'location_label', label: t('field_location_label'), type: 'text' }
            ],
            event: [
                { name: 'event_title', label: t('field_event_title'), type: 'text' },
                { name: 'event_location', label: t('field_event_location'), type: 'text' },
                { name: 'event_description', label: t('field_event_description'), type: 'textarea' },
                { name: 'event_start', label: t('field_event_start'), type: 'datetime-local' },
                { name: 'event_end', label: t('field_event_end'), type: 'datetime-local' }
            ]
        };
        wrapper.empty();
        if (!fields[type]) {
            return;
        }
        fields[type].forEach(field => {
            const col = $('<div/>', { class: 'col-md-6' });
            const label = $('<label/>', { class: 'form-label fw-semibold', text: field.label });
            let input;
            if (field.type === 'textarea') {
                input = $('<textarea/>', { class: 'form-control', name: field.name, rows: 4 });
            } else {
                input = $('<input/>', { class: 'form-control', name: field.name, type: field.type });
            }
            col.append(label, input);
            wrapper.append(col);
        });
    }

    function hexToRgba(hex, alpha) {
        let sanitized = hex.replace('#', '');
        if (sanitized.length === 3) {
            sanitized = sanitized.split('').map((c) => c + c).join('');
        }
        const bigint = parseInt(sanitized, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    function handleDropzone() {
        const dropzoneEl = document.getElementById('noasoft-logo-dropzone');
        if (!dropzoneEl || !window.Dropzone) {
            return;
        }
        Dropzone.autoDiscover = false;
        const dz = new Dropzone(dropzoneEl, {
            url: NoaSoftQR.ajax_url,
            autoProcessQueue: false,
            clickable: true,
            addRemoveLinks: false
        });
        $(dropzoneEl).css({
            borderColor: primaryColor,
            backgroundColor: hexToRgba(accentColor, 0.12)
        });
        dz.on('addedfile', function (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#noasoft-logo-url').val(e.target.result);
                $('#noasoft-logo-preview').text(t('field_logo_saved'));
            };
            reader.readAsDataURL(file);
        });
    }

    function submitForm($form, resultContainer) {
        const formData = new FormData($form[0]);
        formData.append('action', 'noasoft_qr_create');
        formData.append('nonce', NoaSoftQR.nonce);

        $.ajax({
            url: NoaSoftQR.ajax_url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success(response) {
                if (!response.success) {
                    toast(response.data.message || t('error_unknown'), 'danger');
                    return;
                }
                toast(response.data.message, 'success');
                renderResults(resultContainer, response.data);
                if (response.data.remaining !== undefined) {
                    $('.badge.bg-dark').text(`${t('remaining_label')}: ${response.data.remaining}`);
                }
            },
            error() {
                toast(t('error_request'), 'danger');
            }
        });
    }

    function renderResults(container, data) {
        if (!container.length) {
            return;
        }
        container.empty();
        const card = $('<div/>', { class: 'card border-0 shadow-sm rounded-4' });
        const body = $('<div/>', { class: 'card-body' });
        body.append($('<h5/>', { class: 'card-title mb-3', text: t('result_title') }));
        if (data.downloads) {
            const list = $('<div/>', { class: 'row g-3' });
            Object.values(data.downloads).forEach(item => {
                list.append(
                    $('<div/>', { class: 'col-md-4' }).append(
                        $('<a/>', {
                            href: item.url || item,
                            class: 'btn btn-outline-primary w-100',
                            text: (item.format || '').toUpperCase() || t('result_download'),
                            target: '_blank',
                            rel: 'noopener noreferrer'
                        })
                    )
                );
            });
            body.append(list);
        }
        if (data.embed_url) {
            body.append(
                $('<div/>', { class: 'mt-3' }).append(
                    $('<code/>', { class: 'd-block bg-light p-3 rounded-3 small', text: `<img src="${data.embed_url}" alt="QR">` })
                )
            );
        }
        card.append(body);
        container.append(card);
    }

    function initChart() {
        const ctx = document.getElementById('noasoft-qr-stats');
        if (!ctx || !window.Chart) {
            return;
        }
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: t('chart_label'),
                    data: [],
                    backgroundColor: '#2563eb'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: false },
                    y: { beginAtZero: true }
                }
            }
        });

        function updateChart(scope, dataset) {
            chart.data.labels = Object.keys(dataset);
            chart.data.datasets[0].data = Object.values(dataset);
            chart.update();
        }

        function fetchStats(scope) {
            $.post(NoaSoftQR.ajax_url, {
                action: 'noasoft_qr_stats',
                nonce: NoaSoftQR.nonce,
                scope
            }, function (response) {
                if (response.success) {
                    updateChart(scope, response.data[scope]);
                }
            });
        }

        $('#noasoft-chart-tabs button').on('click', function () {
            $('#noasoft-chart-tabs button').removeClass('active');
            $(this).addClass('active');
            const scope = $(this).data('target');
            fetchStats(scope);
        });

        fetchStats('daily');
    }

    function bindCopyButtons() {
        $('[data-copy]').on('click', function () {
            navigator.clipboard.writeText($(this).data('copy'));
            toast(t('copied'), 'success');
        });
    }

    $(document).ready(function () {
        $('.noasoft-qr-admin').each(function () {
            this.style.setProperty('--noasoft-primary', primaryColor);
            this.style.setProperty('--noasoft-accent', accentColor);
        });
        const form = $('#noasoft-qr-create-form');
        if (form.length) {
            renderDynamicFields($('#noasoft-dynamic-fields'), $('#noasoft-type').val());
            $('#noasoft-type').on('change', function () {
                renderDynamicFields($('#noasoft-dynamic-fields'), $(this).val());
            });
            handleDropzone();
            form.on('submit', function (e) {
                e.preventDefault();
                submitForm(form, $('#noasoft-qr-results'));
            });
        }
        initChart();
        bindCopyButtons();
    });
})(jQuery);
