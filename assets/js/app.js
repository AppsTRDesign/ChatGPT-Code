const navToggle = document.getElementById('navToggle');
const mainNav = document.getElementById('mainNav');
if (navToggle && mainNav) {
  navToggle.addEventListener('click', () => {
    mainNav.classList.toggle('open');
  });
}

const notifySuccess = (message) => {
  if (window.toastr) {
    toastr.success(message);
  }
};

const notifyError = (message) => {
  if (window.toastr) {
    toastr.error(message);
  } else {
    console.error(message);
  }
};

const getCsrfToken = () => (
  document.querySelector('input[name="csrf_token"]')?.value
  || document.querySelector('meta[name="csrf-token"]')?.content
  || ''
);

document.querySelectorAll('[data-ajax]').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    if (form.closest('.checkout')) {
      return;
    }
    event.preventDefault();
    const action = form.dataset.ajax;
    const formData = new FormData(form);

    try {
      const response = await fetch(`/api/handler.php?action=${action}`, {
        method: 'POST',
        body: formData,
      });
      let data = {};
      try {
        data = await response.json();
      } catch (error) {
        notifyError('Sunucu yanıtı okunamadı.');
        return;
      }

      if (!response.ok) {
        notifyError(data.message || 'İşlem başarısız.');
        return;
      }

      if (data.message) {
        notifySuccess(data.message);
      }

      if (data.redirect) {
        window.location.href = data.redirect;
      }
    } catch (error) {
      notifyError('Sunucuya ulaşılamadı.');
    }
  });
});

const updateFavoriteButton = (button, favorited) => {
  button.classList.toggle('is-active', favorited);
  button.setAttribute('aria-pressed', favorited ? 'true' : 'false');
  const filledIcon = button.dataset.favoriteFilled || '♥';
  const emptyIcon = button.dataset.favoriteEmpty || '♡';
  if (button.dataset.favoriteLabelAdd || button.dataset.favoriteLabelRemove) {
    button.textContent = favorited
      ? (button.dataset.favoriteLabelRemove || 'Favorilerden Çıkar')
      : (button.dataset.favoriteLabelAdd || 'Favoriye Ekle');
    button.classList.toggle('primary', favorited);
  } else {
    button.textContent = favorited ? filledIcon : emptyIcon;
  }
};

document.addEventListener('click', async (event) => {
  const button = event.target.closest('[data-favorite]');
  if (!button) return;
  const productId = button.dataset.favorite;
  const formData = new FormData();
  formData.append('csrf_token', getCsrfToken());
  formData.append('product_id', productId);

  try {
    const response = await fetch('/api/handler.php?action=favorite', {
      method: 'POST',
      body: formData,
    });
    const data = await response.json();
    if (response.ok) {
      notifySuccess(data.message || 'Favori güncellendi.');
      updateFavoriteButton(button, data.favorited === true);
      if (data.favorited === false) {
        const favoriteItem = button.closest('[data-favorite-item]');
        if (favoriteItem) {
          favoriteItem.remove();
        }
      }
    } else {
      notifyError(data.message || 'Favori güncellenemedi.');
    }
  } catch (error) {
    notifyError('Sunucuya ulaşılamadı.');
  }
});

document.addEventListener('click', async (event) => {
  const button = event.target.closest('[data-review-delete]');
  if (!button) return;
  const reviewId = button.dataset.reviewDelete;
  const formData = new FormData();
  formData.append('csrf_token', getCsrfToken());
  formData.append('review_id', reviewId);

  try {
    const response = await fetch('/api/handler.php?action=review-delete', {
      method: 'POST',
      body: formData,
    });
    const data = await response.json();
    if (response.ok) {
      notifySuccess(data.message || 'Yorum silindi.');
      button.closest('.review-card')?.remove();
    } else {
      notifyError(data.message || 'Yorum silinemedi.');
    }
  } catch (error) {
    notifyError('Sunucuya ulaşılamadı.');
  }
});

document.querySelectorAll('[data-cart-add]').forEach((button) => {
  button.addEventListener('click', async () => {
    const productId = button.dataset.cartAdd;
    const formData = new FormData();
    formData.append('csrf_token', getCsrfToken());
    formData.append('product_id', productId);

    try {
      const response = await fetch('/api/handler.php?action=cart-add', {
        method: 'POST',
        body: formData,
      });
      const data = await response.json();
      if (response.ok) {
        notifySuccess(data.message || 'Sepete eklendi.');
      } else {
        notifyError(data.message || 'Sepete eklenemedi.');
      }
    } catch (error) {
      notifyError('Sunucuya ulaşılamadı.');
    }
  });
});

