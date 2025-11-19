(function($){
    function appendMessage(wrapper, text, me) {
        const div = $('<div/>').addClass('noasoft-ai-message').text(text);
        if (me) {
            div.addClass('me');
        }
        wrapper.append(div);
        wrapper.scrollTop(wrapper[0].scrollHeight);
    }

    function sendMessage(message, image) {
        const formData = new FormData();
        formData.append('message', message);
        if (image) {
            formData.append('image', image);
        }
        return fetch(NoaSoftAI.rest + '/chat', {
            method: 'POST',
            headers: { 'X-WP-Nonce': NoaSoftAI.nonce },
            body: formData
        }).then(res => res.json());
    }

    $(function(){
        const chat = $('.noasoft-ai-chat');
        const trigger = chat.find('.noasoft-ai-trigger');
        const windowEl = chat.find('.noasoft-ai-window');
        const form = chat.find('.noasoft-ai-form');
        const messages = chat.find('.noasoft-ai-messages');
        const themeData = (() => {
            try { return JSON.parse(chat.attr('data-theme')); } catch (e) { return null; }
        })();
        if (themeData && chat.length) {
            Object.entries(themeData).forEach(([key, value]) => {
                chat.get(0).style.setProperty(`--noasoft-${key}`, value);
            });
        }

        trigger.on('click', function(){
            const expanded = trigger.attr('aria-expanded') === 'true';
            trigger.attr('aria-expanded', !expanded);
            windowEl.toggle(expanded === true);
            if (!expanded) {
                windowEl.show();
            }
        });

        chat.find('.noasoft-ai-close').on('click', function(){
            windowEl.hide();
            trigger.attr('aria-expanded', 'false');
        });

        form.on('submit', function(e){
            e.preventDefault();
            const input = form.find('input[name="message"]');
            const file = form.find('input[type="file"]')[0].files[0];
            const message = input.val();
            if (!message && !file) {
                return;
            }
            appendMessage(messages, message || '[Medya gönderildi]', true);
            input.val('');
            sendMessage(message, file).then(response => {
                appendMessage(messages, response.reply);
            }).catch(() => {
                appendMessage(messages, 'Yanıt alınamadı');
            });
        });
    });
})(jQuery);
