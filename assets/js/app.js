const navToggle = document.getElementById('navToggle');
const mainNav = document.getElementById('mainNav');
if (navToggle && mainNav) {
  navToggle.addEventListener('click', () => {
    mainNav.classList.toggle('open');
  });
}

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
        toastr.error(data.message || 'İşlem başarısız.');
        return;
      }

      if (data.message) {
        toastr.success(data.message);
      }

      if (data.redirect) {
        window.location.href = data.redirect;
      }
    } catch (error) {
      toastr.error('Sunucuya ulaşılamadı.');
    }
  });
});
