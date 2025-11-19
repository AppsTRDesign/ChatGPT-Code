(function () {
  const body = document.body;
  if (!body.classList.contains('pro-ultra-layout-dark-future') && !body.classList.contains('layout-dark')) {
    return;
  }

  const header = document.querySelector('.pro-ultra-header');
  if (header) {
    window.addEventListener('mousemove', (evt) => {
      const x = (evt.clientX / window.innerWidth - 0.5) * 2;
      const y = (evt.clientY / window.innerHeight - 0.5) * 2;
      header.style.boxShadow = `0 18px 40px rgba(0,0,0,0.55), ${x * 6}px ${y * 6}px 24px rgba(56, 189, 248, 0.25)`;
    });
  }

  document.querySelectorAll('.pro-ultra-mini-cart__panel, .pro-ultra-card').forEach((card) => {
    card.addEventListener('mouseenter', () => {
      card.style.boxShadow = '0 20px 50px rgba(56, 189, 248, 0.25)';
    });
    card.addEventListener('mouseleave', () => {
      card.style.boxShadow = '';
    });
  });
})();
  document.querySelectorAll('.pro-ultra-payment-icons .pro-ultra-icon, .pro-ultra-carriers .pro-ultra-icon').forEach((icon)=>{
    icon.addEventListener('mouseenter', ()=> icon.classList.add('is-hover'));
    icon.addEventListener('mouseleave', ()=> icon.classList.remove('is-hover'));
  });
