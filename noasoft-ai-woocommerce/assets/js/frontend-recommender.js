(function($){
    var Recommender = {
        init: function(){
            if (typeof NoaSoftAiWooRecommender === 'undefined') {
                return;
            }

            $(document).on('click', '.noasoft-recommender-refresh', function(e){
                e.preventDefault();
                var $card = $(this).closest('.noasoft-recommender-card');
                Recommender.fetch($card);
            });

            $(document).on('click', '.noasoft-recommender-add', function(e){
                e.preventDefault();
                var productId = $(this).data('product');
                if (productId) {
                    Recommender.addToCart(productId);
                }
            });
        },
        fetch: function($card){
            if (!$card.length) {
                return;
            }

            var productId = $card.data('productId') || 0;
            $card.addClass('loading');

            $.post(NoaSoftAiWooRecommender.ajax_url, {
                action: 'noasoft_ai_recommendations',
                nonce: NoaSoftAiWooRecommender.nonce,
                product_id: productId
            }).done(function(response){
                if (response.success) {
                    Recommender.updateCard($card, response.data);
                } else if (window.NoaSoftToast) {
                    NoaSoftToast.show(response.data && response.data.message ? response.data.message : NoaSoftAiWooRecommender.error_label, 'error');
                }
            }).fail(function(){
                if (window.NoaSoftToast) {
                    NoaSoftToast.show(NoaSoftAiWooRecommender.error_label, 'error');
                }
            }).always(function(){
                $card.removeClass('loading');
            });
        },
        updateCard: function($card, data){
            if (!data || !data.product) {
                return;
            }

            $card.attr('data-product-id', data.product.id || 0);
            $card.find('.noasoft-recommender-title')
                .text(data.product.title || '')
                .attr('href', data.product.permalink || '#');
            $card.find('.noasoft-recommender-price').html(data.product.price_html || '');
            $card.find('.noasoft-recommender-product img')
                .attr('src', data.product.image || '')
                .attr('alt', data.product.title || '');

            if (data.ai_copy) {
                $card.find('.noasoft-recommender-header h3').text(data.ai_copy.headline || '');
                $card.find('.noasoft-recommender-why').text(data.ai_copy.why || '');
                Recommender.renderList($card.find('.noasoft-recommender-pros'), data.ai_copy.pros);
                Recommender.renderList($card.find('.noasoft-recommender-cons'), data.ai_copy.cons);
            }

            var metaLabel = $card.find('.noasoft-recommender-meta').data('meta-label') || '';
            var metaValue = data.events ? data.events.length : 0;
            $card.find('.noasoft-recommender-meta').text(metaValue + ' ' + metaLabel);

            $card.find('.noasoft-recommender-add').attr('data-product', data.product.id || '');
        },
        renderList: function($list, items){
            if (!$list.length) {
                return;
            }
            $list.empty();
            if (!items || !items.length) {
                return;
            }
            items.forEach(function(item){
                var value = $('<li/>').text(item);
                $list.append(value);
            });
        },
        addToCart: function(productId){
            if (!window.NoaSoftAiWooFrontend) {
                return;
            }
            $.post(NoaSoftAiWooFrontend.ajax_url, {
                action: 'noasoft_ai_chat_add_to_cart',
                nonce: NoaSoftAiWooFrontend.nonce,
                product_id: productId
            }).done(function(response){
                if (response && response.success) {
                    window.NoaSoftToast && NoaSoftToast.show(NoaSoftAiWooFrontend.chat.strings.cartSuccess, 'success');
                } else {
                    window.NoaSoftToast && NoaSoftToast.show(NoaSoftAiWooFrontend.chat.strings.cartError, 'error');
                }
            }).fail(function(){
                window.NoaSoftToast && NoaSoftToast.show(NoaSoftAiWooFrontend.chat.strings.cartError, 'error');
            });
        }
    };

    $(document).ready(function(){
        Recommender.init();
    });
})(jQuery);
