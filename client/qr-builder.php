<?php
require_once __DIR__ . '/../config/config.php';

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
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Ön Plan Rengi</label>
                        <input type="color" class="form-control form-control-color" name="color" value="#0d6efd">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Arka Plan Rengi</label>
                        <input type="color" class="form-control form-control-color" name="background" id="backgroundInput" value="#0b132b">
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="transparentBackground" name="transparent_background" value="1">
                            <label class="form-check-label" for="transparentBackground">Arka planı transparan yap</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Genişlik (px)</label>
                        <input type="number" class="form-control" name="width" id="widthInput" value="512" min="128" max="2048" step="16">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Yükseklik (px)</label>
                        <input type="number" class="form-control" name="height" id="heightInput" value="512" min="128" max="2048" step="16">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">En / Boy Oranı</label>
                        <select class="form-select" name="aspect_ratio" id="aspectRatio">
                            <option value="1:1" selected>1:1 (Kare)</option>
                            <option value="4:3">4:3</option>
                            <option value="3:4">3:4</option>
                            <option value="16:9">16:9</option>
                            <option value="custom">Serbest</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 mb-3">
                    <label class="form-label">Formatlar</label>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="formats[]" value="png" id="formatPng" checked>
                            <label class="form-check-label" for="formatPng">PNG</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="formats[]" value="jpg" id="formatJpg" checked>
                            <label class="form-check-label" for="formatJpg">JPG</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="formats[]" value="svg" id="formatSvg">
                            <label class="form-check-label" for="formatSvg">SVG</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Logo Yükle</label>
                    <div class="dropzone" id="logoDropzone"></div>
                    <small class="text-white-50">PNG, JPG veya SVG. Maksimum 2MB.</small>
                </div>
                <button type="submit" class="btn btn-primary w-100">QR Oluştur</button>
                <p class="text-center text-white-50 mt-3" id="remainingLabel">
                    <?php if ($remaining !== null): ?>
                        Kalan aylık limit: <?= Helpers::e($remaining) ?>
                    <?php else: ?>
                        Aktif paket limit bilgisi alınamadı.
                    <?php endif; ?>
                </p>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card p-4 text-center h-100">
            <h2 class="h4">Önizleme</h2>
            <div id="qrPreview" class="mt-4">
                <p class="text-white-50">Henüz bir QR oluşturulmadı.</p>
            </div>
            <div id="downloadButtons" class="d-flex flex-wrap gap-2 justify-content-center mt-3"></div>
        </div>
    </div>
</div>
<script>
window.addEventListener('load', () => {
    if (window.Dropzone) {
        const dropzoneElement = document.getElementById('logoDropzone');
        if (dropzoneElement) {
            try {
                const previous = Dropzone.forElement(dropzoneElement);
                if (previous) {
                    previous.destroy();
                }
            } catch (error) {}

            const dropzone = new Dropzone(dropzoneElement, {
                url: '/client/upload-logo',
                maxFiles: 1,
                acceptedFiles: 'image/png,image/jpeg,image/svg+xml',
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
        }
    }

    const form = document.getElementById('qrForm');
    if (!form) {
        return;
    }

    const ratioSelect = document.getElementById('aspectRatio');
    const widthInput = document.getElementById('widthInput');
    const heightInput = document.getElementById('heightInput');
    const backgroundInput = document.getElementById('backgroundInput');
    const transparentToggle = document.getElementById('transparentBackground');

    const syncHeight = () => {
        if (!ratioSelect || !widthInput || !heightInput) {
            return;
        }
        const ratio = ratioSelect.value;
        if (!ratio || ratio === 'custom') {
            heightInput.readOnly = false;
            heightInput.classList.remove('ratio-locked');
            return;
        }

        const [w, h] = ratio.split(':').map(Number);
        if (!w || !h) {
            heightInput.readOnly = false;
            heightInput.classList.remove('ratio-locked');
            return;
        }

        const width = parseInt(widthInput.value, 10) || 512;
        const calculated = Math.round(width * (h / w));
        heightInput.value = Math.max(128, Math.min(calculated, 2048));
        heightInput.readOnly = true;
        heightInput.classList.add('ratio-locked');
    };

    if (ratioSelect) {
        syncHeight();
        ratioSelect.addEventListener('change', syncHeight);
    }
    if (widthInput) {
        widthInput.addEventListener('input', syncHeight);
    }

    if (transparentToggle && backgroundInput) {
        const toggleBackground = () => {
            if (transparentToggle.checked) {
                backgroundInput.setAttribute('disabled', 'disabled');
                backgroundInput.classList.add('opacity-50');
            } else {
                backgroundInput.removeAttribute('disabled');
                backgroundInput.classList.remove('opacity-50');
            }
        };
        toggleBackground();
        transparentToggle.addEventListener('change', toggleBackground);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(form);
        const response = await fetch('/client/generate-qr', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        if (data.status === 'success') {
            const preview = document.getElementById('qrPreview');
            preview.innerHTML = `<img src="${data.preview}" alt="QR" class="img-fluid rounded" style="max-width:360px;">`;
            const downloadContainer = document.getElementById('downloadButtons');
            downloadContainer.innerHTML = '';
            (data.downloads || []).forEach((item) => {
                const link = document.createElement('a');
                link.href = item.data;
                link.download = `qr-code.${item.format}`;
                link.className = 'btn btn-outline-primary btn-sm px-4 py-2 fw-semibold shadow-sm';
                link.textContent = item.label || item.format.toUpperCase();
                downloadContainer.appendChild(link);
            });
            if (typeof data.remaining !== 'undefined') {
                const label = document.getElementById('remainingLabel');
                if (label) {
                    label.textContent = data.remaining === null
                        ? 'Limit bilgisi güncellenemedi.'
                        : `Kalan aylık limit: ${data.remaining}`;
                }
            }
            Swal.fire({ icon: 'success', title: 'QR kod hazır!' });
        } else {
            Swal.fire({ icon: 'error', title: data.message || 'Bir hata oluştu.' });
        }
    });
});
</script>
<?php require __DIR__ . '/../templates/footer.php'; ?>
