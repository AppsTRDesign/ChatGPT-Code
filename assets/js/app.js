if (window.Dropzone) {
    Dropzone.autoDiscover = false;
}

document.addEventListener('DOMContentLoaded', () => {
    const flash = document.querySelector('[data-flash-message]');
    if (flash) {
        const { type, message } = flash.dataset;
        Swal.fire({
            icon: type || 'success',
            title: message,
            confirmButtonColor: '#0d6efd',
        });
    }

    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            const message = element.dataset.confirm || 'Emin misiniz?';
            event.preventDefault();
            Swal.fire({
                title: message,
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Evet',
                cancelButtonText: 'Vazgeç'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                if (element.tagName === 'A' && element.getAttribute('href')) {
                    window.location.href = element.getAttribute('href');
                    return;
                }

                const form = element.closest('form');
                if (form) {
                    form.submit();
                }
            });
        });
    });

    if (window.Dropzone) {
        document.querySelectorAll('.dropzone[data-dropzone-url]').forEach((element) => {
            if (element.dataset.dropzoneInitialized) {
                return;
            }

            element.dataset.dropzoneInitialized = '1';
            const url = element.dataset.dropzoneUrl;
            const type = element.dataset.dropzoneType || 'logo';
            const csrf = element.dataset.dropzoneCsrf || '';
            const accepted = type === 'favicon'
                ? 'image/png,image/x-icon,image/svg+xml'
                : 'image/png,image/jpeg';

            const dz = new Dropzone(element, {
                url,
                paramName: 'file',
                maxFiles: 1,
                acceptedFiles: accepted,
                addRemoveLinks: true,
                dictDefaultMessage: 'Dosyayı sürükleyip bırakın veya tıklayın',
                timeout: 180000,
            });

            dz.on('sending', (file, xhr, formData) => {
                formData.append('type', type);
                if (csrf) {
                    formData.append('csrf_token', csrf);
                }
            });

            dz.on('success', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Görsel güncellendi',
                    confirmButtonColor: '#0d6efd',
                }).then(() => window.location.reload());
            });

            dz.on('error', (file, message) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Yükleme başarısız',
                    text: typeof message === 'string' ? message : 'Dosya yüklenemedi.',
                    confirmButtonColor: '#0d6efd',
                });
            });
        });
    }
});
