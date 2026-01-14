document.addEventListener('DOMContentLoaded', () => {
  const gallery = document.querySelector('.product-gallery-slider');
  if (gallery && window.Splide) {
    new Splide(gallery, {
      type: 'loop',
      perPage: 1,
      gap: '1rem',
      pagination: true,
      arrows: true,
    }).mount();
  }

  const similarSlider = document.getElementById('similarSlider');
  if (similarSlider && window.Splide) {
    new Splide(similarSlider, {
      type: 'loop',
      perPage: 3,
      gap: '1rem',
      pagination: false,
      breakpoints: {
        900: { perPage: 2 },
        600: { perPage: 1 },
      },
    }).mount();
  }

  if (window.GLightbox) {
    GLightbox({ selector: '.lightbox' });
  }
});
