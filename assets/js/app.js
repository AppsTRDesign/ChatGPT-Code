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
