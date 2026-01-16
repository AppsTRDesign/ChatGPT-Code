const chartCanvas = document.getElementById('orderChart');
let orderChart;
const adminNavToggle = document.getElementById('adminNavToggle');
if (adminNavToggle) {
  adminNavToggle.addEventListener('click', () => {
    document.body.classList.toggle('nav-open');
  });
}
const iconPickerModal = document.getElementById('iconPickerModal');
const iconPickerButton = document.querySelector('[data-icon-picker]');
const iconPickerClose = document.querySelector('[data-icon-close]');
const iconInput = document.getElementById('categoryIconInput');
const iconPreview = document.getElementById('categoryIconPreview');
const iconSearchInput = document.getElementById('iconSearchInput');

const closeIconPicker = () => {
  if (iconPickerModal) {
    iconPickerModal.classList.remove('open');
    iconPickerModal.setAttribute('aria-hidden', 'true');
  }
};

if (iconPickerButton && iconPickerModal) {
  iconPickerButton.addEventListener('click', () => {
    iconPickerModal.classList.add('open');
    iconPickerModal.setAttribute('aria-hidden', 'false');
  });
}

if (iconPickerClose) {
  iconPickerClose.addEventListener('click', closeIconPicker);
}

if (iconPickerModal) {
  iconPickerModal.addEventListener('click', (event) => {
    if (event.target === iconPickerModal) {
      closeIconPicker();
    }
  });
}

document.querySelectorAll('[data-icon-value]').forEach((button) => {
  button.addEventListener('click', () => {
    if (!iconInput || !iconPreview) return;
    const iconClass = button.dataset.iconValue || '';
    iconInput.value = iconClass;
    iconPreview.innerHTML = iconClass ? `<i class="${iconClass}"></i>` : '<i class="fa-regular fa-circle"></i>';
    closeIconPicker();
  });
});

if (iconSearchInput) {
  iconSearchInput.addEventListener('input', () => {
    const query = iconSearchInput.value.toLowerCase().trim();
    document.querySelectorAll('[data-icon-value]').forEach((button) => {
      const name = button.dataset.iconName?.toLowerCase() || '';
      const match = name.includes(query);
      button.style.display = match ? '' : 'none';
    });
  });
}
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

document.querySelectorAll('[data-bank-transfer-approve]').forEach((button) => {
  button.addEventListener('click', async () => {
    const notificationId = button.dataset.bankTransferApprove;
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    formData.append('notification_id', notificationId);
    const response = await fetch('/api/handler.php?action=bank-transfer-approve', {
      method: 'POST',
      body: formData,
    });
    const data = await response.json();
    if (response.ok) {
      if (window.toastr) {
        toastr.success(data.message || 'Sipariş onaylandı.');
      }
      window.location.reload();
    } else if (window.toastr) {
      toastr.error(data.message || 'İşlem başarısız.');
    }
  });
});

document.querySelectorAll('[data-bank-transfer-delete]').forEach((button) => {
  button.addEventListener('click', async () => {
    const notificationId = button.dataset.bankTransferDelete;
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    formData.append('notification_id', notificationId);
    const response = await fetch('/api/handler.php?action=bank-transfer-delete', {
      method: 'POST',
      body: formData,
    });
    const data = await response.json();
    if (response.ok) {
      if (window.toastr) {
        toastr.success(data.message || 'Bildirim silindi.');
      }
      button.closest('tr')?.remove();
    } else if (window.toastr) {
      toastr.error(data.message || 'İşlem başarısız.');
    }
  });
});

