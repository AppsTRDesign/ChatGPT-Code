(function ($) {
    'use strict';

    function CompareWidget($root) {
        this.$root = $root;
        this.$form = $root.find('.noasoft-ai-compare-form');
        this.$results = $root.find('.noasoft-ai-compare-results');
        this.$status = $root.find('.noasoft-ai-compare-status');
        this.$shareBtn = $root.find('[data-action="copy-share"]');
        this.$pdfBtn = $root.find('[data-action="export-pdf"]');
        this.state = {
            payload: null
        };

        this.bindEvents();
        this.prefill();
    }

    CompareWidget.prototype.bindEvents = function () {
        var self = this;
        this.$form.on('submit', function (event) {
            event.preventDefault();
            var values = self.getValues();
            if (!values.product_one || !values.product_two) {
                self.setStatus(NoaSoftAiCompare.strings.validation, 'error');
                return;
            }
            self.fetchComparison(values.product_one, values.product_two);
        });

        this.$root.on('click', '[data-action="swap"]', function () {
            var $one = self.$form.find('input[name="product_one"]');
            var $two = self.$form.find('input[name="product_two"]');
            var temp = $one.val();
            $one.val($two.val());
            $two.val(temp);
        });

        this.$shareBtn.on('click', function () {
            var url = $(this).data('share-url');
            if (!url) {
                self.toast(NoaSoftAiCompare.strings.shareUnavailable, 'error');
                return;
            }
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function () {
                    self.toast(NoaSoftAiCompare.strings.shareCopied, 'success');
                }).catch(function () {
                    self.fallbackCopy(url);
                });
            } else {
                self.fallbackCopy(url);
            }
        });

        this.$pdfBtn.on('click', function () {
            if (!self.state.payload) {
                self.toast(NoaSoftAiCompare.strings.shareUnavailable, 'error');
                return;
            }
            self.exportPdf();
        });
    };

    CompareWidget.prototype.prefill = function () {
        var raw = this.$root.attr('data-prefill');
        var data = {};
        try {
            data = raw ? JSON.parse(raw) : {};
        } catch (e) {
            data = {};
        }

        if (data.product_one) {
            this.$form.find('input[name="product_one"]').val(data.product_one);
        }
        if (data.product_two) {
            this.$form.find('input[name="product_two"]').val(data.product_two);
        }

        if (this.$root.attr('data-auto-run') === '1' && data.product_one && data.product_two) {
            this.fetchComparison(data.product_one, data.product_two);
        }
    };

    CompareWidget.prototype.getValues = function () {
        return {
            product_one: this.$form.find('input[name="product_one"]').val().trim(),
            product_two: this.$form.find('input[name="product_two"]').val().trim()
        };
    };

    CompareWidget.prototype.setStatus = function (message, type) {
        this.$status.removeClass('is-loading is-error is-success');
        if (!message) {
            this.$status.text('');
            return;
        }
        if (type) {
            this.$status.addClass('is-' + type);
        }
        this.$status.text(message);
    };

    CompareWidget.prototype.setLoading = function (isLoading, message) {
        if (isLoading) {
            this.$root.addClass('is-loading');
            this.setStatus(message || NoaSoftAiCompare.strings.loading, 'loading');
        } else {
            this.$root.removeClass('is-loading');
            this.setStatus('');
        }
    };

    CompareWidget.prototype.fetchComparison = function (productOne, productTwo) {
        var self = this;
        this.setLoading(true);
        $.post(NoaSoftAiCompare.ajax_url, {
            action: 'noasoft_ai_compare_products',
            nonce: NoaSoftAiCompare.nonce,
            product_one: productOne,
            product_two: productTwo,
            page_url: window.location.href
        }).done(function (response) {
            if (response && response.success) {
                self.renderResults(response.data);
            } else {
                var message = response && response.data && response.data.message ? response.data.message : NoaSoftAiCompare.strings.aiError;
                self.setStatus(message, 'error');
                self.toast(message, 'error');
            }
        }).fail(function () {
            self.setStatus(NoaSoftAiCompare.strings.aiError, 'error');
            self.toast(NoaSoftAiCompare.strings.aiError, 'error');
        }).always(function () {
            self.setLoading(false);
        });
    };

    CompareWidget.prototype.renderResults = function (data) {
        this.state.payload = data;
        var products = data.products || [];
        var ai = data.ai || {};
        var html = '';
        var self = this;

        if (products.length === 2) {
            html += '<div class="noasoft-ai-compare-cards">';
            html += this.renderProductCard(products[0]);
            html += this.renderProductCard(products[1]);
            html += '</div>';
        }

        html += '<div class="noasoft-ai-compare-ai">';
        if (ai.summary) {
            html += '<div class="noasoft-ai-compare-summary">' + ai.summary + '</div>';
        }
        if (ai.differences && ai.differences.length) {
            html += '<div class="noasoft-ai-compare-section"><h4>' + this.escape(NoaSoftAiCompare.strings.differences) + '</h4><ul>';
            ai.differences.forEach(function (item) {
                html += '<li>' + self.escape(item) + '</li>';
            });
            html += '</ul></div>';
        }
        html += this.renderProsCons(ai.product_one, this.escape(NoaSoftAiCompare.strings.productOne));
        html += this.renderProsCons(ai.product_two, this.escape(NoaSoftAiCompare.strings.productTwo));
        if (ai.decision_matrix && ai.decision_matrix.length) {
            html += '<div class="noasoft-ai-compare-matrix"><h4>' + this.escape(NoaSoftAiCompare.strings.matrix) + '</h4>';
            ai.decision_matrix.forEach(function (row) {
                html += '<div class="matrix-row"><strong>' + self.escape(row.title) + '</strong><p>' + self.escape(row.detail) + '</p></div>';
            });
            html += '</div>';
        }
        if (ai.final_recommendation) {
            html += '<div class="noasoft-ai-compare-recommendation"><h4>' + this.escape(NoaSoftAiCompare.strings.recommendation) + '</h4>' + ai.final_recommendation + '</div>';
        }
        html += '</div>';

        if (!html) {
            var emptyText = this.$results.attr('data-empty-text') || '';
            this.$results.html('<p class="noasoft-ai-compare-empty">' + emptyText + '</p>');
        } else {
            this.$results.html(html);
            if (window.NoaSoftAnimator) {
                NoaSoftAnimator.fadeSlide(this.$results.get(0));
            }
        }

        if (data.share && data.share.url) {
            this.$shareBtn.prop('disabled', false).data('share-url', data.share.url);
        } else {
            this.$shareBtn.prop('disabled', true).removeData('share-url');
        }

        this.$pdfBtn.prop('disabled', false);
    };

    CompareWidget.prototype.renderProductCard = function (product) {
        if (!product) {
            return '';
        }
        var self = this;
        var html = '<article class="compare-card">';
        if (product.image) {
            html += '<div class="card-media"><img src="' + product.image + '" alt="' + (product.name || '') + '" /></div>';
        }
        html += '<div class="card-body">';
        if (product.name) {
            html += '<h4>' + this.escape(product.name) + '</h4>';
        }
        if (product.price_html) {
            html += '<div class="price">' + product.price_html + '</div>';
        }
        if (product.short_description) {
            html += '<p>' + this.escape(product.short_description) + '</p>';
        }
        if (product.attributes && product.attributes.length) {
            html += '<ul class="attributes">';
            product.attributes.slice(0, 4).forEach(function (attr) {
                html += '<li>' + self.escape(attr) + '</li>';
            });
            html += '</ul>';
        }
        if (product.stock_html) {
            html += '<div class="stock">' + product.stock_html + '</div>';
        }
        if (product.permalink) {
            html += '<a class="button button-small" target="_blank" rel="noopener" href="' + product.permalink + '">' + this.escape(NoaSoftAiCompare.strings.productLink) + '</a>';
        }
        html += '</div></article>';
        return html;
    };

    CompareWidget.prototype.renderProsCons = function (section, fallbackLabel) {
        if (!section) {
            return '';
        }
        var self = this;
        var html = '<div class="noasoft-ai-compare-section">';
        var title = section.label || fallbackLabel;
        html += '<h4>' + this.escape(title) + '</h4>';
        if (section.best_for) {
            html += '<p class="best-for">' + this.escape(section.best_for) + '</p>';
        }
        if (section.pros && section.pros.length) {
            html += '<div class="list-block"><strong>' + this.escape(NoaSoftAiCompare.strings.pros) + '</strong><ul>';
            section.pros.forEach(function (item) {
                html += '<li>' + self.escape(item) + '</li>';
            });
            html += '</ul></div>';
        }
        if (section.cons && section.cons.length) {
            html += '<div class="list-block"><strong>' + this.escape(NoaSoftAiCompare.strings.cons) + '</strong><ul>';
            section.cons.forEach(function (item) {
                html += '<li>' + self.escape(item) + '</li>';
            });
            html += '</ul></div>';
        }
        html += '</div>';
        return html;
    };

    CompareWidget.prototype.escape = function (text) {
        var div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    };

    CompareWidget.prototype.toast = function (message, type) {
        if (window.NoaSoftToast) {
            window.NoaSoftToast.show(message, type);
        }
    };

    CompareWidget.prototype.fallbackCopy = function (text) {
        var temp = document.createElement('input');
        temp.type = 'text';
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        this.toast(NoaSoftAiCompare.strings.shareCopied, 'success');
    };

    CompareWidget.prototype.exportPdf = function () {
        var self = this;
        this.setStatus(NoaSoftAiCompare.strings.pdfPreparing, 'loading');
        $.post(NoaSoftAiCompare.ajax_url, {
            action: 'noasoft_ai_compare_pdf',
            nonce: NoaSoftAiCompare.nonce,
            payload: JSON.stringify({
                products: this.state.payload.products,
                ai: this.state.payload.ai
            })
        }).done(function (response) {
            if (response && response.success && response.data && response.data.url) {
                window.open(response.data.url, '_blank');
                self.setStatus('', 'success');
            } else {
                self.toast(NoaSoftAiCompare.strings.pdfError, 'error');
                self.setStatus(NoaSoftAiCompare.strings.pdfError, 'error');
            }
        }).fail(function () {
            self.toast(NoaSoftAiCompare.strings.pdfError, 'error');
            self.setStatus(NoaSoftAiCompare.strings.pdfError, 'error');
        });
    };

    $(function () {
        if (typeof NoaSoftAiCompare === 'undefined') {
            return;
        }
        $('.noasoft-ai-compare').each(function () {
            new CompareWidget($(this));
        });
    });
})(jQuery);
