const baseUrl = (window.APP_BASE_URL || window.location.origin).replace(/\/+$/, '');
const withBase = (path = '') => {
    if (!path) return baseUrl;
    if (/^https?:\/\//i.test(path)) return path;
    const cleaned = String(path).replace(/^\/+/, '');
    return `${baseUrl}/${cleaned}`;
};

const strings = window.APP_I18N || {};
const t = (key, fallback = '') => {
    if (Object.prototype.hasOwnProperty.call(strings, key)) {
        return strings[key];
    }
    return fallback || key;
};

const loginForm = document.querySelector('#loginForm');
const forgotPasswordButton = document.querySelector('#forgotPassword');

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4500,
});

if (loginForm) {
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(loginForm);
        const payload = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(withBase('api/auth.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'login',
                    email: payload.email,
                    password: payload.password,
                }),
            });
            const result = await response.json();
            if (result.error) {
                throw new Error(result.message || t('auth.login_failed', 'Giriş başarısız.'));
            }

            toast.fire({ icon: 'success', title: result.message });
            setTimeout(() => {
                window.location.href = withBase('panel');
            }, 800);
        } catch (error) {
            toast.fire({ icon: 'error', title: error.message || t('messages.error_generic', 'İşlem gerçekleştirilemedi.') });
        }
    });
}

if (forgotPasswordButton) {
    forgotPasswordButton.addEventListener('click', async () => {
        const { value: email } = await Swal.fire({
            title: t('auth.reset.title', 'Şifre Sıfırlama'),
            input: 'email',
            inputLabel: t('auth.reset.prompt', 'E-posta adresinizi girin'),
            confirmButtonText: t('auth.reset.submit', 'Gönder'),
            showCancelButton: true,
            cancelButtonText: t('auth.reset.cancel', 'Vazgeç'),
            inputPlaceholder: t('auth.reset.placeholder', 'admin@noasoft.com'),
        });

        if (!email) {
            return;
        }

        try {
            const response = await fetch(withBase('api/auth.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reset', email }),
            });
            const result = await response.json();
            if (result.error) {
                throw new Error(result.message || t('auth.reset.failed', 'Şifre sıfırlanamadı.'));
            }

            Swal.fire({ icon: 'success', text: result.message });
        } catch (error) {
            Swal.fire({ icon: 'error', text: error.message || t('messages.error_generic', 'İşlem gerçekleştirilemedi.') });
        }
    });
}