const socialForm = document.querySelector('form[data-ajax="social-link"]');
document.querySelectorAll('[data-social-edit]').forEach((button) => {
  button.addEventListener('click', () => {
    if (!socialForm) return;
    socialForm.querySelector('input[name="id"]').value = button.dataset.id || '0';
    socialForm.querySelector('input[name="label"]').value = button.dataset.label || '';
    socialForm.querySelector('input[name="url"]').value = button.dataset.url || '';
    socialForm.querySelector('input[name="icon_class"]').value = button.dataset.icon || '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
});

document.querySelectorAll('[data-social-delete]').forEach((button) => {
  button.addEventListener('click', async () => {
    const linkId = button.dataset.socialDelete;
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    formData.append('id', linkId);
    const response = await fetch('/api/handler.php?action=social-link-delete', {
      method: 'POST',
      body: formData,
    });
    const data = await response.json();
    if (response.ok) {
      if (window.toastr) {
        toastr.success(data.message || 'Silindi.');
      }
      button.closest('tr')?.remove();
    } else if (window.toastr) {
      toastr.error(data.message || 'İşlem başarısız.');
    }
  });
});

const iconModal = document.getElementById('iconPickerModal');
if (iconModal) {
  const closeIconModal = () => {
    iconModal.classList.remove('open');
    iconModal.setAttribute('aria-hidden', 'true');
  };
  const openButton = document.querySelector('[data-icon-picker-open]');
  if (openButton) {
    openButton.addEventListener('click', () => {
      iconModal.classList.add('open');
      iconModal.setAttribute('aria-hidden', 'false');
    });
  }
  iconModal.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', closeIconModal);
  });
  iconModal.addEventListener('click', (event) => {
    if (event.target === iconModal) {
      closeIconModal();
    }
  });
  iconModal.querySelectorAll('[data-icon-option]').forEach((button) => {
    button.addEventListener('click', () => {
      const icon = button.dataset.iconOption || '';
      if (socialForm) {
        socialForm.querySelector('input[name="icon_class"]').value = icon;
      }
      closeIconModal();
    });
  });
}

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
      if (action !== 'settings') {
        form.reset();
        const idField = form.querySelector('input[name="id"]');
        if (idField) {
          idField.value = '0';
        }
        if (window.tinymce) {
          tinymce.editors?.forEach((editor) => {
            editor.setContent('');
          });
        }
        if (iconInput && iconPreview) {
          iconInput.value = '';
          iconPreview.innerHTML = '<i class="fa-regular fa-circle"></i>';
        }
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

const orderModal = document.getElementById('orderModal');
const orderModalBody = document.getElementById('orderModalBody');
const orderModalClose = document.getElementById('orderModalClose');

const closeOrderModal = () => {
  if (!orderModal) return;
  orderModal.classList.remove('open');
};

if (orderModalClose) {
  orderModalClose.addEventListener('click', closeOrderModal);
}

if (orderModal) {
  orderModal.addEventListener('click', (event) => {
    if (event.target === orderModal) {
      closeOrderModal();
    }
  });
}

document.querySelectorAll('[data-order-detail]').forEach((button) => {
  button.addEventListener('click', async () => {
    if (!orderModal || !orderModalBody) return;
    const orderId = button.dataset.orderDetail;
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');
    formData.append('order_id', orderId);
    const response = await fetch('/api/handler.php?action=order-detail', {
      method: 'POST',
      body: formData,
    });
    let data = {};
    try {
      data = await response.json();
    } catch (error) {
      if (window.toastr) {
        toastr.error('Sipariş detayı okunamadı.');
      }
      return;
    }
    if (!response.ok) {
      if (window.toastr) {
        toastr.error(data.message || 'Sipariş detayı alınamadı.');
      }
      return;
    }
    const order = data.order || {};
    const items = data.items || [];
    const itemsHtml = items.map((item) => `
      <tr>
        <td>${item.name}</td>
        <td>${item.unit_price}</td>
      </tr>
    `).join('');
    orderModalBody.innerHTML = `
      <p><strong>Ad Soyad:</strong> ${order.full_name || ''}</p>
      <p><strong>E-posta:</strong> ${order.email || ''}</p>
      <p><strong>Telefon:</strong> ${order.phone || ''}</p>
      <p><strong>Adres:</strong> ${order.address || ''}</p>
      <p><strong>Sipariş Notu:</strong> ${order.order_note || '-'}</p>
      <p><strong>Durum:</strong> ${order.status_label || ''}</p>
      <table>
        <thead>
          <tr>
            <th>Ürün</th>
            <th>Birim Fiyat</th>
          </tr>
        </thead>
        <tbody>
          ${itemsHtml}
        </tbody>
      </table>
    `;
    orderModal.classList.add('open');
  });
});
