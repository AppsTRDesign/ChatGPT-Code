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
                if (result.isConfirmed) {
                    window.location.href = element.getAttribute('href');
                }
            });
        });
    });
});
