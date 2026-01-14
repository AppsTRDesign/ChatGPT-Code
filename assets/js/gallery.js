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

  const homeSlider = document.getElementById('homeSlider');
  if (homeSlider && window.Splide) {
    new Splide(homeSlider, {
      type: 'loop',
      perPage: 1,
      autoplay: true,
      interval: 4000,
      gap: '1rem',
      arrows: true,
      pagination: true,
    }).mount();
  }
});
