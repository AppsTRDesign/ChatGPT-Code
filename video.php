<?php
require_once __DIR__ . '/includes/functions.php';

$title = 'Video Dönüştürücü | NoaSoft Converter';
$active = 'video';
$maxFiles = (int) ns_config('upload.max_files', 5);
$maxSize = (int) ns_config('upload.max_size_mb', 2048);
include __DIR__ . '/includes/header.php';
?>
<section class="ns-tool">
    <h1>Video Dönüştürücü</h1>
    <p>Sosyal medya presetleri ve gelişmiş ffmpeg parametreleriyle videolarınızı istediğiniz formatta hazırlayın.</p>
    <form class="ns-form" id="videoForm">
        <div class="ns-grid">
            <div class="ns-field">
                <label for="video_preset">Hazır Ayarlar</label>
                <select id="video_preset" name="preset">
                    <option value="custom">Özel Ayarlar</option>
                    <option value="youtube_hd">YouTube 1080p (MP4 / 30 fps)</option>
                    <option value="youtube_short">YouTube Shorts (1080x1920 / 60 fps)</option>
                    <option value="instagram_square">Instagram Square (1080x1080 / 30 fps)</option>
                    <option value="tiktok_vertical">TikTok (1080x1920 / 30 fps)</option>
                    <option value="facebook_hd">Facebook 720p (MP4 / 30 fps)</option>
                </select>
            </div>
            <div class="ns-field">
                <label for="video_format">Çıkış Formatı</label>
                <select id="video_format" name="video_format" data-lockable="true">
                    <option value="mp4">MP4 (H.264)</option>
                    <option value="mov">MOV (H.264)</option>
                    <option value="mkv">MKV (H.264)</option>
                    <option value="webm">WEBM (VP9)</option>
                    <option value="gif">GIF</option>
                </select>
            </div>
            <div class="ns-field">
                <label for="video_resolution">Çözünürlük</label>
                <select id="video_resolution" name="video_resolution" data-lockable="true">
                    <option value="3840x2160">4K UHD (3840x2160)</option>
                    <option value="1920x1080" selected>Full HD (1920x1080)</option>
                    <option value="1280x720">HD (1280x720)</option>
                    <option value="1080x1080">Instagram Square (1080x1080)</option>
                    <option value="1080x1920">Dikey (1080x1920)</option>
                </select>
            </div>
            <div class="ns-field">
                <label for="video_fps">Kare Hızı (fps)</label>
                <input type="number" id="video_fps" name="video_fps" min="10" max="120" step="1" value="30" data-lockable="true" />
            </div>
            <div class="ns-field">
                <label for="video_bitrate">Video Bit Hızı (kbps)</label>
                <input type="number" id="video_bitrate" name="video_bitrate" min="500" max="50000" step="100" value="8000" data-lockable="true" />
            </div>
            <div class="ns-field">
                <label for="audio_bitrate">Ses Bit Hızı (kbps)</label>
                <input type="number" id="audio_bitrate" name="audio_bitrate" min="64" max="320" step="16" value="192" data-lockable="true" />
            </div>
            <div class="ns-field">
                <label for="video_codec">Video Codec</label>
                <select id="video_codec" name="video_codec" data-lockable="true">
                    <option value="libx264" selected>H.264 (libx264)</option>
                    <option value="libx265">H.265 (libx265)</option>
                    <option value="libvpx-vp9">VP9 (libvpx-vp9)</option>
                    <option value="gif">GIF</option>
                </select>
            </div>
            <div class="ns-field">
                <label for="audio_codec">Ses Codec</label>
                <select id="audio_codec" name="audio_codec" data-lockable="true">
                    <option value="aac" selected>AAC</option>
                    <option value="libmp3lame">MP3 (libmp3lame)</option>
                    <option value="libopus">OPUS</option>
                    <option value="pcm_s16le">PCM (wav)</option>
                </select>
            </div>
            <div class="ns-field ns-col-2">
                <label for="video_extra">Ek FFmpeg Parametreleri</label>
                <input type="text" id="video_extra" name="video_extra" placeholder="Örn: -preset slow -crf 18" />
            </div>
        </div>
    </form>
    <div class="ns-uploader">
        <div id="videoDropzone" class="dropzone"></div>
        <div id="videoSummary" class="ns-selected hidden"></div>
        <div id="resultList" class="ns-results"></div>
        <div class="ns-actions">
            <button id="convertButton" class="ns-btn ns-btn-primary" type="button">Videoyu Dönüştür</button>
        </div>
    </div>
