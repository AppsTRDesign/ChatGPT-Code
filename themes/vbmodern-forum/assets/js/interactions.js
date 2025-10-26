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
  });
})(jQuery);
