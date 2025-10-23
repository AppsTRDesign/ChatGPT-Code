<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Helpers;
use App\Subscription;

Auth::requireRole('client');
$user = Auth::user();
$remaining = Subscription::usageLeft((int) $user['id']);

require __DIR__ . '/../templates/header.php';
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card p-4">
            <h2 class="h4">QR Oluştur</h2>
            <p class="text-white-50">Logo yükledikten sonra formu göndererek QR kodunuzu oluşturabilirsiniz. Dropzone ile yüklediğiniz logolar otomatik olarak merkezlenir.</p>
            <form id="qrForm">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <input type="hidden" name="logo" id="logoInput">
                <div class="mb-3">
                    <label class="form-label">İçerik</label>
                    <textarea name="data" class="form-control" rows="4" placeholder="https://..." required></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ön Plan Rengi</label>
                        <input type="color" class="form-control form-control-color" name="color" value="#0d6efd">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Arka Plan Rengi</label>
                        <input type="color" class="form-control form-control-color" name="background" value="#0b132b">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Logo Yükle</label>
                    <div class="dropzone" id="logoDropzone"></div>
                    <small class="text-white-50">PNG veya JPG. Maksimum 2MB.</small>
                </div>
                <button type="submit" class="btn btn-primary w-100">QR Oluştur</button>
                <?php if ($remaining !== null): ?>
                    <p class="text-center text-white-50 mt-3">Kalan aylık limit: <?= Helpers::e($remaining) ?></p>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card p-4 text-center">
            <h2 class="h4">Önizleme</h2>
            <div id="qrPreview" class="mt-4">
                <p class="text-white-50">Henüz bir QR oluşturulmadı.</p>
            </div>
            <a id="downloadLink" class="btn btn-outline-primary mt-3 d-none" download="qr-code.png">QR İndir</a>
        </div>
    </div>
</div>
<script>
window.addEventListener('load', () => {
    if (!window.Dropzone) {
        return;
    }

    const dropzoneElement = document.getElementById('logoDropzone');
    if (!dropzoneElement) {
        return;
    }

    let existingInstance = null;
    if (typeof Dropzone.forElement === 'function') {
        try {
            existingInstance = Dropzone.forElement(dropzoneElement);
        } catch (error) {
            existingInstance = null;
        }
    }

    if (existingInstance) {
        existingInstance.destroy();
    }

    const dropzone = new Dropzone(dropzoneElement, {
        url: '/client/upload-logo',
        maxFiles: 1,
        acceptedFiles: 'image/*',
        addRemoveLinks: true,
        dictDefaultMessage: 'Logonuzu buraya sürükleyin veya tıklayın',
        init() {
            this.on('success', (file, response) => {
                let data;
                try {
                    data = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (error) {
                    data = { status: 'error' };
                }

                if (data.status === 'success') {
                    document.getElementById('logoInput').value = data.path;
                } else {
                    Swal.fire({ icon: 'error', title: data.message || 'Logo yüklenemedi' });
                    this.removeFile(file);
                }
            });
            this.on('removedfile', () => {
                document.getElementById('logoInput').value = '';
            });
        }
    });

    document.getElementById('qrForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const response = await fetch('/client/generate-qr', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.status === 'success') {
            document.getElementById('qrPreview').innerHTML = `<img src="${data.image}" alt="QR" class="img-fluid rounded" style="max-width:320px;">`;
            const downloadLink = document.getElementById('downloadLink');
            downloadLink.href = data.image;
            downloadLink.classList.remove('d-none');
            Swal.fire({ icon: 'success', title: 'QR kod hazır!' });
        } else {
            Swal.fire({ icon: 'error', title: data.message || 'Bir hata oluştu.' });
        }
    });
});
</script>
<?php require __DIR__ . '/../templates/footer.php'; ?>
