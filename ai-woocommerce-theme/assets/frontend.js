(function ($) {
  const toast = (message) => {
    let el = document.querySelector('.ai-toast');
    if (!el) {
      el = document.createElement('div');
      el.className = 'ai-toast';
      document.body.appendChild(el);
    }
    el.textContent = message;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2200);
  };

  $('.ai-ajax-cart').on('click', function (event) {
    event.preventDefault();
    const productId = $(this).data('product-id');
    if (!productId) return;

    toast(AICartSettings.i18n.processing);
    $.post(AICartSettings.ajaxUrl, {
      action: 'aicart_add_to_cart',
      product_id: productId,
    }).done((response) => {
      toast(response.data && response.data.message ? response.data.message : AICartSettings.i18n.addedToCart);
    });
  });

  $('.ai-fav-toggle').on('click', function (event) {
    event.preventDefault();
    const productId = $(this).data('product-id');
    const target = $(this);
    $.post(AICartSettings.ajaxUrl, {
      action: target.hasClass('wishlist') ? 'aicart_toggle_wishlist' : 'aicart_toggle_favorite',
      product_id: productId,
    }).done(() => {
      target.toggleClass('active');
      toast(AICartSettings.i18n.saved);
    });
  });

  $('.ai-like-toggle').on('click', function (event) {
    event.preventDefault();
    const productId = $(this).data('product-id');
    const target = $(this);
    $.post(AICartSettings.ajaxUrl, {
      action: 'aicart_toggle_like',
      product_id: productId,
    }).done(() => {
      target.toggleClass('active');
      toast(AICartSettings.i18n.saved);
    });
  });

  $('#ai-filter-form').on('change', 'select, input', function () {
    $(this).closest('form').trigger('submit');
  });

  $('#ai-filter-form').on('submit', function (event) {
    event.preventDefault();
    const params = $(this).serialize();
    const url = `${window.location.pathname}?${params}`;
    window.location.href = url;
  });

  // Floating assistant panel
  const panel = $('#ai-assistant-panel');
  if (AICartSettings.assistant) {
    $('#ai-toggle-assistant').on('click', function () {
      panel.attr('aria-hidden', false).addClass('open');
    });
    $('.ai-assistant-close').on('click', function () {
      panel.attr('aria-hidden', true).removeClass('open');
    });
  } else {
    panel.remove();
  }

  $('#ai-assistant-form').on('submit', function (event) {
    event.preventDefault();
    const prompt = $(this).find('textarea[name="prompt"]').val();
    const mode = $(this).find('input[name="mode"]:checked').val();
    if (!prompt) return;
    const log = $('.ai-assistant-log');
    log.append(`<div class="ai-chat-line user">${prompt}</div>`);
    $.post(AICartSettings.ajaxUrl, {
      action: 'aicart_ai_assistant',
      prompt,
      mode,
    }).done((response) => {
      const text = response?.data?.payload?.message || JSON.stringify(response?.data?.payload || response?.data);
      log.append(`<div class="ai-chat-line bot">${text}</div>`);
      log.scrollTop(log.prop('scrollHeight'));
    });
  });

  $('#ai-inline-compare').on('submit', function (event) {
    event.preventDefault();
    const data = $(this).serializeArray();
    const ids = data.map((item) => item.value).filter(Boolean);
    $.post(AICartSettings.ajaxUrl, { action: 'aicart_compare_products', 'product_ids[]': ids }).done((response) => {
      $('#ai-compare-result').text(response?.data?.comparison?.summary || JSON.stringify(response.data));
    });
  });

  $('#ai-order-lookup').on('submit', function (event) {
    event.preventDefault();
    const payload = $(this).serialize();
    $.post(AICartSettings.ajaxUrl, `action=aicart_order_lookup&${payload}`).done((response) => {
      $('#ai-order-result').text(response?.data?.status?.message || JSON.stringify(response.data));
    }).fail(() => toast('API hatası'));
  });
})(jQuery);
