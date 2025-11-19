(function($){
    $(function(){
        $('#noasoft-ai-tabs').tabs();
        $('.noasoft-ai-color').wpColorPicker();

        $('.noasoft-ai-copy').on('click', function(e){
            e.preventDefault();
            navigator.clipboard.writeText($(this).data('code'));
            wp.a11y.speak('Kopyalandı');
        });

        $('.noasoft-ai-upload').on('click', function(e){
            e.preventDefault();
            const frame = wp.media({ title: 'Avatar seç', multiple: false });
            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                $(e.currentTarget).closest('.noasoft-ai-avatar-field').find('input').val(attachment.id);
            });
            frame.open();
        });

        $('.noasoft-ai-remove').on('click', function(e){
            e.preventDefault();
            $(e.currentTarget).closest('.noasoft-ai-avatar-field').find('input').val('');
        });

        $('#noasoft-ai-fill').on('click', function(){
            const productId = $(this).data('product-id');
            const name = $('#title').val();
            $(this).prop('disabled', true);
            wp.apiRequest({
                path: `noasoft-ai/v1/products/${productId}/generate`,
                method: 'POST',
                data: { name, _wpnonce: NoaSoftAIAdmin.nonce }
            }).done(response => {
                $('input[name="noasoft_ai_seo_title"]').val(response.seo_title);
                $('textarea[name="noasoft_ai_seo_desc"]').val(response.seo_desc);
                $('input[name="noasoft_ai_tags"]').val(response.tags);
                $('textarea[name="noasoft_ai_advantages"]').val(response.advantages);
                $('textarea[name="noasoft_ai_features"]').val(response.features);
            }).fail(error => {
                alert(error.message || 'AI yanıtı alınamadı');
            }).always(() => $(this).prop('disabled', false));
        });

        $('#noasoft-ai-optimize-image').on('click', function(){
            const productId = $(this).data('product-id');
            const log = $('#noasoft-ai-image-log');
            log.text('İşleniyor...');
            wp.apiRequest({
                path: `noasoft-ai/v1/products/${productId}/optimize-image`,
                method: 'POST',
                data: { _wpnonce: NoaSoftAIAdmin.nonce }
            }).done(response => {
                log.text(response.message);
            }).fail(error => {
                log.text(error.message || 'İşlem başarısız');
            });
        });

        if (document.getElementById('noasoft-ai-report-chart')) {
            const ctx = document.getElementById('noasoft-ai-report-chart');
            const data = NoaSoftAIAdmin.reportData || { labels: [], datasets: [] };
            if (window.Chart) {
                new Chart(ctx, {
                    type: 'bar',
                    data,
                    options: { responsive: true }
                });
            }
        }
    });
})(jQuery);
