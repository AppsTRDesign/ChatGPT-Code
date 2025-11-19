(function () {
  const body = document.body;
  if (!body.classList.contains('pro-ultra-layout-luxury-gold') && !body.classList.contains('layout-luxury')) {
    return;
  }

  const glowTargets = document.querySelectorAll('.button, .woocommerce a.button, .pro-ultra-mini-cart__panel');
  glowTargets.forEach((el) => {
    el.addEventListener('mouseenter', () => {
      el.style.boxShadow = '0 14px 36px rgba(250, 204, 21, 0.35)';
    });
    el.addEventListener('mouseleave', () => {
      el.style.boxShadow = '';
    });
  });
})();
  document.querySelectorAll('.pro-ultra-payment-icons .pro-ultra-icon, .pro-ultra-carriers .pro-ultra-icon').forEach((icon)=>{
    icon.addEventListener('mouseenter', ()=> icon.classList.add('is-hover'));
    icon.addEventListener('mouseleave', ()=> icon.classList.remove('is-hover'));
  });
