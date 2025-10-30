const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');

const handleResponse = async (response) => {
    const text = await response.text();
    let data = {};
    if (text) {
        try {
            data = JSON.parse(text);
        } catch (error) {
            console.error('JSON parse error', error, text);
            throw { error: 'Sunucudan beklenmeyen cevap alındı.' };
        }
    }
    if (!response.ok) {
        throw data.error ? data : { error: 'İşlem başarısız' };
    }
    return data;
};

loginForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = Object.fromEntries(new FormData(loginForm));
    try {
        const response = await fetch('/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await handleResponse(response);
        Swal.fire('Başarılı', data.message, 'success').then(() => {
            if (data.role === 'admin') {
                window.location.href = '/admin';
            } else {
                window.location.href = '/dashboard';
            }
        });
    } catch (error) {
        Swal.fire('Hata', error.error || 'Giriş başarısız', 'error');
    }
});

registerForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = Object.fromEntries(new FormData(registerForm));
    try {
        const response = await fetch('/auth/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await handleResponse(response);
        Swal.fire('Başarılı', data.message, 'success');
        registerForm.reset();
    } catch (error) {
        const message = error.error || Object.values(error.errors || {}).join('<br>');
        Swal.fire('Hata', message || 'Kayıt başarısız', 'error');
    }
});
