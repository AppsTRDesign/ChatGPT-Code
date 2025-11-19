(function () {
  const body = document.body;
  if (!body.classList.contains('pro-ultra-layout-gradient-modern') && !body.classList.contains('layout-gradient')) {
    return;
  }

  const hero = document.querySelector('.pro-ultra-header');
  const bubble = document.createElement('span');
  bubble.className = 'gradient-bubble';
  if (hero) {
    hero.appendChild(bubble);
    hero.addEventListener('mousemove', (e) => {
      const rect = hero.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width) * 100;
      const y = ((e.clientY - rect.top) / rect.height) * 100;
      bubble.style.left = `${x}%`;
      bubble.style.top = `${y}%`;
    });
  }

  let hue = 200;
  setInterval(() => {
    hue = (hue + 2) % 360;
    document.documentElement.style.setProperty('--gradient-accent', `hsl(${hue}, 75%, 65%)`);
  }, 120);
})();
  document.querySelectorAll('.pro-ultra-payment-icons .pro-ultra-icon, .pro-ultra-carriers .pro-ultra-icon').forEach((icon)=>{
    icon.addEventListener('mouseenter', ()=> icon.classList.add('is-hover'));
    icon.addEventListener('mouseleave', ()=> icon.classList.remove('is-hover'));
  });
