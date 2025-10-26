document.addEventListener('DOMContentLoaded', () => {
    const registerDemo = document.getElementById('register-demo');
    if (registerDemo) {
        registerDemo.addEventListener('click', async () => {
            registerDemo.disabled = true;
            registerDemo.textContent = 'Abonelik oluşturuluyor...';
            try {
                const response = await fetch('./client/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        endpoint: `demo-${Date.now()}@webpush.noasoft.org`,
                        device: 'desktop',
                        browser: 'edge',
                        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                        tags: ['demo', 'landing'],
                    }),
                });

                const result = await response.json();
                alert(result.message || 'İşlem tamamlandı.');
            } catch (error) {
                alert('Beklenmeyen bir hata oluştu.');
            } finally {
                registerDemo.disabled = false;
                registerDemo.textContent = 'Demo Abonelik';
            }
        });
    }
});
