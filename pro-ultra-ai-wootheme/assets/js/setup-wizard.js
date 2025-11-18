(function ($) {
    const Wizard = {
        init() {
            this.$panels = $('.pro-ultra-panel');
            this.$steps = $('.pro-ultra-step-item');
            this.$nonce = $('#pro-ultra-setup-nonce');
            this.bindEvents();
            if (proUltraSetup.completed) {
                this.setStep('finish');
            }
        },

        bindEvents() {
            const self = this;
            $('.pro-ultra-next').on('click', function (e) {
                e.preventDefault();
                self.setStep($(this).data('next'));
            });

            $('.pro-ultra-prev').on('click', function (e) {
                e.preventDefault();
                self.setStep($(this).data('prev'));
            });

            $('.pro-ultra-run').on('click', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const action = $btn.data('action');
                const next = $btn.data('next');
                self.runAction(action, $btn, next);
            });
        },

        setStep(step) {
            if (!step) {
                return;
            }
            this.$panels.removeClass('active');
            this.$panels.filter(`[data-step="${step}"]`).addClass('active');
            this.$steps.removeClass('active');
            this.$steps.filter(`[data-step="${step}"]`).addClass('active');
        },

        markComplete(step) {
            this.$steps.filter(`[data-step="${step}"]`).addClass('completed');
        },

        runAction(action, $btn, nextStep) {
            if (!action) {
                return;
            }
            const original = $btn.text();
            $btn.prop('disabled', true).text($btn.data('loading-text') || original);

            $.ajax({
                url: proUltraSetup.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'pro_ultra_setup_' + action,
                    security: this.$nonce.val() || proUltraSetup.nonce,
                },
            })
                .done((response) => {
                    if (response.success) {
                        this.handleResponse(action, response.data);
                        const stepKey = {
                            requirements: 'requirements',
                            plugins: 'plugins',
                            import: 'import',
                            home: 'homepage',
                            finish: 'finish',
                        }[action] || action;
                        this.markComplete(stepKey);
                        if (nextStep) {
                            this.setStep(nextStep);
                        }
                        this.toast(response.data && response.data.message ? response.data.message : proUltraSetup.texts.done);
                    } else {
                        this.toast(response.data && response.data.message ? response.data.message : 'Hata oluştu', true);
                    }
                })
                .fail(() => {
                    this.toast('İstek başarısız. Lütfen tekrar deneyin.', true);
                })
                .always(() => {
                    $btn.prop('disabled', false).text(original);
                });
        },

        handleResponse(action, data) {
            switch (action) {
                case 'requirements':
                    this.renderList('#pro-ultra-req-results', data.requirements || []);
                    break;
                case 'plugins':
                    this.renderList('#pro-ultra-plugin-results', data.plugins || []);
                    break;
                case 'import':
                    this.renderImport('#pro-ultra-import-results', data);
                    break;
                case 'home':
                    this.renderHome('#pro-ultra-home-results', data);
                    break;
                case 'finish':
                    this.setStep('finish');
                    break;
                default:
                    break;
            }
        },

        renderList(container, items) {
            const $wrap = $(container);
            $wrap.empty();
            if (!items.length) {
                const emptyText = (window.wp && window.wp.i18n) ? wp.i18n.__('Sonuç bulunamadı', 'pro-ultra-ai') : 'Sonuç yok';
                $wrap.append('<p>' + emptyText + '</p>');
                return;
            }
            items.forEach((row) => {
                const statusClass = row.status ? 'success' : 'error';
                const badge = `<span class="badge ${statusClass}">${row.status ? '✓' : '×'} ${row.status ? proUltraSetup.texts.done : 'Hata'}</span>`;
                $wrap.append(`<div class="pro-ultra-result-row"><strong>${row.label}</strong>${badge}<span>${row.message || ''}</span></div>`);
            });
        },

        renderImport(container, data) {
            const $wrap = $(container);
            $wrap.empty();
            if (!data) {
                return;
            }
            if (data.products && data.products.error) {
                $wrap.append(`<div class="pro-ultra-result-row"><span class="badge error">×</span><span>${data.products.error}</span></div>`);
                return;
            }
            const pages = data.pages ? Object.keys(data.pages).length : 0;
            const products = data.products && data.products.length ? data.products.length : 0;
            const menu = data.menus && data.menus.menu_name ? data.menus.menu_name : 'Menu';
            $wrap.append(`<div class="pro-ultra-result-row"><strong>Sayfalar</strong><span class="badge success">${pages}</span></div>`);
            $wrap.append(`<div class="pro-ultra-result-row"><strong>Ürünler</strong><span class="badge success">${products}</span></div>`);
            $wrap.append(`<div class="pro-ultra-result-row"><strong>Menü</strong><span class="badge success">${menu}</span></div>`);
        },

        renderHome(container, data) {
            const $wrap = $(container);
            $wrap.empty();
            if (data && data.message) {
                $wrap.append(`<div class="pro-ultra-result-row"><span class="badge success">✓</span><span>${data.message}</span></div>`);
            }
        },

        toast(message, isError = false) {
            const $notice = $('<div class="notice is-dismissible"></div>');
            $notice.addClass(isError ? 'notice-error' : 'notice-success').append(`<p>${message}</p>`);
            $('.pro-ultra-setup-wrap').prepend($notice);
            setTimeout(() => {
                $notice.fadeOut(300, () => $notice.remove());
            }, 4000);
        },
    };

    $(function () {
        Wizard.init();
    });
})(jQuery);
