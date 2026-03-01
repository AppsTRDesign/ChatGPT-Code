const navToggle = document.getElementById('navToggle');
const mainNav = document.getElementById('mainNav');
if (navToggle && mainNav) {
  navToggle.addEventListener('click', () => {
    mainNav.classList.toggle('open');
  });
}

document.querySelectorAll('.nav-dropdown').forEach((dropdown) => {
  const trigger = dropdown.querySelector('.dropdown-trigger, span, a');
  const menu = dropdown.querySelector('.dropdown-menu');
  if (!trigger || !menu) return;

  trigger.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();

    document.querySelectorAll('.nav-dropdown.open').forEach((openDropdown) => {
      if (openDropdown !== dropdown) {
        openDropdown.classList.remove('open');
      }
    });

    dropdown.classList.toggle('open');
  });

  menu.addEventListener('click', (event) => {
    event.stopPropagation();
  });
});

document.addEventListener('click', () => {
  document.querySelectorAll('.nav-dropdown.open').forEach((dropdown) => {
    dropdown.classList.remove('open');
  });
});

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


document.addEventListener('click', async (event) => {
  const link = event.target.closest('[data-currency-code]');
  if (!link) return;
  event.preventDefault();

  const fallbackHref = link.getAttribute('href') || '/';
  const formData = new FormData();
  formData.append('csrf_token', getCsrfToken());
  formData.append('currency_code', link.dataset.currencyCode || '');

  try {
    const response = await fetch('/api/handler.php?action=set-currency', { method: 'POST', body: formData });
    if (!response.ok) {
      window.location.href = fallbackHref;
      return;
    }
    window.location.reload();
  } catch (error) {
    window.location.href = fallbackHref;
  }
});

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

      if (form.classList.contains('bank-transfer-form')) {
        const modal = form.closest('.modal');
        if (modal) {
          modal.classList.remove('open');
          modal.setAttribute('aria-hidden', 'true');
        }
        form.reset();
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
    const quantityInput = document.querySelector('[data-product-quantity]');
    const quantity = quantityInput ? Number(quantityInput.value || 1) : 1;
    const formData = new FormData();
    formData.append('csrf_token', getCsrfToken());
    formData.append('product_id', productId);
    formData.append('quantity', String(Math.max(1, quantity)));

    try {
      const response = await fetch('/api/handler.php?action=cart-add', {
        method: 'POST',
        body: formData,
      });
      const data = await response.json();
      if (response.ok) {
        notifySuccess(data.message || 'Sepete eklendi.');
        const cartCount = document.querySelector('[data-cart-count]');
        if (cartCount && typeof data.cart_count === 'number') {
          cartCount.textContent = String(data.cart_count);
        }
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
      const cartCount = document.querySelector('[data-cart-count]');
      if (cartCount && typeof data.cart_count === 'number') {
        cartCount.textContent = String(data.cart_count);
      }
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

const bankTransferModal = document.getElementById('bankTransferModal');
if (bankTransferModal) {
  const closeModal = () => {
    bankTransferModal.classList.remove('open');
    bankTransferModal.setAttribute('aria-hidden', 'true');
  };

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-bank-transfer-open]');
    if (!button) return;
    const orderId = button.dataset.orderId;
    const orderInput = bankTransferModal.querySelector('input[name="order_id"]');
    if (orderInput) {
      orderInput.value = orderId || '';
    }
    const orderLabel = bankTransferModal.querySelector('[data-order-label]');
    if (orderLabel) {
      orderLabel.textContent = orderId ? `#${orderId}` : '—';
    }
    bankTransferModal.classList.add('open');
    bankTransferModal.setAttribute('aria-hidden', 'false');
  });

  bankTransferModal.addEventListener('click', (event) => {
    if (event.target === bankTransferModal) {
      closeModal();
    }
  });

  bankTransferModal.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', closeModal);
  });
}

document.addEventListener('click', async (event) => {
  const button = event.target.closest('[data-share]');
  if (!button) return;
  const shareData = {
    title: document.title,
    url: window.location.href,
  };
  if (navigator.share) {
    try {
      await navigator.share(shareData);
      notifySuccess('Paylaşım hazırlandı.');
    } catch (error) {
      notifyError('Paylaşım iptal edildi.');
    }
  } else if (navigator.clipboard) {
    try {
      await navigator.clipboard.writeText(shareData.url);
      notifySuccess('Link kopyalandı.');
    } catch (error) {
      notifyError('Link kopyalanamadı.');
    }
  } else {
    notifyError('Paylaşım desteklenmiyor.');
  }
});

const reviewsContainer = document.getElementById('reviewsContainer');
const reviewsPagination = document.getElementById('reviewsPagination');
const reviewsSortSelect = document.querySelector('[data-review-sort-select]');
if (reviewsContainer && reviewsPagination) {
  const loadReviews = async ({ page = '1', sort = reviewsContainer.dataset.reviewSort || 'top' } = {}) => {
    const productId = reviewsContainer.dataset.productId;
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
        reviewsPagination.innerHTML = data.pagination || '';
        reviewsContainer.dataset.reviewSort = sort;
      } else {
        notifyError(data.message || 'Yorumlar yüklenemedi.');
      }
    } catch (error) {
      notifyError('Sunucuya ulaşılamadı.');
    }
  };

  reviewsPagination.addEventListener('click', (event) => {
    const button = event.target.closest('[data-review-page]');
    if (!button) return;
    const page = button.dataset.reviewPage;
    loadReviews({ page });
  });

  if (reviewsSortSelect) {
    reviewsSortSelect.addEventListener('change', () => {
      loadReviews({ page: '1', sort: reviewsSortSelect.value });
    });
  }
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

const productQuantityInput = document.querySelector('[data-product-quantity]');
if (productQuantityInput) {
  const priceEl = document.querySelector('[data-product-price]');
  const checkoutLink = document.querySelector('[data-checkout-link]');
  const unitPrice = priceEl ? Number(priceEl.dataset.unitPrice || 0) : 0;
  const unitOldPrice = priceEl ? Number(priceEl.dataset.unitOldPrice || 0) : 0;
  const currencySymbol = priceEl?.dataset.currencySymbol || '₺';
  const currencyCode = priceEl?.dataset.currencyCode || '';

  const formatPrice = (amount) => {
    const formattedAmount = amount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return currencyCode ? `${formattedAmount} ${currencySymbol} (${currencyCode})` : `${formattedAmount} ${currencySymbol}`;
  };

  const updateProductTotal = () => {
    const qty = Math.max(1, Number(productQuantityInput.value || 1));
    if (priceEl) {
      const newPriceEl = priceEl.querySelector('[data-price-new]');
      if (newPriceEl) {
        newPriceEl.textContent = formatPrice(unitPrice * qty);
      }
      const oldPriceEl = priceEl.querySelector('[data-price-old]');
      if (oldPriceEl) {
        oldPriceEl.textContent = formatPrice(unitOldPrice * qty);
      }
    }
    if (checkoutLink) {
      const url = new URL(checkoutLink.href, window.location.origin);
      url.searchParams.set('qty', String(qty));
      checkoutLink.href = url.pathname + url.search;
    }
  };
  productQuantityInput.addEventListener('input', updateProductTotal);
  updateProductTotal();
}

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
