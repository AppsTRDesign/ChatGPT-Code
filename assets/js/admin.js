const chartCanvas = document.getElementById('orderChart');
let orderChart;
if (chartCanvas) {
  orderChart = new Chart(chartCanvas, {
    type: 'line',
    data: {
      labels: [],
      datasets: [
        {
          label: 'Sipariş Sayısı',
          data: [],
          backgroundColor: 'rgba(232, 93, 117, 0.2)',
          borderColor: '#E85D75',
          borderWidth: 2,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
      },
    },
  });
}

const refreshDashboard = async () => {
  if (!orderChart) {
    return;
  }
  const formData = new FormData();
  formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
  const response = await fetch('/api/handler.php?action=stats', {
    method: 'POST',
    body: formData,
  });
  const data = await response.json();
  if (response.ok) {
    orderChart.data.labels = data.labels || [];
    orderChart.data.datasets[0].data = data.counts || [];
    orderChart.update();
    document.querySelector('[data-summary="pending"]')?.textContent = data.summary?.pending ?? 0;
    document.querySelector('[data-summary="approved"]')?.textContent = data.summary?.approved ?? 0;
    document.querySelector('[data-summary="preparing"]')?.textContent = data.summary?.preparing ?? 0;
    document.querySelector('[data-summary="shipping"]')?.textContent = data.summary?.shipping ?? 0;
    document.querySelector('[data-summary="delivered"]')?.textContent = data.summary?.delivered ?? 0;
  }
};

refreshDashboard();

document.querySelectorAll('[data-order-status]').forEach((select) => {
  select.addEventListener('change', async (event) => {
    const orderId = event.target.dataset.orderStatus;
    const status = event.target.value;
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    formData.append('order_id', orderId);
    formData.append('status', status);

    const response = await fetch('/api/handler.php?action=order-status', {
      method: 'POST',
      body: formData,
    });
    const data = await response.json();
    if (response.ok) {
      if (window.toastr) {
        toastr.success(data.message || 'Güncellendi.');
      }
    } else if (window.toastr) {
      toastr.error(data.message || 'Güncellenemedi.');
    }
  });
});

document.querySelectorAll('[data-ajax]').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const action = form.dataset.ajax;
    const formData = new FormData(form);

    const response = await fetch(`/api/handler.php?action=${action}`, {
      method: 'POST',
      body: formData,
    });
    const data = await response.json();
    if (response.ok) {
      if (window.toastr) {
        toastr.success(data.message || 'Kaydedildi.');
      }
    } else if (window.toastr) {
      toastr.error(data.message || 'İşlem başarısız.');
    }
  });
});

document.querySelectorAll('[data-export]').forEach((button) => {
  button.addEventListener('click', () => {
    if (window.toastr) {
      toastr.info('Dışa aktarım dosyaları hazırlanıyor.');
    }
  });
});

const bindDeleteButtons = (selector, action) => {
  document.querySelectorAll(selector).forEach((button) => {
    button.addEventListener('click', async () => {
      const id = button.dataset.deleteId;
      const formData = new FormData();
      formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
      formData.append('id', id);
      const response = await fetch(`/api/handler.php?action=${action}`, {
        method: 'POST',
        body: formData,
      });
      const data = await response.json();
      if (window.toastr) {
        if (response.ok) {
          toastr.success(data.message || 'Silindi.');
          button.closest('tr')?.remove();
        } else {
          toastr.error(data.message || 'Silinemedi.');
        }
      }
    });
  });
};

document.querySelectorAll('[data-delete-product]').forEach((button) => {
  button.dataset.deleteId = button.dataset.deleteProduct;
});
document.querySelectorAll('[data-delete-page]').forEach((button) => {
  button.dataset.deleteId = button.dataset.deletePage;
});
document.querySelectorAll('[data-delete-category]').forEach((button) => {
  button.dataset.deleteId = button.dataset.deleteCategory;
});
document.querySelectorAll('[data-delete-faq]').forEach((button) => {
  button.dataset.deleteId = button.dataset.deleteFaq;
});
document.querySelectorAll('[data-delete-slider]').forEach((button) => {
  button.dataset.deleteId = button.dataset.deleteSlider;
});

bindDeleteButtons('[data-delete-product]', 'delete-product');
bindDeleteButtons('[data-delete-page]', 'delete-page');
bindDeleteButtons('[data-delete-category]', 'delete-category');
bindDeleteButtons('[data-delete-faq]', 'delete-faq');
bindDeleteButtons('[data-delete-slider]', 'delete-slider');
