(function($){
  $(function(){
    const $toggle = $('.nav__toggle');
    const $menu = $('.nav__menu');

    $toggle.on('click', function(){
      const isOpen = $menu.toggleClass('nav__menu--open').hasClass('nav__menu--open');
      $(this).attr('aria-expanded', isOpen);
    });

    $(document).on('click', function(event){
      if (!$(event.target).closest('.header').length) {
        $menu.removeClass('nav__menu--open');
        $toggle.attr('aria-expanded', 'false');
      }
    });
  });
})(jQuery);
