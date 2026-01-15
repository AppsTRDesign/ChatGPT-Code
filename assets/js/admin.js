const chartCanvas = document.getElementById('orderChart');
let orderChart;
if (chartCanvas && window.Chart) {
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
  let data = {};
  try {
    data = await response.json();
  } catch (error) {
    if (window.toastr) {
      toastr.error('Dashboard verisi alınamadı.');
    }
    return;
  }
  if (response.ok) {
    orderChart.data.labels = data.labels || [];
    orderChart.data.datasets[0].data = data.counts || [];
    orderChart.update();
    const pending = document.querySelector('[data-summary="pending"]');
    if (pending) pending.textContent = data.summary?.pending ?? 0;
    const approved = document.querySelector('[data-summary="approved"]');
    if (approved) approved.textContent = data.summary?.approved ?? 0;
    const preparing = document.querySelector('[data-summary="preparing"]');
    if (preparing) preparing.textContent = data.summary?.preparing ?? 0;
    const shipping = document.querySelector('[data-summary="shipping"]');
    if (shipping) shipping.textContent = data.summary?.shipping ?? 0;
    const delivered = document.querySelector('[data-summary="delivered"]');
    if (delivered) delivered.textContent = data.summary?.delivered ?? 0;
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
    if (window.tinymce) {
      tinymce.triggerSave();
    }
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

const slugifyText = (value) => {
  const map = {
    Ç: 'C', Ş: 'S', Ğ: 'G', Ü: 'U', İ: 'I', Ö: 'O',
    ç: 'c', ş: 's', ğ: 'g', ü: 'u', ı: 'i', ö: 'o',
  };
  return value
    .trim()
    .split('')
    .map((char) => map[char] || char)
    .join('')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
};

const bindSlugAuto = (nameSelector, slugSelector) => {
  const nameInput = document.querySelector(nameSelector);
  const slugInput = document.querySelector(slugSelector);
  if (!nameInput || !slugInput) return;
  let lastAuto = slugifyText(nameInput.value);
  let manualEdit = slugInput.value !== '' && slugInput.value !== lastAuto;
  const syncSlug = () => {
    const next = slugifyText(nameInput.value);
    if (!manualEdit || slugInput.value === '' || slugInput.value === lastAuto) {
      slugInput.value = next;
      lastAuto = next;
      manualEdit = false;
    }
  };
  nameInput.addEventListener('input', syncSlug);
  slugInput.addEventListener('input', () => {
    manualEdit = slugInput.value !== '' && slugInput.value !== lastAuto;
  });
};

bindSlugAuto('input[name="name"]', 'input[name="slug"]');
bindSlugAuto('input[name="title"]', 'input[name="slug"]');

const orderChannel = document.querySelector('select[name="order_channel"]');
const orderLinkInput = document.querySelector('input[name="order_link"]');
const orderLinkField = orderLinkInput?.closest('label');
if (orderChannel && orderLinkField && orderLinkInput) {
  const toggleOrderLink = () => {
    const isWhatsapp = orderChannel.value === 'whatsapp';
    orderLinkField.style.display = isWhatsapp ? 'block' : 'none';
    orderLinkInput.disabled = !isWhatsapp;
  };
  orderChannel.addEventListener('change', toggleOrderLink);
  toggleOrderLink();
}

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
      let data = {};
      try {
        data = await response.json();
      } catch (error) {
        if (window.toastr) {
          toastr.error('Sunucu yanıtı okunamadı.');
        }
        return;
      }
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
