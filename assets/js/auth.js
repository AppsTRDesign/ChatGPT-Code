const baseUrl = (window.APP_BASE_URL || window.location.origin).replace(/\/+$/, '');
const withBase = (path = '') => {
    if (!path) return baseUrl;
    if (/^https?:\/\//i.test(path)) return path;
    const cleaned = String(path).replace(/^\/+/, '');
    return `${baseUrl}/${cleaned}`;
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
                throw new Error(result.message || 'Giriş başarısız.');
            }

            toast.fire({ icon: 'success', title: result.message });
            setTimeout(() => {
                window.location.href = withBase('panel');
            }, 800);
        } catch (error) {
            toast.fire({ icon: 'error', title: error.message });
        }
    });
}

if (forgotPasswordButton) {
    forgotPasswordButton.addEventListener('click', async () => {
        const { value: email } = await Swal.fire({
            title: 'Şifre Sıfırlama',
            input: 'email',
            inputLabel: 'E-posta adresinizi girin',
            confirmButtonText: 'Gönder',
            showCancelButton: true,
            cancelButtonText: 'Vazgeç',
            inputPlaceholder: 'admin@noasoft.com',
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
                throw new Error(result.message || 'Şifre sıfırlanamadı.');
            }

            Swal.fire({ icon: 'success', text: result.message });
        } catch (error) {
            Swal.fire({ icon: 'error', text: error.message });
        }
    });
}
