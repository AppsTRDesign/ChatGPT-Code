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

  const subcategorySlider = document.getElementById('subcategorySlider');
  if (subcategorySlider && window.Splide) {
    new Splide(subcategorySlider, {
      perPage: 4,
      gap: '1rem',
      pagination: false,
      arrows: true,
      breakpoints: {
        900: { perPage: 3 },
        700: { perPage: 2 },
        500: { perPage: 1 },
      },
    }).mount();
  }

  const categorySlider = document.getElementById('categorySlider');
  if (categorySlider && window.Splide) {
    new Splide(categorySlider, {
      perPage: 8,
      gap: '1rem',
      pagination: false,
      arrows: true,
      breakpoints: {
        1200: { perPage: 6 },
        900: { perPage: 5 },
        700: { perPage: 4 },
        560: { perPage: 3 },
        420: { perPage: 2 },
      },
    }).mount();
  }

  if (window.GLightbox) {
    GLightbox({ selector: '.lightbox' });
  }

  if (window.lightbox) {
    lightbox.option({
      resizeDuration: 200,
      wrapAround: true,
      albumLabel: 'Görsel %1 / %2',
    });
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

  const campaignSlider = document.getElementById('campaignSlider');
  if (campaignSlider && window.Splide) {
    new Splide(campaignSlider, {
      type: 'loop',
      perPage: 1,
      gap: '1rem',
      pagination: true,
      arrows: true,
    }).mount();
  }
});
