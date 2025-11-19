(function($){
    var escapeHtml = function(str){
        return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    };
    var Manager = {
        init: function(){
            this.$container = $('.noasoft-language-manager');
            if(!this.$container.length){
                return;
            }
            this.$localeSelect = this.$container.find('#noasoft-plugin-locale');
            this.$results = this.$container.find('.language-inline-results');
            this.$search = this.$container.find('#noasoft-language-search');
            this.activeLocale = this.$container.data('selected-locale') || (NoaSoftLanguageData && NoaSoftLanguageData.selected_locale) || 'default';
            this.inlineLocale = this.$container.find('.language-inline-editor').data('active-locale') || 'tr_TR';
            this.searchTimer = null;
            this.bindEvents();
            this.highlightPanel(this.inlineLocale);
        },
        bindEvents: function(){
            var self = this;
            this.$localeSelect.on('change', function(){
                self.activeLocale = $(this).val();
                self.saveLocale();
            });

            this.$container.on('click', '.noasoft-language-export', function(e){
                e.preventDefault();
                self.exportFile($(this).data('format'), $(this).data('locale'));
            });

            this.$container.on('change', '.noasoft-language-import', function(){
                var format = $(this).data('format');
                var locale = $(this).data('locale');
                if(this.files && this.files.length){
                    self.importFile(this.files[0], format, locale);
                    $(this).val('');
                }
            });

            this.$search.on('input', function(){
                var query = $(this).val();
                clearTimeout(self.searchTimer);
                self.searchTimer = setTimeout(function(){
                    self.search(query);
                }, 300);
            });

            this.$container.on('submit', '.noasoft-inline-entry', function(e){
                e.preventDefault();
                self.saveEntry($(this));
            });

            this.$container.on('click', '.language-panel', function(e){
                if($(e.target).is('button') || $(e.target).is('input') || $(e.target).closest('label').length){
                    return;
                }
                var locale = $(this).data('locale');
                self.setInlineLocale(locale);
            });
        },
        saveLocale: function(){
            var self = this;
            var locale = this.$localeSelect.val();
            $.post(NoaSoftLanguageData.ajax_url, {
                action: 'noasoft_ai_language_save_locale',
                nonce: NoaSoftLanguageData.nonce,
                locale: locale
            }).done(function(){
                self.toast(NoaSoftLanguageData.strings.localeSaved, 'success');
                if(locale !== 'default'){
                    self.setInlineLocale(locale);
                } else {
                    self.setInlineLocale('tr_TR');
                }
            }).fail(function(){
                self.toast(NoaSoftLanguageData.strings.exportFailed, 'error');
            });
        },
        exportFile: function(format, locale){
            var url = NoaSoftLanguageData.ajax_url + '?action=noasoft_ai_language_export_' + format + '&locale=' + encodeURIComponent(locale) + '&nonce=' + NoaSoftLanguageData.nonce;
            window.location.href = url;
        },
        importFile: function(file, format, locale){
            var formData = new FormData();
            formData.append('action', 'noasoft_ai_language_import_' + format);
            formData.append('nonce', NoaSoftLanguageData.nonce);
            formData.append('locale', locale);
            formData.append('file', file);
            var self = this;
            $.ajax({
                url: NoaSoftLanguageData.ajax_url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false
            }).done(function(resp){
                if(resp && resp.success){
                    self.toast(resp.data.message || NoaSoftLanguageData.strings.imported, 'success');
                } else {
                    self.toast(NoaSoftLanguageData.strings.importFailed, 'error');
                }
            }).fail(function(){
                self.toast(NoaSoftLanguageData.strings.importFailed, 'error');
            });
        },
        search: function(query){
            var locale = this.getInlineLocale();
            var self = this;
            if(!query){
                this.$results.removeClass('is-loading');
                this.$results.html('<div class="language-inline-empty">' + NoaSoftLanguageData.strings.searchHint + '</div>');
                return;
            }
            this.$results.addClass('is-loading');
            $.post(NoaSoftLanguageData.ajax_url, {
                action: 'noasoft_ai_language_search',
                nonce: NoaSoftLanguageData.nonce,
                locale: locale,
                query: query
            }).done(function(resp){
                self.$results.removeClass('is-loading');
                if(resp && resp.success && resp.data.results.length){
                    self.renderResults(resp.data.results, locale);
                } else {
                    self.$results.html('<div class="language-inline-empty">' + NoaSoftLanguageData.strings.noResults + '</div>');
                }
            }).fail(function(){
                self.$results.removeClass('is-loading');
                self.toast(NoaSoftLanguageData.strings.exportFailed, 'error');
            });
        },
        renderResults: function(results, locale){
            var html = results.map(function(item){
                return '\n                <form class="noasoft-inline-entry" data-key="' + item.key + '" data-locale="' + locale + '">\n                    <div class="inline-original">' + escapeHtml(item.original) + '</div>\n                    <textarea rows="3" name="translation">' + escapeHtml(item.translation || '') + '</textarea>\n                    <div class="inline-actions">\n                        <button type="submit" class="button button-primary">' + (item.has_override ? NoaSoftLanguageData.strings.savedState : NoaSoftLanguageData.strings.save) + '</button>\n                        ' + (item.has_override ? '<span class="inline-badge">' + NoaSoftLanguageData.strings.override + '</span>' : '') + '\n                    </div>\n                </form>';
            }).join('');
            this.$results.html(html);
        },
        saveEntry: function($form){
            var data = {
                action: 'noasoft_ai_language_save_entry',
                nonce: NoaSoftLanguageData.nonce,
                locale: $form.data('locale'),
                entry_key: $form.data('key'),
                translation: $form.find('textarea').val()
            };
            var self = this;
            $form.addClass('is-saving');
            $.post(NoaSoftLanguageData.ajax_url, data).done(function(resp){
                $form.removeClass('is-saving');
                if(resp && resp.success){
                    self.toast(resp.data.message || NoaSoftLanguageData.strings.saved, 'success');
                    var hasOverride = resp.data.has_override;
                    if(typeof resp.data.translation !== 'undefined'){
                        $form.find('textarea').val(resp.data.translation);
                    }
                    $form.find('.inline-badge').remove();
                    var $btn = $form.find('button');
                    if(hasOverride){
                        $btn.text(NoaSoftLanguageData.strings.savedState);
                        $form.find('.inline-actions').append('<span class="inline-badge">' + NoaSoftLanguageData.strings.override + '</span>');
                    } else {
                        $btn.text(NoaSoftLanguageData.strings.save);
                    }
                } else {
                    self.toast(NoaSoftLanguageData.strings.exportFailed, 'error');
                }
            }).fail(function(){
                $form.removeClass('is-saving');
                self.toast(NoaSoftLanguageData.strings.exportFailed, 'error');
            });
        },
        getInlineLocale: function(){
            return this.inlineLocale || 'tr_TR';
        },
        setInlineLocale: function(locale){
            this.inlineLocale = locale;
            this.$container.find('.language-inline-editor').attr('data-active-locale', locale);
            this.highlightPanel(locale);
            if(this.$search.val()){
                this.search(this.$search.val());
            }
        },
        highlightPanel: function(locale){
            this.$container.find('.language-panel').removeClass('is-active');
            this.$container.find('.language-panel[data-locale="' + locale + '"]').addClass('is-active');
        },
        toast: function(message, type){
            if(window.NoaSoftToast){
                window.NoaSoftToast.show(message, type);
            } else {
                alert(message);
            }
        }
    };

    $(function(){
        Manager.init();
    });
})(jQuery);
