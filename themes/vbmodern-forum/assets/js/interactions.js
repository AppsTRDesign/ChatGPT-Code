(function($){
  $(function(){
    $('.section__tab').on('click', function(){
      const $tab = $(this);
      const target = $tab.data('target');

      $tab.addClass('section__tab--active').siblings().removeClass('section__tab--active');

      if (target) {
        $(target)
          .addClass('is-active')
          .siblings('[data-tab-content]')
          .removeClass('is-active');
      }
    });

    $('.filter-chip').on('click', function(){
      $(this).toggleClass('filter-chip--active');
    });

    $('[data-theme-toggle]').on('click', function(){
      const isLight = $('body').toggleClass('theme--light').hasClass('theme--light');
      $(this).attr('aria-pressed', isLight);
    });

    const openModal = (selector) => {
      const $modal = $(selector);
      if ( $modal.length ) {
        $modal.removeAttr('hidden');
        $('body').addClass('has-open-modal');
      }
    };

    const closeModal = ($modal) => {
      $modal.attr('hidden', true);
      if ( ! $('.forum-modal:not([hidden])').length ) {
        $('body').removeClass('has-open-modal');
      }
    };

    $(document).on('click', '[data-modal-trigger]', function(){
      const target = $(this).data('modal-trigger');
      if ( target ) {
        openModal(target);
      }
    });

    $(document).on('click', '[data-modal-close]', function(){
      closeModal($(this).closest('.forum-modal'));
    });

    $(document).on('mousedown', '.forum-modal', function(event){
      if ( $(event.target).is('.forum-modal') ) {
        closeModal($(this));
      }
    });

    const handleResponse = (response, successMessage, callback) => {
      if ( response.success ) {
        Swal.fire({
          icon: 'success',
          title: successMessage,
          timer: 2000,
          showConfirmButton: false
        }).then(() => {
          if ( response.data && response.data.redirect ) {
            window.location.href = response.data.redirect;
          }
          if ( typeof callback === 'function' ) {
            callback(response);
          }
        });
      } else {
        const message = response.data && response.data.message ? response.data.message : vbModernForum.i18n.unknownError;
        Swal.fire({
          icon: 'error',
          title: message
        });
      }
    };

    $(document).on('submit', '[data-ajax-form]', function(event){
      event.preventDefault();
      const $form = $(this);
      const action = $form.data('ajax-action');
      const fallbackMessage = $form.data('success-message') || vbModernForum.i18n.topicCreated;

      if ( ! action ) {
        return;
      }

      const formData = $form.serializeArray();
      formData.push({ name: 'action', value: action });
      formData.push({ name: 'nonce', value: vbModernForum.nonce });

      Swal.fire({
        title: vbModernForum.i18n.processing,
        didOpen: () => { Swal.showLoading(); },
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false
      });

      $.post(vbModernForum.ajaxUrl, formData)
        .done(function(response){
          Swal.close();
          handleResponse(response, (response.data && response.data.message) ? response.data.message : fallbackMessage, function(){
            if ( $form.is('#vb-register-form') || $form.is('#vb-reset-form') ) {
              closeModal($form.closest('.forum-modal'));
              $form.trigger('reset');
            }
            if ( $form.is('#vb-topic-form') && response.data && response.data.redirect ) {
              window.location.href = response.data.redirect;
            }
          });
        })
        .fail(function(){
          Swal.close();
          Swal.fire({
            icon: 'error',
            title: vbModernForum.i18n.unknownError
          });
        });
    });

    $(document).on('click', '[data-moderation-action]', function(){
      const $button = $(this);
      const action = $button.data('moderation-action');
      const topicId = $button.data('topic-id');
      const userId = $button.data('user-id');

      if ( ! action ) {
        return;
      }

      const payload = {
        action: action === 'lock' ? 'vbmodern_forum_toggle_lock' : 'vbmodern_forum_toggle_ban',
        nonce: vbModernForum.nonce,
        post_id: topicId,
        user_id: userId
      };

      Swal.fire({
        title: vbModernForum.i18n.processing,
        didOpen: () => { Swal.showLoading(); },
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false
      });

      $.post(vbModernForum.ajaxUrl, payload)
        .done(function(response){
          Swal.close();
          handleResponse(response, response.data && response.data.message ? response.data.message : vbModernForum.i18n.unknownError, function(res){
            if ( action === 'lock' && res.data ) {
              const locked = res.data.locked ? 1 : 0;
              $button.attr('data-locked', locked);
              $button.text( locked ? vbModernForum.i18n.unlockTopic : vbModernForum.i18n.lockTopic );
              if ( locked ) {
                $('.post__notice--locked').removeAttr('hidden');
              } else {
                $('.post__notice--locked').attr('hidden', true);
              }
            }

            if ( action === 'ban' && res.data ) {
              const banned = res.data.banned ? 1 : 0;
              $button.attr('data-banned', banned);
              $button.text( banned ? vbModernForum.i18n.unbanUser : vbModernForum.i18n.banUser );
            }
          });
        })
        .fail(function(){
          Swal.close();
          Swal.fire({
            icon: 'error',
            title: vbModernForum.i18n.unknownError
          });
        });
    });
  });
})(jQuery);
