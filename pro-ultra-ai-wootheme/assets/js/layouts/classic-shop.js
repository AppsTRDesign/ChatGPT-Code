(function () {
  const body = document.body;
  if (!body.classList.contains('pro-ultra-layout-classic-shop') && !body.classList.contains('layout-classic')) {
    return;
  }

  const header = document.querySelector('.pro-ultra-header');
  if (header) {
    window.addEventListener('scroll', () => {
      header.classList.toggle('pro-ultra-header--shadow', (window.scrollY || 0) > 12);
    });
  }

  const anchors = document.querySelectorAll('.pro-ultra-nav a');
  anchors.forEach((link) => {
    link.addEventListener('mouseenter', () => link.classList.add('is-hover'));
    link.addEventListener('mouseleave', () => link.classList.remove('is-hover'));
  });
})();
