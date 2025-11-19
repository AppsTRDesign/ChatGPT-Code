(function($){
    var NoaSoftSettings = {
        init: function(){
            this.$tabs = $('.noasoft-tab-nav a');
            this.$panels = $('.noasoft-tab-panel');
            this.$body = $('body');
            this.bindTabs();
            this.activateTab( this.$tabs.first().data('tab') );
            this.bindAjaxForms();
            this.bindProviderTest();
            this.bindClipboard();
            this.bindModals();
            this.bindRangeFields();
            this.bindAvatarPicker();
        },
        bindTabs: function(){
            var self = this;
            this.$tabs.on('click', function(e){
                e.preventDefault();
                var tab = $(this).data('tab');
                self.activateTab( tab );
            });
        },
        activateTab: function(tab){
            if ( ! tab ) {
                return;
            }
            this.$tabs.removeClass('active').attr('aria-selected', 'false');
            var $activeTab = this.$tabs.filter('[data-tab="' + tab + '"]').addClass('active').attr('aria-selected', 'true');
            this.$panels.removeClass('is-active');
            var $panel = this.$panels.filter('[data-tab="' + tab + '"]').addClass('is-active');
            if (window.NoaSoftAnimator && $panel.length) {
                NoaSoftAnimator.fadeSlide($panel.get(0));
            }
            if ($activeTab.length && window.NoaSoftAnimator) {
                NoaSoftAnimator.spring($activeTab.get(0));
            }
        },
        bindAjaxForms: function(){
            var self = this;
            $(document).on('submit', '.noasoft-ajax-form', function(e){
                e.preventDefault();
                self.submitAjaxForm( $(this) );
            });
        },
        submitAjaxForm: function($form){
            if ( 'undefined' === typeof NoaSoftAiWooAdmin ) {
                return;
            }
            var data = $form.serialize();
            var $spinner = $form.find('.noasoft-form-spinner');
            var $button = $form.find('button[type="submit"]').last();
            $spinner.addClass('is-active');
            $button.prop('disabled', true);

            $.post(NoaSoftAiWooAdmin.ajax_url, data)
                .done(function(response){
                    var successMessage = $form.data('success') || NoaSoftSettings.getSuccessMessage();
                    if ( response && response.success ) {
                        NoaSoftSettings.toast(response.data && response.data.message ? response.data.message : successMessage, 'success');
                    } else {
                        var message = (response && response.data && response.data.message) ? response.data.message : NoaSoftSettings.getErrorMessage();
                        NoaSoftSettings.toast(message, 'error');
                    }
                })
                .fail(function(){
                    NoaSoftSettings.toast(NoaSoftSettings.getErrorMessage(), 'error');
                })
                .always(function(){
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false);
                });
        },
        bindProviderTest: function(){
            var self = this;
            $(document).on('click', '.noasoft-provider-test', function(e){
                e.preventDefault();
                var slug = $(this).data('provider');
                self.runProviderTest( slug, $(this) );
            });
        },
        runProviderTest: function(slug, $button){
            if ( ! slug || 'undefined' === typeof NoaSoftAiWooAdmin ) {
                return;
            }
            var payload = {
                action: 'noasoft_ai_provider_test',
                provider: slug,
                nonce: NoaSoftAiWooAdmin.nonce
            };
            if ( window.Swal ) {
                Swal.fire({
                    title: NoaSoftAiWooAdmin.providerTest ? NoaSoftAiWooAdmin.providerTest.title : 'Test',
                    text: NoaSoftAiWooAdmin.providerTest ? NoaSoftAiWooAdmin.providerTest.running : 'Bağlantı test ediliyor...',
                    allowOutsideClick: false,
                    didOpen: function(){ Swal.showLoading(); }
                });
            }
            $button.prop('disabled', true);
            $.post(NoaSoftAiWooAdmin.ajax_url, payload)
                .done(function(response){
                    var message = (response && response.data && response.data.message) ? response.data.message : NoaSoftSettings.getSuccessMessage();
                    if ( window.Swal ) {
                        Swal.fire({
                            icon: response && response.success ? 'success' : 'error',
                            title: NoaSoftAiWooAdmin.providerTest ? NoaSoftAiWooAdmin.providerTest.title : 'Test',
                            text: message
                        });
                    } else {
                        NoaSoftSettings.toast(message, response && response.success ? 'success' : 'error');
                    }
                })
                .fail(function(){
                    NoaSoftSettings.toast(NoaSoftSettings.getErrorMessage(), 'error');
                })
                .always(function(){
                    $button.prop('disabled', false);
                });
        },
        bindClipboard: function(){
            $(document).on('click', '.noasoft-copy', function(){
                var value = $(this).data('copy');
                if ( ! value ) {
                    return;
                }
                if ( navigator.clipboard ) {
                    navigator.clipboard.writeText( value ).then(function(){
                        NoaSoftSettings.toast(NoaSoftAiWooAdmin.copy ? NoaSoftAiWooAdmin.copy.success : 'Kopyalandı', 'success');
                    }).catch(function(){
                        NoaSoftSettings.toast(NoaSoftAiWooAdmin.copy ? NoaSoftAiWooAdmin.copy.error : 'Kopyalanamadı', 'error');
                    });
                    return;
                }
                var temp = $('<textarea />').css({ position: 'absolute', left: '-9999px' }).val( value );
                $('body').append( temp );
                temp.select();
                try {
                    document.execCommand('copy');
                    NoaSoftSettings.toast(NoaSoftAiWooAdmin.copy ? NoaSoftAiWooAdmin.copy.success : 'Kopyalandı', 'success');
                } catch (err) {
                    NoaSoftSettings.toast(NoaSoftAiWooAdmin.copy ? NoaSoftAiWooAdmin.copy.error : 'Kopyalanamadı', 'error');
                }
                temp.remove();
            });
        },
        bindModals: function(){
            var self = this;
            $(document).on('click', '[data-modal-target]', function(e){
                e.preventDefault();
                var target = $(this).data('modalTarget');
                var $modal = $(target);
                if ($modal.length) {
                    $modal.addClass('is-visible');
                    self.$body.addClass('noasoft-modal-open');
                }
            });
            $(document).on('click', '.noasoft-modal-close, .noasoft-modal, [data-modal-close]', function(e){
                var $targetModal = $(this).closest('.noasoft-modal');
                if ($(this).is('[data-modal-close]')) {
                    $targetModal.removeClass('is-visible');
                    self.$body.removeClass('noasoft-modal-open');
                    return;
                }
                if (!$(e.target).closest('.noasoft-modal-dialog').length || $(e.target).is('.noasoft-modal-close')) {
                    $('.noasoft-modal').removeClass('is-visible');
                    self.$body.removeClass('noasoft-modal-open');
                }
            });
        },
        bindRangeFields: function(){
            var update = function($input){
                var $wrap = $input.closest('.range-control');
                var $display = $wrap.find('.range-value');
                if ($display.length) {
                    var unit = $display.data('unit') || '';
                    $display.text($input.val() + unit);
                }
            };
            $('.noasoft-range-field input[type="range"]').each(function(){
                update($(this));
            });
            $(document).on('input change', '.noasoft-range-field input[type="range"]', function(){
                update($(this));
            });
        },
        bindAvatarPicker: function(){
            if (typeof wp === 'undefined' || !wp.media) {
                return;
            }
            $(document).on('click', '.noasoft-avatar-picker .pick', function(e){
                e.preventDefault();
                var $wrap = $(this).closest('.noasoft-avatar-picker');
                var mediaLabels = (window.NoaSoftAiWooAdmin && NoaSoftAiWooAdmin.media) ? NoaSoftAiWooAdmin.media : {};
                var frame = wp.media({
                    title: mediaLabels.title || 'Avatar seç',
                    button: { text: mediaLabels.button || 'Kullan' },
                    multiple: false,
                    library: { type: 'image' }
                });
                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    var preview = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
                    $wrap.find('.noasoft-avatar-input').val(attachment.id);
                    $wrap.find('.avatar-preview img').attr('src', preview);
                    $wrap.find('.remove').prop('disabled', false);
                });
                frame.open();
            });

            $(document).on('click', '.noasoft-avatar-picker .remove', function(e){
                e.preventDefault();
                var $wrap = $(this).closest('.noasoft-avatar-picker');
                $wrap.find('.noasoft-avatar-input').val('');
                $wrap.find('.avatar-preview img').attr('src', $wrap.data('placeholder'));
                $(this).prop('disabled', true);
            });
        },
        toast: function(message, type){
            if ( window.NoaSoftToast ) {
                NoaSoftToast.show(message, type);
                return;
            }
            if ( window.Swal ) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    timer: 2500,
                    showConfirmButton: false,
                    icon: type === 'error' ? 'error' : 'success',
                    title: message
                });
            }
        },
        getErrorMessage: function(){
            if ( 'undefined' !== typeof NoaSoftAiWooAdmin && NoaSoftAiWooAdmin.error ) {
                return NoaSoftAiWooAdmin.error;
            }
            return 'Beklenmedik bir hata oluştu.';
        },
        getSuccessMessage: function(){
            if ( 'undefined' !== typeof NoaSoftAiWooAdmin && NoaSoftAiWooAdmin.success ) {
                return NoaSoftAiWooAdmin.success;
            }
            return 'İşlem tamamlandı.';
        }
    };

    $(document).ready(function(){
        NoaSoftSettings.init();
    });
})(jQuery);