</section>
<?php
include __DIR__ . '/includes/footer.php';
?>
<script src="/assets/js/converter.js"></script>
<script>
window.nsInitializeConverter({
    type: 'video',
    dropzoneId: 'videoDropzone',
    resultListId: 'resultList',
    formId: 'videoForm',
    endpoint: '/ajax/process.php',
    convertButtonId: 'convertButton',
    convertButtonLabel: 'Videoyu Dönüştür',
    fileSummaryId: 'videoSummary',
    maxFiles: <?= $maxFiles ?>,
    maxFileSize: <?= $maxSize ?>,
    instructionText: 'Sürükle veya tıklayarak dosya seçin - Maks <?= $maxFiles ?> dosya',
    acceptedFiles: 'video/*',
    setupPresets(form){
        const presetSelect = form.querySelector('#video_preset');
        const lockable = form.querySelectorAll('[data-lockable="true"]');
        const defaults = new Map();
        form.querySelectorAll('input, select, textarea').forEach(field => {
            defaults.set(field, field.value);
        });

        const setValue = (field, value) => {
            if (!field) return;
            if (field.tagName === 'SELECT') {
                field.value = value;
                Array.from(field.options).forEach(opt => {
                    opt.selected = opt.value === value;
                });
            } else {
                field.value = value;
            }
        };

        const lockFields = (locked) => {
            lockable.forEach(el => {
                el.disabled = locked;
                el.classList.toggle('ns-locked', locked);
            });
        };

        const applyPreset = (value) => {
            presetSelect.value = value;
            Array.from(presetSelect.options).forEach(option => {
                option.selected = option.value === value;
            });
            if (value === 'custom') {
                defaults.forEach((defaultValue, field) => setValue(field, defaultValue));
                lockFields(false);
                return;
            }

            lockFields(true);

            switch(value){
                case 'youtube_hd':
                    setValue(form.video_format, 'mp4');
                    setValue(form.video_resolution, '1920x1080');
                    setValue(form.video_fps, '30');
                    setValue(form.video_bitrate, '12000');
                    setValue(form.audio_bitrate, '192');
                    setValue(form.video_codec, 'libx264');
                    setValue(form.audio_codec, 'aac');
                    break;
                case 'youtube_short':
                    setValue(form.video_format, 'mp4');
                    setValue(form.video_resolution, '1080x1920');
                    setValue(form.video_fps, '60');
                    setValue(form.video_bitrate, '15000');
                    setValue(form.audio_bitrate, '192');
                    setValue(form.video_codec, 'libx264');
                    setValue(form.audio_codec, 'aac');
                    break;
                case 'instagram_square':
                    setValue(form.video_format, 'mp4');
                    setValue(form.video_resolution, '1080x1080');
                    setValue(form.video_fps, '30');
                    setValue(form.video_bitrate, '8000');
                    setValue(form.audio_bitrate, '192');
                    setValue(form.video_codec, 'libx264');
                    setValue(form.audio_codec, 'aac');
                    break;
                case 'tiktok_vertical':
                    setValue(form.video_format, 'mp4');
                    setValue(form.video_resolution, '1080x1920');
                    setValue(form.video_fps, '30');
                    setValue(form.video_bitrate, '10000');
                    setValue(form.audio_bitrate, '192');
                    setValue(form.video_codec, 'libx264');
                    setValue(form.audio_codec, 'aac');
                    break;
                case 'facebook_hd':
                    setValue(form.video_format, 'mp4');
                    setValue(form.video_resolution, '1280x720');
                    setValue(form.video_fps, '30');
                    setValue(form.video_bitrate, '6000');
                    setValue(form.audio_bitrate, '160');
                    setValue(form.video_codec, 'libx264');
                    setValue(form.audio_codec, 'aac');
                    break;
                default:
                    lockFields(false);
            }
        };

        presetSelect.addEventListener('change', (event) => applyPreset(event.target.value));

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
