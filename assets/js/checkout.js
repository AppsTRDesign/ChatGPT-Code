const goToStep = (step) => {
  document.querySelectorAll('.checkout .step').forEach((item) => {
    item.classList.toggle('active', item.dataset.step === String(step));
  });
};

document.querySelectorAll('.checkout [data-ajax="login-inline"], .checkout [data-ajax="register-inline"]').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const action = form.dataset.ajax;
    const formData = new FormData(form);

    const response = await fetch(`/api/handler.php?action=${action}`, {
      method: 'POST',
      body: formData,
    });
    let data = {};
    try {
      data = await response.json();
    } catch (error) {
      if (window.toastr) {
        toastr.error('Sunucu yanıtı okunamadı.');
      }
      return;
    }
    if (response.ok) {
      goToStep(2);
    }
    if (window.toastr) {
      response.ok ? toastr.success(data.message || 'Devam edebilirsiniz.') : toastr.error(data.message || 'İşlem başarısız.');
    }
  });
});

document.querySelectorAll('.checkout [data-ajax="checkout"]').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(form);

    const response = await fetch('/api/handler.php?action=checkout', {
      method: 'POST',
      body: formData,
    });
    let data = {};
    try {
      data = await response.json();
    } catch (error) {
      if (window.toastr) {
        toastr.error('Sunucu yanıtı okunamadı.');
      }
      return;
    }
    const result = document.getElementById('checkoutResult');
    if (response.ok && result) {
      result.innerHTML = data.html || '';
      goToStep(3);
    }
    if (window.toastr) {
      response.ok ? toastr.success(data.message || 'Sipariş hazır.') : toastr.error(data.message || 'İşlem başarısız.');
    }
  });
});

document.querySelectorAll('[data-order-summary]').forEach((summary) => {
  const unitPrice = Number(summary.dataset.unitPrice || 0);
  const quantityInput = document.querySelector('[data-quantity-input]');
  const totalEl = summary.querySelector('[data-order-total]');
  if (!quantityInput || !totalEl) return;

  const updateTotal = () => {
    const qty = Math.max(1, Number(quantityInput.value || 1));
    const total = unitPrice * qty;
    totalEl.textContent = `${total.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₺`;
  };

  quantityInput.addEventListener('input', updateTotal);
  updateTotal();
});
