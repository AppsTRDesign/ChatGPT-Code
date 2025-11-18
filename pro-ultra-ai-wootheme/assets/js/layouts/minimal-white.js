(function () {
  const body = document.body;
  if (!body.classList.contains('pro-ultra-layout-minimal-white') && !body.classList.contains('layout-minimal')) {
    return;
  }

  const header = document.querySelector('.pro-ultra-header');
  let last = 0;
  window.addEventListener('scroll', () => {
    if (!header) return;
    const y = window.scrollY || window.pageYOffset;
    if (y > 32 && y > last) {
      header.classList.add('pro-ultra-header--elevated');
    } else if (y < 24) {
      header.classList.remove('pro-ultra-header--elevated');
    }
    last = y;
  });

  document.querySelectorAll('.button, .woocommerce a.button, .pro-ultra-mini-cart__close').forEach((btn) => {
    btn.addEventListener('pointerdown', (event) => {
      const ripple = document.createElement('span');
      ripple.className = 'pro-ultra-ripple';
      ripple.style.left = `${event.offsetX}px`;
      ripple.style.top = `${event.offsetY}px`;
      btn.appendChild(ripple);
      setTimeout(() => ripple.remove(), 500);
    });
  });
})();
