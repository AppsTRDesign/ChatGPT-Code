<?php
require_once __DIR__ . '/includes/functions.php';

$title = 'Ses Dönüştürücü | NoaSoft Converter';
$active = 'audio';
$maxFiles = (int) ns_config('upload.max_files', 5);
$maxSize = (int) ns_config('upload.max_size_mb', 2048);
include __DIR__ . '/includes/header.php';
?>
<section class="ns-tool">
    <h1>Ses Dönüştürücü</h1>
    <p>ffmpeg tabanlı altyapı ile ses dosyalarınızı farklı formatlara, bit hızlarına ve örnekleme oranlarına dönüştürün.</p>
    <form class="ns-form" id="audioForm">
        <div class="ns-grid">
            <div class="ns-field">
                <label for="preset">Hazır Ayarlar</label>
                <select id="preset" name="preset">
                    <option value="custom">Özel Ayarlar</option>
                    <option value="podcast">Podcast (MP3 - 128 kbps / 44.1 kHz)</option>
                    <option value="music_hq">Yüksek Kalite Müzik (FLAC / 48 kHz)</option>
                    <option value="voiceover">Seslendirme (WAV - 48 kHz / Mono)</option>
                    <option value="audiobook">Sesli Kitap (M4A - 64 kbps / 44.1 kHz)</option>
                </select>
            </div>
            <div class="ns-field">
                <label for="audio_format">Çıkış Formatı</label>
                <select id="audio_format" name="audio_format" data-lockable="true">
                    <option value="mp3">MP3</option>
                    <option value="aac">AAC</option>
                    <option value="wav">WAV</option>
                    <option value="flac">FLAC</option>
                    <option value="ogg">OGG</option>
                    <option value="m4a">M4A</option>
                </select>
            </div>
            <div class="ns-field">
                <label for="audio_bitrate">Bit Hızı (kbps)</label>
                <input type="number" id="audio_bitrate" name="audio_bitrate" min="32" max="512" step="16" value="192" data-lockable="true" />
            </div>
            <div class="ns-field">
                <label for="audio_samplerate">Örnekleme Oranı</label>
                <select id="audio_samplerate" name="audio_samplerate" data-lockable="true">
                    <option value="32000">32 kHz</option>
                    <option value="44100" selected>44.1 kHz</option>
                    <option value="48000">48 kHz</option>
                    <option value="96000">96 kHz</option>
                </select>
            </div>
            <div class="ns-field">
                <label for="audio_channels">Kanal</label>
                <select id="audio_channels" name="audio_channels" data-lockable="true">
                    <option value="1">Mono</option>
                    <option value="2" selected>Stereo</option>
                </select>
            </div>
            <div class="ns-field ns-col-2">
                <label for="audio_normalize">Ses Seviyesi Normalizasyonu</label>
                <select id="audio_normalize" name="audio_normalize">
                    <option value="none">Yok</option>
                    <option value="ebu_r128">EBU R128</option>
                    <option value="peak">Peak Normalize</option>
                </select>
            </div>
        </div>
    </form>
    <div class="ns-uploader">
        <div id="audioDropzone" class="dropzone"></div>
        <div id="audioSummary" class="ns-selected hidden"></div>
        <div id="resultList" class="ns-results"></div>
        <div class="ns-actions">
            <button id="convertButton" class="ns-btn ns-btn-primary" type="button">Ses Dönüştür</button>
        </div>
    </div>
</section>
<?php
include __DIR__ . '/includes/footer.php';
?>
<script src="/assets/js/converter.js"></script>
<script>
window.nsInitializeConverter({
    type: 'audio',
    dropzoneId: 'audioDropzone',
    resultListId: 'resultList',
    formId: 'audioForm',
    endpoint: '/ajax/process.php',
    convertButtonId: 'convertButton',
    convertButtonLabel: 'Ses Dönüştür',
    fileSummaryId: 'audioSummary',
    maxFiles: <?= $maxFiles ?>,
    maxFileSize: <?= $maxSize ?>,
    instructionText: 'Sürükle veya tıklayarak dosya seçin - Maks <?= $maxFiles ?> dosya',
    acceptedFiles: 'audio/*',
    setupPresets(form){
        const presetSelect = form.querySelector('#preset');
        const lockable = form.querySelectorAll('[data-lockable="true"]');
        const defaults = new Map();
        form.querySelectorAll('input, select, textarea').forEach(field => {
            defaults.set(field, field.value);
        });

        function setFieldValue(field, value){
            if (!field) return;
            if (field.tagName === 'SELECT') {
                field.value = value;
                Array.from(field.options).forEach(option => {
                    option.selected = option.value === value;
                });
            } else {
                field.value = value;
            }
        }

        function setLockState(locked){
            lockable.forEach(el => {
                el.disabled = locked;
                el.classList.toggle('ns-locked', locked);
            });
        }

        function applyPreset(value){
            presetSelect.value = value;
            if (value === 'custom') {
                defaults.forEach((defaultValue, field) => setFieldValue(field, defaultValue));
                setLockState(false);
                return;
            }

            setLockState(true);

            switch(value){
                case 'podcast':
                    setFieldValue(form.audio_format, 'mp3');
                    setFieldValue(form.audio_bitrate, '128');
                    setFieldValue(form.audio_samplerate, '44100');
                    setFieldValue(form.audio_channels, '2');
                    break;
                case 'music_hq':
                    setFieldValue(form.audio_format, 'flac');
                    setFieldValue(form.audio_bitrate, '320');
                    setFieldValue(form.audio_samplerate, '48000');
                    setFieldValue(form.audio_channels, '2');
                    break;
                case 'voiceover':
                    setFieldValue(form.audio_format, 'wav');
                    setFieldValue(form.audio_bitrate, '256');
                    setFieldValue(form.audio_samplerate, '48000');
                    setFieldValue(form.audio_channels, '1');
                    break;
                case 'audiobook':
                    setFieldValue(form.audio_format, 'm4a');
                    setFieldValue(form.audio_bitrate, '64');
                    setFieldValue(form.audio_samplerate, '44100');
                    setFieldValue(form.audio_channels, '1');
                    break;
                default:
                    setLockState(false);
            }
        }

        presetSelect.addEventListener('change', (e) => {
            applyPreset(e.target.value);
        });

        applyPreset(presetSelect.value || 'custom');

        return {
            reset(){
                presetSelect.value = 'custom';
                applyPreset('custom');
            }
        };
    }
});
</script>
