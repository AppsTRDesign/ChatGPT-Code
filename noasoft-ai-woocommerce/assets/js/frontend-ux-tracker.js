(function($){
    var Tracker = {
        send: function(eventName, payload){
            if (!window.NoaSoftAiWooTracker) {
                return;
            }

            var data = {
                action: 'noasoft_track_event',
                nonce: NoaSoftAiWooTracker.nonce,
                event: eventName,
                data: payload || {},
                session: NoaSoftAiWooTracker.session || ''
            };

            if (navigator.sendBeacon) {
                var formData = new FormData();
                Object.keys(data).forEach(function(key){
                    if (typeof data[key] === 'object') {
                        formData.append(key, JSON.stringify(data[key]));
                    } else {
                        formData.append(key, data[key]);
                    }
                });
                navigator.sendBeacon(NoaSoftAiWooTracker.ajax_url, formData);
            } else {
                $.post(NoaSoftAiWooTracker.ajax_url, data);
            }
        },
        trackView: function(){
            if (NoaSoftAiWooTracker.product) {
                Tracker.send('view_product', { product_id: NoaSoftAiWooTracker.product });
            }
        },
        trackAddToCart: function(){
            $(document).on('submit', 'form.cart', function(){
                var productId = $(this).find('[name="add-to-cart"]').val() || NoaSoftAiWooTracker.product;
                if (productId) {
                    Tracker.send('add_to_cart', { product_id: productId });
                }
            });

            $(document).on('click', '.add_to_cart_button', function(){
                var productId = $(this).data('product_id');
                if (productId) {
                    Tracker.send('add_to_cart', { product_id: productId });
                }
            });
        },
        trackFavorites: function(){
            $(document).on('click', '.noasoft-favorite-button', function(){
                var productId = $(this).data('product_id') || $(this).data('product-id') || NoaSoftAiWooTracker.product;
                if (productId) {
                    Tracker.send('favorite', { product_id: productId });
                }
            });
        },
        trackReviews: function(){
            $(document).on('submit', '#commentform', function(){
                var productId = $(this).find('#comment_post_ID').val() || NoaSoftAiWooTracker.product;
                if (productId) {
                    Tracker.send('review', { product_id: productId });
                }
            });
        },
        init: function(){
            if (!window.NoaSoftAiWooTracker) {
                return;
            }

            Tracker.trackView();
            Tracker.trackAddToCart();
            Tracker.trackFavorites();
            Tracker.trackReviews();
        }
    };

    $(document).ready(function(){
        Tracker.init();
    });
})(jQuery);
