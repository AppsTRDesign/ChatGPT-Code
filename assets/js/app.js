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
      const data = await response.json();

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

document.querySelectorAll('[data-favorite]').forEach((button) => {
  button.addEventListener('click', async () => {
    const productId = button.dataset.favorite;
    const formData = new FormData();
    const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
    formData.append('csrf_token', csrf);
    formData.append('product_id', productId);

    try {
      const response = await fetch('/api/handler.php?action=favorite', {
        method: 'POST',
        body: formData,
      });
      const data = await response.json();
      if (response.ok) {
        notifySuccess(data.message || 'Favori güncellendi.');
      } else {
        notifyError(data.message || 'Favori güncellenemedi.');
      }
    } catch (error) {
      notifyError('Sunucuya ulaşılamadı.');
    }
  });
});

document.querySelectorAll('[data-cart-add]').forEach((button) => {
  button.addEventListener('click', async () => {
    const productId = button.dataset.cartAdd;
    const formData = new FormData();
    const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
    formData.append('csrf_token', csrf);
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
    const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
    formData.append('csrf_token', csrf);
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

document.querySelectorAll('[data-review-like]').forEach((button) => {
  button.addEventListener('click', async () => {
    const reviewId = button.dataset.reviewLike;
    const formData = new FormData();
    const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
    formData.append('csrf_token', csrf);
    formData.append('review_id', reviewId);

    try {
      const response = await fetch('/api/handler.php?action=review-like', {
        method: 'POST',
        body: formData,
      });
      const data = await response.json();
      if (response.ok) {
        notifySuccess(data.message || 'Beğeni kaydedildi.');
      } else {
        notifyError(data.message || 'Beğeni kaydedilemedi.');
      }
    } catch (error) {
      notifyError('Sunucuya ulaşılamadı.');
    }
  });
});
