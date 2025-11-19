(function($){
    var NoaSoftSettings = {
        init: function(){
            this.$tabs = $('.noasoft-tab-nav a');
            this.$panels = $('.noasoft-tab-panel');
            this.$body = $('body');
            this.bindTabs();
            this.activateTab( this.$tabs.first().data('tab') );
            this.bindChatForm();
            this.bindMediaForm();
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
        bindChatForm: function(){
            var self = this;
            $(document).on('submit', '.noasoft-chat-settings-form', function(e){
                e.preventDefault();
                self.saveChatSettings( $(this) );
            });
        },
        bindMediaForm: function(){
            var self = this;
            $(document).on('submit', '.noasoft-media-settings-form', function(e){
                e.preventDefault();
                self.saveMediaSettings( $(this) );
            });
        },
        saveChatSettings: function($form){
            if ( 'undefined' === typeof NoaSoftAiWooAdmin ) {
                return;
            }
            var data = $form.serializeArray();
            data.push({ name: 'action', value: 'noasoft_ai_save_chat_settings' });
            data.push({ name: 'nonce', value: NoaSoftAiWooAdmin.nonce });
            $form.find('.spinner').addClass('is-active');
            $.post(NoaSoftAiWooAdmin.ajax_url, data)
                .done(function(response){
                    if ( response && response.success ) {
                        if ( window.NoaSoftToast ) {
                            NoaSoftToast.show(response.data.message, 'success');
                        }
                    } else {
                        var message = (response && response.data && response.data.message) ? response.data.message : NoaSoftSettings.getErrorMessage();
                        if ( window.NoaSoftToast ) {
                            NoaSoftToast.show(message, 'error');
                        }
                    }
                })
                .fail(function(){
                    if ( window.NoaSoftToast ) {
                        NoaSoftToast.show(NoaSoftSettings.getErrorMessage(), 'error');
                    }
                })
                .always(function(){
                    $form.find('.spinner').removeClass('is-active');
                });
        },
        saveMediaSettings: function($form){
            if ( 'undefined' === typeof NoaSoftAiWooAdmin ) {
                return;
            }
            var data = $form.serializeArray();
            data.push({ name: 'action', value: 'noasoft_ai_save_media_settings' });
            data.push({ name: 'nonce', value: NoaSoftAiWooAdmin.nonce });
            $form.find('.spinner').addClass('is-active');
            $.post(NoaSoftAiWooAdmin.ajax_url, data)
                .done(function(response){
                    if ( response && response.success ) {
                        if ( window.NoaSoftToast ) {
                            NoaSoftToast.show(response.data.message, 'success');
                        }
                    } else {
                        var message = (response && response.data && response.data.message) ? response.data.message : NoaSoftSettings.getErrorMessage();
                        if ( window.NoaSoftToast ) {
                            NoaSoftToast.show(message, 'error');
                        }
                    }
                })
                .fail(function(){
                    if ( window.NoaSoftToast ) {
                        NoaSoftToast.show(NoaSoftSettings.getErrorMessage(), 'error');
                    }
                })
                .always(function(){
                    $form.find('.spinner').removeClass('is-active');
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
        getErrorMessage: function(){
            if ( 'undefined' !== typeof NoaSoftAiWooAdmin && NoaSoftAiWooAdmin.error ) {
                return NoaSoftAiWooAdmin.error;
            }
            return 'Beklenmedik bir hata oluştu.';
        }
    };

    $(document).ready(function(){
        NoaSoftSettings.init();
    });
})(jQuery);