document.querySelectorAll('[data-cart-remove]').forEach((button) => {
  button.addEventListener('click', async () => {
    const productId = button.dataset.cartRemove;
    const formData = new FormData();
    formData.append('csrf_token', getCsrfToken());
    formData.append('product_id', productId);

    try {
      const response = await fetch('/api/handler.php?action=cart-remove', {
        method: 'POST',
        body: formData,
      });
      const data = await response.json();
      if (response.ok) {
        notifySuccess(data.message || 'Sepetten çıkarıldı.');
        button.closest('tr')?.remove();
      } else {
        notifyError(data.message || 'Sepetten çıkarılamadı.');
      }
    } catch (error) {
      notifyError('Sunucuya ulaşılamadı.');
    }
  });
});

document.addEventListener('click', async (event) => {
  const button = event.target.closest('[data-review-like]');
  if (!button) return;
  if (button.disabled || button.dataset.reviewLikePending === 'true') {
    return;
  }
  const reviewId = button.dataset.reviewLike;
  const formData = new FormData();
  formData.append('csrf_token', getCsrfToken());
  formData.append('review_id', reviewId);
  button.dataset.reviewLikePending = 'true';

  try {
    const response = await fetch('/api/handler.php?action=review-like', {
      method: 'POST',
      body: formData,
    });
    const data = await response.json();
    if (response.ok) {
      const likes = typeof data.likes === 'number'
        ? data.likes
        : (parseInt(button.dataset.reviewLikes, 10) || 0) + 1;
      button.dataset.reviewLikes = likes;
      button.textContent = `Faydalı (${likes})`;
      button.disabled = true;
      button.classList.add('primary');
      notifySuccess(data.message || 'Beğeni kaydedildi.');
    } else {
      notifyError(data.message || 'Beğeni kaydedilemedi.');
    }
  } catch (error) {
    notifyError('Sunucuya ulaşılamadı.');
  } finally {
    button.dataset.reviewLikePending = 'false';
  }
});

const reviewsContainer = document.getElementById('reviewsContainer');
const reviewsPagination = document.getElementById('reviewsPagination');
if (reviewsContainer && reviewsPagination) {
  reviewsPagination.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-review-page]');
    if (!button) return;
    const page = button.dataset.reviewPage;
    const productId = reviewsContainer.dataset.productId;
    const sort = reviewsContainer.dataset.reviewSort || 'top';
    const formData = new FormData();
    formData.append('csrf_token', getCsrfToken());
    formData.append('product_id', productId);
    formData.append('page', page);
    formData.append('sort', sort);

    try {
      const response = await fetch('/api/handler.php?action=reviews-list', {
        method: 'POST',
        body: formData,
      });
      const data = await response.json();
      if (response.ok) {
        reviewsContainer.innerHTML = data.html || '';
        reviewsPagination.querySelectorAll('[data-review-page]').forEach((pageButton) => {
          pageButton.classList.toggle('primary', pageButton.dataset.reviewPage === page);
        });
      } else {
        notifyError(data.message || 'Yorumlar yüklenemedi.');
      }
    } catch (error) {
      notifyError('Sunucuya ulaşılamadı.');
    }
  });
}

document.querySelectorAll('[data-tabs]').forEach((tabs) => {
  const buttons = tabs.querySelectorAll('[data-tab-target]');
  const panels = tabs.querySelectorAll('.tab-panel');
  const activateTab = (button) => {
    const targetSelector = button.dataset.tabTarget;
    const targetPanel = tabs.querySelector(targetSelector);
    buttons.forEach((tabButton) => {
      const isActive = tabButton === button;
      tabButton.classList.toggle('is-active', isActive);
      tabButton.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
    panels.forEach((panel) => {
      panel.classList.toggle('is-active', panel === targetPanel);
    });
  };
  buttons.forEach((button, index) => {
    button.addEventListener('click', () => activateTab(button));
    if (index === 0 && button.classList.contains('is-active')) {
      activateTab(button);
    }
  });
});

document.querySelectorAll('[data-price-range]').forEach((range) => {
  const minRange = range.querySelector('[data-range="min"]');
  const maxRange = range.querySelector('[data-range="max"]');
  const minValue = range.querySelector('[data-range-value="min"]');
  const maxValue = range.querySelector('[data-range-value="max"]');
  const minInput = range.querySelector('input[name="price_min"]');
  const maxInput = range.querySelector('input[name="price_max"]');
  if (!minRange || !maxRange || !minValue || !maxValue || !minInput || !maxInput) return;

  const syncValues = () => {
    let min = Number(minRange.value);
    let max = Number(maxRange.value);
    if (min > max) {
      [min, max] = [max, min];
    }
    minRange.value = String(min);
    maxRange.value = String(max);
    minValue.textContent = String(min);
    maxValue.textContent = String(max);
    minInput.value = String(min);
    maxInput.value = String(max);
  };

  minRange.addEventListener('input', syncValues);
  maxRange.addEventListener('input', syncValues);
  syncValues();
});
