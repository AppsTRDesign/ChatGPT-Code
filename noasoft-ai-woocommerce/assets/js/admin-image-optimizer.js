(function($){
    var ImageOptimizer = {
        init: function(){
            if ( 'undefined' === typeof NoaSoftImageOptimizer ) {
                return;
            }
            this.$featuredBox = $('#postimagediv .inside');
            if ( ! this.$featuredBox.length ) {
                return;
            }
            this.renderButton();
            this.bindEvents();
        },
        renderButton: function(){
            this.$buttonWrap = $('<div class="noasoft-image-optimize-wrap" />');
            this.$button = $('<button type="button" class="button noasoft-image-optimize" />');
            this.$button.text(NoaSoftImageOptimizer.strings.button);
            if ( ! NoaSoftImageOptimizer.enabled ) {
                this.$button.prop('disabled', true);
            }
            this.$spinner = $('<span class="spinner"></span>');
            this.$buttonWrap.append(this.$button).append(this.$spinner);
            this.$featuredBox.append(this.$buttonWrap);
        },
        bindEvents: function(){
            var self = this;
            this.$button.on('click', function(e){
                e.preventDefault();
                if ( ! NoaSoftImageOptimizer.enabled ) {
                    self.notify(NoaSoftImageOptimizer.strings.disabled, 'error');
                    return;
                }
                var attachmentId = parseInt($('#_thumbnail_id').val(), 10);
                if ( ! attachmentId ) {
                    self.notify(NoaSoftImageOptimizer.strings.missing, 'error');
                    return;
                }
                self.optimize(attachmentId);
            });
        },
        optimize: function(attachmentId){
            var self = this;
            var postId = $('#post_ID').val();
            this.$button.prop('disabled', true);
            this.$spinner.addClass('is-active');
            var payload = {
                action: 'noasoft_ai_optimize_image',
                nonce: NoaSoftImageOptimizer.nonce,
                attachment_id: attachmentId,
                post_id: postId
            };
            $.post(NoaSoftImageOptimizer.ajax_url, payload)
                .done(function(response){
                    if ( response && response.success ) {
                        var newId = response.data.attachment_id;
                        $('#_thumbnail_id').val(newId).trigger('change');
                        if ( window.wp && wp.media && wp.media.featuredImage ) {
                            wp.media.featuredImage.set(newId);
                        }
                        self.notify(NoaSoftImageOptimizer.strings.success, 'success');
                    } else {
                        var message = (response && response.data && response.data.message) ? response.data.message : NoaSoftImageOptimizer.strings.error;
                        self.notify(message, 'error');
                    }
                })
                .fail(function(){
                    self.notify(NoaSoftImageOptimizer.strings.error, 'error');
                })
                .always(function(){
                    self.$button.prop('disabled', false);
                    self.$spinner.removeClass('is-active');
                });
        },
        notify: function(message, type){
            if ( window.NoaSoftToast ) {
                NoaSoftToast.show(message, type);
            } else {
                window.alert(message);
            }
        }
    };

    $(document).ready(function(){
        ImageOptimizer.init();
    });
})(jQuery);
