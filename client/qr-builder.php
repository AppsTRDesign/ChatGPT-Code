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
            <h2 class="h4 mb-3">QR Oluştur</h2>
            <p class="text-white-50">Logo yükledikten sonra içerik türünü seçerek QR kodunuzu oluşturabilirsiniz. Her sekme, URL, metin, etkinlik veya sosyal ağ gibi senaryolar için hazır şablonlar sunar.</p>
            <form id="qrForm">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                <input type="hidden" name="logo" id="logoInput">
                <input type="hidden" name="qr_type" id="qrTypeInput" value="url">

                <div class="mb-3">
                    <label class="form-label text-uppercase small text-white-50">İçerik Türü</label>
                    <div class="qr-type-tabs">
                        <ul class="nav nav-pills flex-nowrap gap-2 overflow-auto" id="qrTypeTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="qr-tab-url" data-bs-toggle="pill" data-bs-target="#qrPaneUrl" type="button" role="tab" aria-controls="qrPaneUrl" aria-selected="true" data-type="url">URL</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-text" data-bs-toggle="pill" data-bs-target="#qrPaneText" type="button" role="tab" aria-controls="qrPaneText" aria-selected="false" data-type="text">Metin</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-email" data-bs-toggle="pill" data-bs-target="#qrPaneEmail" type="button" role="tab" aria-controls="qrPaneEmail" aria-selected="false" data-type="email">E-posta</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-phone" data-bs-toggle="pill" data-bs-target="#qrPanePhone" type="button" role="tab" aria-controls="qrPanePhone" aria-selected="false" data-type="phone">Telefon</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-sms" data-bs-toggle="pill" data-bs-target="#qrPaneSms" type="button" role="tab" aria-controls="qrPaneSms" aria-selected="false" data-type="sms">SMS</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-wifi" data-bs-toggle="pill" data-bs-target="#qrPaneWifi" type="button" role="tab" aria-controls="qrPaneWifi" aria-selected="false" data-type="wifi">Wi-Fi</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-location" data-bs-toggle="pill" data-bs-target="#qrPaneLocation" type="button" role="tab" aria-controls="qrPaneLocation" aria-selected="false" data-type="location">Konum</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-event" data-bs-toggle="pill" data-bs-target="#qrPaneEvent" type="button" role="tab" aria-controls="qrPaneEvent" aria-selected="false" data-type="event">Etkinlik</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-facebook" data-bs-toggle="pill" data-bs-target="#qrPaneFacebook" type="button" role="tab" aria-controls="qrPaneFacebook" aria-selected="false" data-type="facebook">Facebook</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-instagram" data-bs-toggle="pill" data-bs-target="#qrPaneInstagram" type="button" role="tab" aria-controls="qrPaneInstagram" aria-selected="false" data-type="instagram">Instagram</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-twitter" data-bs-toggle="pill" data-bs-target="#qrPaneTwitter" type="button" role="tab" aria-controls="qrPaneTwitter" aria-selected="false" data-type="twitter">Twitter</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-youtube" data-bs-toggle="pill" data-bs-target="#qrPaneYoutube" type="button" role="tab" aria-controls="qrPaneYoutube" aria-selected="false" data-type="youtube">YouTube</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-whatsapp" data-bs-toggle="pill" data-bs-target="#qrPaneWhatsapp" type="button" role="tab" aria-controls="qrPaneWhatsapp" aria-selected="false" data-type="whatsapp">WhatsApp</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-bitcoin" data-bs-toggle="pill" data-bs-target="#qrPaneBitcoin" type="button" role="tab" aria-controls="qrPaneBitcoin" aria-selected="false" data-type="bitcoin">Bitcoin</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-ethereum" data-bs-toggle="pill" data-bs-target="#qrPaneEthereum" type="button" role="tab" aria-controls="qrPaneEthereum" aria-selected="false" data-type="ethereum">Ethereum</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab-custom" data-bs-toggle="pill" data-bs-target="#qrPaneCustom" type="button" role="tab" aria-controls="qrPaneCustom" aria-selected="false" data-type="custom">Özel Veri</button>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="tab-content qr-type-content mb-4" id="qrTypeContent">
                    <div class="tab-pane fade show active" id="qrPaneUrl" role="tabpanel" aria-labelledby="qr-tab-url">
                        <div class="mb-3">
                            <label class="form-label">URL</label>
                            <input type="url" class="form-control" name="url" id="qrUrl" placeholder="https://ornek.com" required>
                            <small class="text-white-50">HTTP veya HTTPS bağlantısı ekleyin.</small>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneText" role="tabpanel" aria-labelledby="qr-tab-text">
                        <div class="mb-3">
                            <label class="form-label">Metin İçeriği</label>
                            <textarea class="form-control" name="text_content" id="qrText" rows="4" placeholder="Mesajınızı yazın" required></textarea>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneEmail" role="tabpanel" aria-labelledby="qr-tab-email">
                        <div class="mb-3">
                            <label class="form-label">E-posta Adresi</label>
                            <input type="email" class="form-control" name="email_address" id="qrEmailAddress" placeholder="ornek@site.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konu</label>
                            <input type="text" class="form-control" name="email_subject" id="qrEmailSubject" placeholder="Konu (isteğe bağlı)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mesaj</label>
                            <textarea class="form-control" name="email_body" id="qrEmailBody" rows="3" placeholder="Mesaj metni (isteğe bağlı)"></textarea>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPanePhone" role="tabpanel" aria-labelledby="qr-tab-phone">
                        <div class="mb-3">
                            <label class="form-label">Telefon Numarası</label>
                            <input type="tel" class="form-control" name="phone_number" id="qrPhoneNumber" placeholder="+905551112233" required>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneSms" role="tabpanel" aria-labelledby="qr-tab-sms">
                        <div class="mb-3">
                            <label class="form-label">Alıcı Numarası</label>
                            <input type="tel" class="form-control" name="sms_number" id="qrSmsNumber" placeholder="+905551112233" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mesaj</label>
                            <textarea class="form-control" name="sms_message" id="qrSmsMessage" rows="3" placeholder="Mesaj metni"></textarea>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneWifi" role="tabpanel" aria-labelledby="qr-tab-wifi">
                        <div class="mb-3">
                            <label class="form-label">Ağ Adı (SSID)</label>
                            <input type="text" class="form-control" name="wifi_ssid" id="qrWifiSsid" placeholder="NoaSoft-WiFi" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="text" class="form-control" name="wifi_password" id="qrWifiPassword" placeholder="Şifre">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifreleme</label>
                            <select class="form-select" name="wifi_encryption" id="qrWifiEncryption">
                                <option value="WPA" selected>WPA/WPA2</option>
                                <option value="WEP">WEP</option>
                                <option value="NOPASS">Şifresiz</option>
                            </select>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="qrWifiHidden" name="wifi_hidden" value="1">
                            <label class="form-check-label" for="qrWifiHidden">Ağ gizli</label>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneLocation" role="tabpanel" aria-labelledby="qr-tab-location">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Enlem</label>
                                <input type="number" step="any" class="form-control" name="location_lat" id="qrLocationLat" placeholder="41.0082" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Boylam</label>
                                <input type="number" step="any" class="form-control" name="location_lng" id="qrLocationLng" placeholder="28.9784" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Etiket</label>
                            <input type="text" class="form-control" name="location_label" id="qrLocationLabel" placeholder="NoaSoft Ofis (isteğe bağlı)">
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneEvent" role="tabpanel" aria-labelledby="qr-tab-event">
                        <div class="mb-3">
                            <label class="form-label">Etkinlik Başlığı</label>
                            <input type="text" class="form-control" name="event_title" id="qrEventTitle" placeholder="Lansman Toplantısı" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Başlangıç</label>
                                <input type="datetime-local" class="form-control" name="event_start" id="qrEventStart" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Bitiş</label>
                                <input type="datetime-local" class="form-control" name="event_end" id="qrEventEnd">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Konum</label>
                            <input type="text" class="form-control" name="event_location" id="qrEventLocation" placeholder="Adres veya toplantı linki">
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" name="event_description" id="qrEventDescription" rows="3" placeholder="Detaylar"></textarea>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneFacebook" role="tabpanel" aria-labelledby="qr-tab-facebook">
                        <div class="mb-3">
                            <label class="form-label">Facebook Bağlantısı veya Kullanıcı Adı</label>
                            <input type="text" class="form-control" name="facebook_value" id="qrFacebook" placeholder="noasoft" required>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneInstagram" role="tabpanel" aria-labelledby="qr-tab-instagram">
                        <div class="mb-3">
                            <label class="form-label">Instagram Bağlantısı veya Kullanıcı Adı</label>
                            <input type="text" class="form-control" name="instagram_value" id="qrInstagram" placeholder="noasoft" required>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneTwitter" role="tabpanel" aria-labelledby="qr-tab-twitter">
                        <div class="mb-3">
                            <label class="form-label">Twitter Bağlantısı veya Kullanıcı Adı</label>
                            <input type="text" class="form-control" name="twitter_value" id="qrTwitter" placeholder="noasoft" required>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneYoutube" role="tabpanel" aria-labelledby="qr-tab-youtube">
                        <div class="mb-3">
                            <label class="form-label">YouTube Bağlantısı</label>
                            <input type="text" class="form-control" name="youtube_value" id="qrYoutube" placeholder="https://youtube.com/@noasoft" required>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneWhatsapp" role="tabpanel" aria-labelledby="qr-tab-whatsapp">
                        <div class="mb-3">
                            <label class="form-label">Telefon Numarası</label>
                            <input type="tel" class="form-control" name="whatsapp_number" id="qrWhatsappNumber" placeholder="905551112233" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mesaj (isteğe bağlı)</label>
                            <textarea class="form-control" name="whatsapp_message" id="qrWhatsappMessage" rows="3" placeholder="Selam! Menüye göz atmak ister misiniz?"></textarea>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneBitcoin" role="tabpanel" aria-labelledby="qr-tab-bitcoin">
                        <div class="mb-3">
                            <label class="form-label">Bitcoin Adresi</label>
                            <input type="text" class="form-control" name="bitcoin_address" id="qrBitcoinAddress" placeholder="bc1q..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tutar (BTC)</label>
                            <input type="text" class="form-control" name="bitcoin_amount" id="qrBitcoinAmount" placeholder="0.01">
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneEthereum" role="tabpanel" aria-labelledby="qr-tab-ethereum">
                        <div class="mb-3">
                            <label class="form-label">Ethereum Adresi</label>
                            <input type="text" class="form-control" name="ethereum_address" id="qrEthereumAddress" placeholder="0x..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tutar (ETH)</label>
                            <input type="text" class="form-control" name="ethereum_amount" id="qrEthereumAmount" placeholder="0.5">
                        </div>
                    </div>
                    <div class="tab-pane fade" id="qrPaneCustom" role="tabpanel" aria-labelledby="qr-tab-custom">
                        <div class="mb-3">
                            <label class="form-label">Ham Veri</label>
                            <textarea class="form-control" name="custom_data" id="qrCustomData" rows="4" placeholder="Özel QR içeriğiniz" required></textarea>
                            <small class="text-white-50">vCard, MECARD veya kendi özel formatınızı girebilirsiniz.</small>
                        </div>
                    </div>
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
    const form = document.getElementById('qrForm');
    const typeInput = document.getElementById('qrTypeInput');
    const tabContainer = document.getElementById('qrTypeTabs');
    const tabContent = document.getElementById('qrTypeContent');

    const togglePaneState = (activeId) => {
        if (!tabContent) {
            return;
        }
        tabContent.querySelectorAll('.tab-pane').forEach((pane) => {
            const shouldDisable = pane.id !== activeId.replace('#', '');
            pane.querySelectorAll('input, textarea, select').forEach((field) => {
                if (shouldDisable) {
                    field.setAttribute('disabled', 'disabled');
                } else {
                    field.removeAttribute('disabled');
                }
            });
        });
    };

    if (tabContainer) {
        togglePaneState('qrPaneUrl');
        tabContainer.querySelectorAll('[data-bs-toggle="pill"]').forEach((tab) => {
            tab.addEventListener('shown.bs.tab', (event) => {
                const target = event.target.getAttribute('data-bs-target');
                const type = event.target.getAttribute('data-type');
                if (target) {
                    togglePaneState(target);
                }
                if (type && typeInput) {
                    typeInput.value = type;
                }
                if (type === 'wifi' && typeof toggleWifiPassword === 'function') {
                    setTimeout(() => toggleWifiPassword(), 0);
                }
            });
        });
    }

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

    if (!form) {
        return;
    }

    const ratioSelect = document.getElementById('aspectRatio');
    const widthInput = document.getElementById('widthInput');
    const heightInput = document.getElementById('heightInput');
    const backgroundInput = document.getElementById('backgroundInput');
    const transparentToggle = document.getElementById('transparentBackground');
    const wifiEncryption = document.getElementById('qrWifiEncryption');
    const wifiPassword = document.getElementById('qrWifiPassword');

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

    const toggleWifiPassword = () => {
        if (!wifiEncryption || !wifiPassword) {
            return;
        }
        if (wifiEncryption.value === 'NOPASS') {
            wifiPassword.removeAttribute('required');
            wifiPassword.setAttribute('disabled', 'disabled');
            wifiPassword.value = '';
        } else {
            wifiPassword.removeAttribute('disabled');
            wifiPassword.setAttribute('required', 'required');
        }
    };

    if (wifiEncryption && wifiPassword) {
        toggleWifiPassword();
        wifiEncryption.addEventListener('change', toggleWifiPassword);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(form);
        try {
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
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Bağlantı hatası', text: 'Sunucuya ulaşılamadı. Lütfen yeniden deneyin.' });
        }
    });
});
</script>
<?php require __DIR__ . '/../templates/footer.php'; ?>
