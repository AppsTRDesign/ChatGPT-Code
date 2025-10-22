(function () {
    const hasSwal = () => typeof window !== 'undefined' && typeof window.Swal !== 'undefined';

    const showAlert = (icon, title, text) => {
        if (hasSwal()) {
            return window.Swal.fire({
                icon,
                title,
                text,
                confirmButtonText: 'Tamam'
            });
        }
        window.alert(text);
        return Promise.resolve();
    };

    const showSuccess = (message) => showAlert('success', 'Başarılı', message || 'İşlem başarıyla tamamlandı.');
    const showError = (message) => showAlert('error', 'Hata', message || 'İşlem sırasında hata oluştu.');

    const sendAjax = async (url, { method = 'POST', body, headers = {} } = {}) => {
        const requestHeaders = Object.assign(
            {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            headers
        );

        let requestBody = body;
        if (body instanceof URLSearchParams) {
            requestHeaders['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        }

        const response = await fetch(url, {
            method,
            body: requestBody,
            headers: requestHeaders,
            credentials: 'same-origin'
        });

        let payload;
        try {
            payload = await response.json();
        } catch (error) {
            throw new Error('Sunucudan geçerli bir yanıt alınamadı.');
        }

        if (!response.ok || payload.success === false) {
            const err = new Error(payload.message || 'İşlem sırasında hata oluştu.');
            err.payload = payload;
            throw err;
        }

        return payload;
    };

    const handleRedirect = (payload) => {
        if (payload && payload.redirect) {
            window.location.href = payload.redirect;
            return true;
        }
        return false;
    };

    document.addEventListener('DOMContentLoaded', () => {
        const bodyError = document.body && document.body.dataset ? document.body.dataset.error : '';
        if (bodyError) {
            showError(bodyError);
        }

        document.querySelectorAll('form[data-ajax="true"]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submitButtons = Array.from(form.querySelectorAll('[type="submit"]'));
                submitButtons.forEach((btn) => (btn.disabled = true));

                try {
                    const action = form.getAttribute('action') || window.location.href;
                    const method = (form.getAttribute('method') || 'POST').toUpperCase();
                    let url = action;
                    let body;

                    if (method === 'GET') {
                        const params = new URLSearchParams(new FormData(form));
                        url += (url.includes('?') ? '&' : '?') + params.toString();
                    } else {
                        body = new FormData(form);
                    }

                    const payload = await sendAjax(url, { method, body });
                    await showSuccess(payload.message);
                    if (!handleRedirect(payload)) {
                        if (form.dataset.reset === 'true') {
                            form.reset();
                        } else {
                            window.location.reload();
                        }
                    }
                } catch (error) {
                    await showError(error.message);
                    const redirectTarget = error.payload && error.payload.redirect;
                    if (redirectTarget) {
                        window.location.href = redirectTarget;
                    }
                } finally {
                    submitButtons.forEach((btn) => (btn.disabled = false));
                }
            });
        });

        document.querySelectorAll('.delete-button').forEach((button) => {
            button.addEventListener('click', async () => {
                const url = button.dataset.deleteUrl;
                if (!url) {
                    return;
                }
                const confirmTitle = button.dataset.confirmTitle || 'Emin misiniz?';
                const confirmText = button.dataset.confirmText || 'Bu kayıt kalıcı olarak silinecektir.';

                const confirmed = await (hasSwal()
                    ? window.Swal.fire({
                          icon: 'warning',
                          title: confirmTitle,
                          text: confirmText,
                          showCancelButton: true,
                          confirmButtonText: 'Evet, sil',
                          cancelButtonText: 'Vazgeç'
                      }).then((result) => result.isConfirmed)
                    : Promise.resolve(window.confirm(confirmText)));

                if (!confirmed) {
                    return;
                }

                button.disabled = true;
                try {
                    const id = button.dataset.id || '';
                    const payload = await sendAjax(url, {
                        method: 'POST',
                        body: new URLSearchParams(id ? { id } : {})
                    });
                    await showSuccess(payload.message);
                    if (!handleRedirect(payload)) {
                        window.location.reload();
                    }
                } catch (error) {
                    await showError(error.message);
                } finally {
                    button.disabled = false;
                }
            });
        });
    });
})();
