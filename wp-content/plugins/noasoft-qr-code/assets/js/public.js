(function ($) {
    'use strict';

    const translations = (window.NoaSoftQRPublic && window.NoaSoftQRPublic.translations) || {};
    const locale = (window.NoaSoftQRPublic && window.NoaSoftQRPublic.locale) || 'en';
    const settings = (window.NoaSoftQRPublic && window.NoaSoftQRPublic.settings) || {};
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
            container = $('<div/>', { class: 'noasoft-toast-container position-fixed top-0 end-0 p-3', css: { zIndex: 99999 } }).appendTo('body');
        }
        const toastEl = $('<div/>', {
            class: `toast align-items-center text-bg-${type} border-0 shadow show mb-2`,
            role: 'alert'
        }).append(
            $('<div/>', { class: 'd-flex' }).append(
                $('<div/>', { class: 'toast-body', text: message }),
                $('<button/>', { type: 'button', class: 'btn-close btn-close-white me-2 m-auto', 'data-bs-dismiss': 'toast', 'aria-label': 'Close' })
            )
        );
        container.append(toastEl);
        setTimeout(() => toastEl.fadeOut(300, () => toastEl.remove()), 6000);
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

    function renderDynamic($wrapper, type) {
        const map = {
            url: [ { name: 'url', label: t('field_url'), type: 'url' } ],
            text: [ { name: 'text_content', label: t('field_text'), type: 'textarea' } ],
            email: [ { name: 'email_address', label: t('field_email'), type: 'email' } ],
            phone: [ { name: 'phone_number', label: t('field_phone'), type: 'tel' } ],
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
        $wrapper.empty();
        if (!map[type]) { return; }
        map[type].forEach(field => {
            const col = $('<div/>', { class: 'col-md-6' });
            const label = $('<label/>', { class: 'form-label fw-semibold', text: field.label });
            let input;
            if (field.type === 'textarea') {
                input = $('<textarea/>', { class: 'form-control', name: field.name, rows: 4 });
            } else {
                input = $('<input/>', { class: 'form-control', type: field.type, name: field.name });
            }
            col.append(label, input);
            $wrapper.append(col);
        });
    }

    function renderResult($container, data) {
        $container.empty();
        if (!data.downloads) {
            return;
        }
        const list = $('<div/>', { class: 'row g-2' });
        Object.values(data.downloads).forEach(item => {
            list.append(
                $('<div/>', { class: 'col-md-4' }).append(
                    $('<a/>', {
                        href: item.url || item,
                        class: 'btn btn-outline-primary w-100',
                        target: '_blank',
                        rel: 'noopener noreferrer',
                        text: (item.format || '').toUpperCase() || t('result_download')
                    })
                )
            );
        });
        $container.append(list);
    }

    function handleDropzone(dropzoneId, inputId, previewId) {
        const el = document.getElementById(dropzoneId);
        if (!el || !window.Dropzone) {
            return;
        }
        Dropzone.autoDiscover = false;
        const dz = new Dropzone(el, {
            url: NoaSoftQRPublic.ajax_url,
            autoProcessQueue: false,
            clickable: true,
            addRemoveLinks: false
        });
        $(el).css({
            borderColor: primaryColor,
            backgroundColor: hexToRgba(accentColor, 0.12)
        });
        dz.on('addedfile', function (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#' + inputId).val(e.target.result);
                $('#' + previewId).text(t('field_logo_saved'));
            };
            reader.readAsDataURL(file);
        });
    }

    $(document).ready(function () {
        $('.noasoft-qr-frontend').each(function () {
            this.style.setProperty('--noasoft-primary', primaryColor);
            this.style.setProperty('--noasoft-accent', accentColor);
        });
        $('.noasoft-qr-form').each(function () {
            const $form = $(this);
            const dynamicId = $form.data('dynamic');
            const dropzoneId = $form.data('dropzone');
            const logoField = $form.data('logo-field');
            const previewId = $form.data('logo-preview');
            const $dynamic = $('#' + dynamicId);
            renderDynamic($dynamic, $form.find('select[name="type"]').val());
            $form.on('change', 'select[name="type"]', function () {
                renderDynamic($dynamic, $(this).val());
            });
            handleDropzone(dropzoneId, logoField, previewId);
            $form.on('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'noasoft_qr_create');
                formData.append('nonce', NoaSoftQRPublic.nonce);
                $.ajax({
                    url: NoaSoftQRPublic.ajax_url,
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
                        renderResult($form.siblings('.noasoft-qr-result'), response.data);
                    },
                    error() {
                        toast(t('error_request'), 'danger');
                    }
                });
            });
        });
    });
})(jQuery);
