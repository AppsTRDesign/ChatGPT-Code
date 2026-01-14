const chartCanvas = document.getElementById('orderChart');
if (chartCanvas) {
  const chartData = {
    labels: ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'],
    datasets: [
      {
        label: 'Sipariş Sayısı',
        data: [3, 6, 4, 8, 5, 9, 7],
        backgroundColor: 'rgba(232, 93, 117, 0.2)',
        borderColor: '#E85D75',
        borderWidth: 2,
        fill: true,
      },
    ],
  };

  new Chart(chartCanvas, {
    type: 'line',
    data: chartData,
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: false,
        },
      },
    },
  });
}

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
      toastr.success(data.message || 'Güncellendi.');
    } else {
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
      toastr.success(data.message || 'Kaydedildi.');
    } else {
      toastr.error(data.message || 'İşlem başarısız.');
    }
  });
});

document.querySelectorAll('[data-export]').forEach((button) => {
  button.addEventListener('click', () => {
    toastr.info('Dışa aktarım dosyaları hazırlanıyor.');
  });
});
