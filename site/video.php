<?php
$title = 'Video Dönüştürücü | NoaSoft Converter';
$active = 'video';
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
                <select id="video_codec" name="video_codec">
                    <option value="libx264" selected>H.264 (libx264)</option>
                    <option value="libx265">H.265 (libx265)</option>
                    <option value="libvpx-vp9">VP9 (libvpx-vp9)</option>
                    <option value="gif">GIF</option>
                </select>
            </div>
            <div class="ns-field">
                <label for="audio_codec">Ses Codec</label>
                <select id="audio_codec" name="audio_codec">
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
    maxFiles: 5,
    maxFileSize: 2048,
    setupPresets(form){
        const presetSelect = form.querySelector('#video_preset');
        const lockable = form.querySelectorAll('[data-lockable="true"]');
        const lockFields = (locked) => lockable.forEach(el => el.disabled = locked);
        const applyPreset = (value) => {
            lockFields(value !== 'custom');
            switch(value){
                case 'youtube_hd':
                    form.video_format.value = 'mp4';
                    form.video_resolution.value = '1920x1080';
                    form.video_fps.value = '30';
                    form.video_bitrate.value = '12000';
                    form.audio_bitrate.value = '192';
                    form.video_codec.value = 'libx264';
                    form.audio_codec.value = 'aac';
                    break;
                case 'youtube_short':
                    form.video_format.value = 'mp4';
                    form.video_resolution.value = '1080x1920';
                    form.video_fps.value = '60';
                    form.video_bitrate.value = '15000';
                    form.audio_bitrate.value = '192';
                    form.video_codec.value = 'libx264';
                    form.audio_codec.value = 'aac';
                    break;
                case 'instagram_square':
                    form.video_format.value = 'mp4';
                    form.video_resolution.value = '1080x1080';
                    form.video_fps.value = '30';
                    form.video_bitrate.value = '8000';
                    form.audio_bitrate.value = '192';
                    form.video_codec.value = 'libx264';
                    form.audio_codec.value = 'aac';
                    break;
                case 'tiktok_vertical':
                    form.video_format.value = 'mp4';
                    form.video_resolution.value = '1080x1920';
                    form.video_fps.value = '30';
                    form.video_bitrate.value = '10000';
                    form.audio_bitrate.value = '192';
                    form.video_codec.value = 'libx264';
                    form.audio_codec.value = 'aac';
                    break;
                case 'facebook_hd':
                    form.video_format.value = 'mp4';
                    form.video_resolution.value = '1280x720';
                    form.video_fps.value = '30';
                    form.video_bitrate.value = '6000';
                    form.audio_bitrate.value = '160';
                    form.video_codec.value = 'libx264';
                    form.audio_codec.value = 'aac';
                    break;
                default:
                    lockFields(false);
            }
            if (value === 'custom') {
                lockFields(false);
            }
        };
        presetSelect.addEventListener('change', (event) => applyPreset(event.target.value));
        applyPreset(presetSelect.value);
    }
});
</script>
