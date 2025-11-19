(function($){
    function ChatInstance($root, settings, globalConfig){
        this.$root = $root;
        this.settings = settings || {};
        this.globalConfig = globalConfig || {};
        if ( ! this.settings.enabled ) {
            return;
        }
        this.$panel       = $root.find('.noasoft-chat-panel');
        this.$launcher    = $root.find('.noasoft-chat-launcher');
        this.$messages    = $root.find('.noasoft-chat-messages');
        this.$suggestions = $root.find('.noasoft-chat-suggestions');
        this.$actionMenu  = $root.find('.noasoft-chat-action-menu');
        this.$actionToggle = this.$actionMenu.find('.noasoft-chat-action-toggle');
        this.$actionList  = this.$actionMenu.find('.noasoft-chat-action-list');
        this.$form        = $root.find('.noasoft-chat-form');
        this.$input       = this.$form.find('input[name="message"]');
        this.$file        = this.$form.find('.noasoft-chat-file');
        this.$uploadBtn   = this.$form.find('.noasoft-chat-upload');
        this.$spinner     = this.$root.find('.noasoft-chat-spinner');
        this.$typing      = this.$root.find('.noasoft-chat-typing');
        this.isBusy       = false;
        this.assistantAvatar = this.settings.avatar || '';
        this.assistantLabel  = this.settings.header_title || 'AI';
        this.userAvatar      = ( this.globalConfig.user && this.globalConfig.user.avatar ) ? this.globalConfig.user.avatar : '';
        this.userName        = ( this.globalConfig.user && this.globalConfig.user.name ) ? this.globalConfig.user.name : '';
        this.bindEvents();
        this.renderSuggestions();
        this.bootstrap();
        this.applyMenuCopy();
    }

    ChatInstance.prototype.bindEvents = function(){
        var self = this;
        if ( this.$launcher.length ) {
            this.$launcher.on('click', function(){
                self.togglePanel();
            });
        } else {
            this.$root.addClass('is-open');
        }

        this.$root.find('.noasoft-chat-close').on('click', function(){
            self.togglePanel(false);
        });

        this.$form.on('submit', function(e){
            e.preventDefault();
            var message = self.$input.val().trim();
            if ( ! message ) {
                return;
            }
            self.pushUserMessage( message );
            self.$input.val('');
            self.sendRequest({
                message: message,
                intent: 'general'
            });
        });

        if ( this.$actionToggle.length ) {
            this.$actionToggle.on('click', function(){
                self.toggleActionMenu();
            });
        }

        this.$actionList.on('click', 'button', function(){
            var intent = $(this).data('intent');
            self.handleIntent( intent );
        });

        this.$suggestions.on('click', 'button', function(){
            var text = $(this).text();
            self.pushUserMessage( text );
            self.sendRequest({
                message: text,
                intent: 'general'
            });
        });

        if ( this.$uploadBtn.length && this.settings.upload_enabled ) {
            this.$uploadBtn.on('click', function(){
                self.$file.trigger('click');
            });
            this.$file.on('change', function(){
                if ( this.files && this.files.length ) {
                    self.uploadImage( this.files[0] );
                    this.value = '';
                }
            });
        }

        this.$messages.on('click', '.noasoft-chat-product-add', function(e){
            e.preventDefault();
            var productId = $(this).data('product');
            self.addToCart( productId );
        });
    };

    ChatInstance.prototype.togglePanel = function(force){
        var isOpen = this.$root.hasClass('is-open');
        var next = 'undefined' === typeof force ? ! isOpen : !! force;
        this.$root.toggleClass('is-open', next);
        if ( this.$launcher.length ) {
            this.$launcher.attr('aria-expanded', next ? 'true' : 'false');
        }
        if ( next && window.NoaSoftAnimator ) {
            NoaSoftAnimator.spring( this.$panel.get(0) );
        }
    };

    ChatInstance.prototype.renderSuggestions = function(){
        var suggestions = this.settings.suggestions || [];
        if ( ! suggestions.length ) {
            this.$suggestions.hide();
            return;
        }
        var frag = $( document.createDocumentFragment() );
        suggestions.forEach(function(text){
            if ( ! text ) {
                return;
            }
            var btn = $('<button type="button" />').text( text );
            frag.append( btn );
        });
        this.$suggestions.empty().append( frag );
    };

    ChatInstance.prototype.bootstrap = function(){
        if ( this.settings.greeting ) {
            this.pushAssistantMessage( this.settings.greeting );
        }
    };

    ChatInstance.prototype.pushUserMessage = function(text){
        var $msg = $('<div class="noasoft-chat-msg is-user" />');
        $msg.append( this.buildAvatar('user') );
        $('<div class="bubble" />').text( text ).appendTo( $msg );
        this.$messages.append( $msg );
        if ( window.NoaSoftAnimator ) {
            NoaSoftAnimator.message( $msg.get(0) );
        }
        this.scrollToBottom();
    };

    ChatInstance.prototype.pushAssistantMessage = function(text, role){
        var cls = role || 'assistant';
        var $msg = $('<div class="noasoft-chat-msg is-' + cls + '" />');
        $msg.append( this.buildAvatar('assistant') );
        $('<div class="bubble" />').html( text ).appendTo( $msg );
        this.$messages.append( $msg );
        if ( window.NoaSoftAnimator ) {
            NoaSoftAnimator.message( $msg.get(0) );
        }
        this.scrollToBottom();
    };

    ChatInstance.prototype.scrollToBottom = function(){
        if ( ! this.$messages.length ) {
            return;
        }
        this.$messages.stop().animate({ scrollTop: this.$messages[0].scrollHeight }, 300);
    };

    ChatInstance.prototype.handleIntent = function(intent){
        if ( ! intent ) {
            return;
        }
        this.toggleActionMenu(false);
        this.promptIntent(intent);
    };

    ChatInstance.prototype.toggleActionMenu = function(force){
        if ( ! this.$actionMenu.length ) {
            return;
        }
        var isOpen = this.$actionMenu.hasClass('is-open');
        var next = 'undefined' === typeof force ? ! isOpen : !! force;
        this.$actionMenu.toggleClass('is-open', next);
        this.$actionMenu.attr('aria-expanded', next ? 'true' : 'false');
        if ( next && window.NoaSoftAnimator ) {
            var list = this.$actionMenu.find('.noasoft-chat-action-list').get(0);
            if ( list ) {
                NoaSoftAnimator.fadeSlide( list );
            }
        }
    };

    ChatInstance.prototype.applyMenuCopy = function(){
        var strings = this.settings.strings || {};
        if ( strings.menuHint ) {
            this.$root.find('.noasoft-chat-action-hint').text( strings.menuHint );
        }
        if ( strings.menuLabel ) {
            this.$actionToggle.find('.label').text( strings.menuLabel );
        }
        if ( strings.menuOpen ) {
            this.$actionToggle.attr( 'aria-label', strings.menuOpen );
        }
    };

    ChatInstance.prototype.promptIntent = function(intent){
        var self = this;
        var strings = this.settings.strings || {};
        if ( ! window.Swal ) {
            // fallback to simple prompt if SweetAlert2 not available
            var fallback = window.prompt( strings.productPrompt || '' );
            if ( ! fallback ) {
                return;
            }
            this.pushUserMessage( fallback );
            this.sendRequest({ intent: intent, message: fallback });
            return;
        }

        var html = '';
        var escapeAttr = this.escapeAttr.bind(this);
        if ( 'order_status' === intent || 'shipping_status' === intent ) {
            var emailDefault = this.globalConfig.user && this.globalConfig.user.email ? this.globalConfig.user.email : '';
            html = '<div class="noasoft-swal-group">'
                + '<label>' + escapeAttr( strings.orderPrompt || '' ) + '<input id="noasoft-swal-order" class="swal2-input" placeholder="' + escapeAttr( strings.orderPlaceholder || '' ) + '"></label>'
                + '<label>' + escapeAttr( strings.emailPrompt || '' ) + '<input id="noasoft-swal-email" class="swal2-input" placeholder="' + escapeAttr( strings.emailPlaceholder || '' ) + '" value="' + escapeAttr( emailDefault ) + '"></label>'
                + '</div>';
        } else {
            var label = intent === 'stock_status' ? strings.stockPrompt : strings.productPrompt;
            html = '<div class="noasoft-swal-group">'
                + '<label>' + escapeAttr( label || '' ) + '<input id="noasoft-swal-identifier" class="swal2-input" placeholder="' + escapeAttr( strings.identifierPlaceholder || '' ) + '"></label>'
                + '</div>';
        }

        var titleMap = {
            order_status: strings.orderTitle || 'Sipariş Durumu',
            shipping_status: strings.shippingTitle || 'Kargo Takibi',
            stock_status: strings.stockTitle || 'Stok Kontrolü',
            product_info: strings.productTitle || 'Ürün Bilgisi'
        };

        Swal.fire({
            title: titleMap[ intent ] || strings.menuLabel || 'AI',
            html: html,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: strings.modalConfirm || 'Devam',
            cancelButtonText: strings.modalCancel || 'Vazgeç',
            preConfirm: function(){
                if ( 'order_status' === intent || 'shipping_status' === intent ) {
                    var order = document.getElementById('noasoft-swal-order').value.trim();
                    var emailInput = document.getElementById('noasoft-swal-email').value.trim();
                    var email = emailInput || (self.globalConfig.user && self.globalConfig.user.email ? self.globalConfig.user.email : '');
                    if ( ! order ) {
                        Swal.showValidationMessage( strings.requiredField || 'Zorunlu alan' );
                        return false;
                    }
                    if ( ! email ) {
                        Swal.showValidationMessage( strings.emailPrompt || 'E-posta gerekli' );
                        return false;
                    }
                    return { order: order, email: email };
                }
                var identifier = document.getElementById('noasoft-swal-identifier').value.trim();
                if ( ! identifier ) {
                    Swal.showValidationMessage( strings.requiredField || 'Zorunlu alan' );
                    return false;
                }
                return { identifier: identifier };
            }
        }).then(function(result){
            if ( ! result.isConfirmed || ! result.value ) {
                return;
            }
            if ( 'order_status' === intent || 'shipping_status' === intent ) {
                self.pushUserMessage( result.value.order );
                self.sendRequest({
                    intent: intent,
                    order_number: result.value.order,
                    email: result.value.email,
                    message: result.value.order
                });
            } else {
                self.pushUserMessage( result.value.identifier );
                self.sendRequest({
                    intent: intent,
                    identifier: result.value.identifier,
                    message: result.value.identifier
                });
            }
        });
    };

    ChatInstance.prototype.escapeAttr = function(text){
        return ( text || '' ).replace(/"/g, '&quot;');
    };

    ChatInstance.prototype.buildAvatar = function(type){
        var src = type === 'assistant' ? this.assistantAvatar : this.userAvatar;
        var label = type === 'assistant' ? this.assistantLabel : ( this.userName || 'Siz' );
        var initials = ( label || '' ).split(' ').map(function(part){ return part.charAt(0); }).join('').substring(0, 2).toUpperCase();
        var $avatar = $('<div class="avatar" />');
        if ( src ) {
            $('<img />').attr({ src: src, alt: initials || label || '' }).appendTo( $avatar );
        } else {
            $avatar.text( initials || 'AI' );
        }
        return $avatar;
    };

    ChatInstance.prototype.sendRequest = function(data){
        if ( ! window.NoaSoftAiWooFrontend ) {
            return;
        }
        var self = this;
        data = data || {};
        data.action = 'noasoft_chat_message';
        data.nonce  = NoaSoftAiWooFrontend.nonce;
        this.setBusy( true );
        $.post( NoaSoftAiWooFrontend.ajax_url, data )
            .done(function(response){
                if ( response && response.success ) {
                    self.handleResponse( response.data );
                } else {
                    self.showError( ( response && response.data && response.data.message ) ? response.data.message : self.settings.strings.genericError );
                }
            })
            .fail(function(){
                self.showError( self.settings.strings.genericError );
            })
            .always(function(){
                self.setBusy( false );
            });
    };

    ChatInstance.prototype.setBusy = function(state){
        this.isBusy = state;
        this.$root.toggleClass( 'is-busy', !! state );
        if ( state ) {
            this.$spinner.addClass('visible');
            this.$typing.addClass('is-visible');
        } else {
            this.$spinner.removeClass('visible');
            this.$typing.removeClass('is-visible');
        }
    };

    ChatInstance.prototype.handleResponse = function(data){
        if ( data.reply ) {
            this.pushAssistantMessage( data.reply );
        }
        if ( data.products ) {
            this.appendProducts( data.products );
        }
        if ( data.order ) {
            this.appendOrder( data.order );
        }
    };

    ChatInstance.prototype.appendProducts = function(products){
        var self = this;
        products.forEach(function(product){
        var card = $('<div class="noasoft-chat-product-card" />');
            if ( product.image ) {
                card.append('<img src="' + product.image + '" alt="' + ( product.title || '' ) + '" />');
            }
            if ( product.title ) {
                card.append('<h4>' + product.title + '</h4>');
            }
            if ( product.price_html ) {
                card.append('<div class="price">' + product.price_html + '</div>');
            }
            if ( product.stock_html ) {
                card.append('<div class="stock">' + product.stock_html + '</div>');
            }
            if ( product.excerpt ) {
                card.append('<p>' + product.excerpt + '</p>');
            }
            if ( product.ai_copy ) {
                card.append('<div class="ai-copy">' + product.ai_copy + '</div>');
            }
            var actions = $('<div class="actions" />');
            if ( product.permalink ) {
                actions.append('<a class="button-link" href="' + product.permalink + '" target="_blank" rel="noreferrer">' + ( self.settings.strings.viewProduct || 'Ürüne Git' ) + '</a>');
            }
            if ( product.id ) {
                actions.append('<button type="button" class="noasoft-chat-product-add" data-product="' + product.id + '">' + ( self.settings.strings.addToCart || 'Sepete Ekle' ) + '</button>');
            }
            card.append( actions );
            self.$messages.append( card );
            if ( window.NoaSoftAnimator ) {
                NoaSoftAnimator.fadeSlide( card.get(0) );
            }
        });
        this.scrollToBottom();
    };

    ChatInstance.prototype.appendOrder = function(order){
        var strings = this.settings.strings || {};
        var card = $('<div class="noasoft-chat-order-card" />');
        card.append('<h4>' + ( strings.orderSummary || 'Sipariş Özeti' ) + '</h4>');
        if ( order.number ) {
            card.append('<div class="order-number">#' + order.number + ' - ' + ( order.status || '' ) + '</div>');
        }
        if ( order.date ) {
            card.append('<div class="order-date">' + order.date + '</div>');
        }
        if ( order.items && order.items.length ) {
            var list = $('<ul class="order-items" />');
            order.items.forEach(function(item){
                list.append('<li>' + item.qty + ' × ' + item.name + '</li>');
            });
            card.append( list );
        }
        if ( order.total ) {
            card.append('<div class="order-total">' + order.total + '</div>');
        }
        if ( order.tracking && order.tracking.number ) {
            var track = $('<div class="tracking" />').text( ( strings.trackingLabel || 'Takip No:' ) + ' ' + order.tracking.number );
            if ( order.tracking.url ) {
                track.append(' <a href="' + order.tracking.url + '" target="_blank" rel="noreferrer">' + ( strings.viewTracking || 'Takip et' ) + '</a>');
            }
            card.append( track );
        }
        this.$messages.append( card );
        if ( window.NoaSoftAnimator ) {
            NoaSoftAnimator.fadeSlide( card.get(0) );
        }
        this.scrollToBottom();
    };

    ChatInstance.prototype.uploadImage = function(file){
        if ( ! window.NoaSoftAiWooFrontend || ! file ) {
            return;
        }
        var self = this;
        var formData = new FormData();
        formData.append( 'action', 'noasoft_ai_chat_upload' );
        formData.append( 'nonce', NoaSoftAiWooFrontend.nonce );
        formData.append( 'chat_image', file );
        this.setBusy( true );
        $.ajax({
            url: NoaSoftAiWooFrontend.ajax_url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        }).done(function(response){
            if ( response && response.success ) {
                self.handleResponse( response.data );
            } else {
                self.showError( ( response && response.data && response.data.message ) ? response.data.message : self.settings.strings.genericError );
            }
        }).fail(function(){
            self.showError( self.settings.strings.genericError );
        }).always(function(){
            self.setBusy( false );
        });
    };

    ChatInstance.prototype.addToCart = function(productId){
        if ( ! productId || ! window.NoaSoftAiWooFrontend ) {
            return;
        }
        var self = this;
        var strings = this.settings.strings || {};
        var successMsg = strings.cartSuccess || 'Ürün sepete eklendi.';
        var errorMsg = strings.cartError || 'Ürün sepete eklenemedi.';
        $.post( NoaSoftAiWooFrontend.ajax_url, {
            action: 'noasoft_ai_chat_add_to_cart',
            product_id: productId,
            nonce: NoaSoftAiWooFrontend.nonce
        }).done(function(response){
            if ( response && response.success ) {
                self.toast( successMsg, 'success' );
            } else {
                self.toast( errorMsg, 'error' );
            }
        }).fail(function(){
            self.toast( errorMsg, 'error' );
        });
    };

    ChatInstance.prototype.showError = function(message){
        var fallback = ( this.settings.strings && this.settings.strings.genericError ) ? this.settings.strings.genericError : 'Bir hata oluştu.';
        this.toast( message || fallback, 'error' );
        if ( message ) {
            this.pushAssistantMessage( message, 'system' );
        }
    };

    ChatInstance.prototype.toast = function(message, type){
        if ( window.NoaSoftToast ) {
            NoaSoftToast.show( message, type );
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
    };

    $(document).ready(function(){
        var config = window.NoaSoftAiWooFrontend || {};
        var settings = config.chat || {};
        if ( ! settings.enabled ) {
            return;
        }
        $('.noasoft-chat-embed').each(function(){
            new ChatInstance( $(this), settings, config );
        });
    });
})(jQuery);
