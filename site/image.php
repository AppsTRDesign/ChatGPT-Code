<?php
require_once __DIR__ . '/includes/functions.php';

$title = 'Görsel Dönüştürücü | NoaSoft Converter';
$active = 'image';
$maxFiles = (int) ns_config('upload.max_files', 5);
$maxSize = (int) ns_config('upload.max_size_mb', 2048);
include __DIR__ . '/includes/header.php';
?>
<section class="ns-tool">
    <h1>Görsel Dönüştürücü</h1>
    <p>WebP, PNG, JPG ve GIF dahil olmak üzere popüler formatlara dönüştürün. Sosyal medya presetleri ile boyutlandırma işlemlerini hızlandırın.</p>
    <form class="ns-form" id="imageForm">
        <div class="ns-grid">
            <div class="ns-field">
                <label for="image_preset">Hazır Ayarlar</label>
                <select id="image_preset" name="preset">
                    <option value="custom">Özel Ayarlar</option>
                    <option value="instagram_post">Instagram Gönderi (1080x1080)</option>
                    <option value="instagram_story">Instagram Story (1080x1920)</option>
                    <option value="facebook_cover">Facebook Kapak (820x312)</option>
                    <option value="twitter_post">Twitter Gönderi (1200x675)</option>
                    <option value="linkedin_post">LinkedIn Gönderi (1200x627)</option>
                </select>
            </div>
            <div class="ns-field">
                <label for="image_format">Çıkış Formatı</label>
                <select id="image_format" name="image_format" data-lockable="true">
                    <option value="jpg">JPG</option>
                    <option value="png">PNG</option>
                    <option value="webp" selected>WEBP</option>
                    <option value="gif">GIF</option>
                </select>
            </div>
            <div class="ns-field">
                <label for="image_width">Genişlik (px)</label>
                <input type="number" id="image_width" name="image_width" min="32" max="8000" value="1920" data-lockable="true" />
            </div>
            <div class="ns-field">
                <label for="image_height">Yükseklik (px)</label>
                <input type="number" id="image_height" name="image_height" min="32" max="8000" value="1080" data-lockable="true" />
            </div>
            <div class="ns-field">
                <label for="image_quality">Kalite (%)</label>
                <input type="number" id="image_quality" name="image_quality" min="10" max="100" value="85" data-lockable="true" />
            </div>
            <div class="ns-field">
                <label for="image_background">Arka Plan Rengi (PNG→JPG)</label>
                <input type="text" id="image_background" name="image_background" value="#0b1220" />
            </div>
            <div class="ns-field ns-col-2">
                <label for="image_keep_aspect">Oran Koruma</label>
                <select id="image_keep_aspect" name="image_keep_aspect">
                    <option value="yes" selected>Evet</option>
                    <option value="no">Hayır</option>
                </select>
            </div>
        </div>
    </form>
    <div class="ns-uploader">
        <div id="imageDropzone" class="dropzone"></div>
        <div id="imageSummary" class="ns-selected hidden"></div>
        <div id="resultList" class="ns-results"></div>
        <div class="ns-actions">
            <button id="convertButton" class="ns-btn ns-btn-primary" type="button">Görseli Dönüştür</button>
        </div>
    </div>
</section>
<?php
include __DIR__ . '/includes/footer.php';
?>
<script src="/assets/js/converter.js"></script>
<script>
window.nsInitializeConverter({
    type: 'image',
    dropzoneId: 'imageDropzone',
    resultListId: 'resultList',
    formId: 'imageForm',
    endpoint: '/ajax/process.php',
    convertButtonId: 'convertButton',
    convertButtonLabel: 'Görseli Dönüştür',
    fileSummaryId: 'imageSummary',
    maxFiles: <?= $maxFiles ?>,
    maxFileSize: <?= $maxSize ?>,
    instructionText: 'Sürükle veya tıklayarak dosya seçin - Maks <?= $maxFiles ?> dosya',
    acceptedFiles: 'image/*',
    setupPresets(form){
        const presetSelect = form.querySelector('#image_preset');
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

        const lock = (state) => {
            lockable.forEach(el => {
                el.disabled = state;
                el.classList.toggle('ns-locked', state);
            });
        };

        const apply = (value) => {
            presetSelect.value = value;
            Array.from(presetSelect.options).forEach(option => {
                option.selected = option.value === value;
            });
            if (value === 'custom') {
                defaults.forEach((defaultValue, field) => setValue(field, defaultValue));
                lock(false);
                return;
            }

            lock(true);

            switch(value){
                case 'instagram_post':
                    setValue(form.image_format, 'jpg');
                    setValue(form.image_width, '1080');
                    setValue(form.image_height, '1080');
                    setValue(form.image_quality, '90');
                    break;
                case 'instagram_story':
                    setValue(form.image_format, 'jpg');
                    setValue(form.image_width, '1080');
                    setValue(form.image_height, '1920');
                    setValue(form.image_quality, '85');
                    break;
                case 'facebook_cover':
                    setValue(form.image_format, 'png');
                    setValue(form.image_width, '820');
                    setValue(form.image_height, '312');
                    setValue(form.image_quality, '100');
                    break;
                case 'twitter_post':
                    setValue(form.image_format, 'png');
                    setValue(form.image_width, '1200');
                    setValue(form.image_height, '675');
                    setValue(form.image_quality, '95');
                    break;
                case 'linkedin_post':
                    setValue(form.image_format, 'jpg');
                    setValue(form.image_width, '1200');
                    setValue(form.image_height, '627');
                    setValue(form.image_quality, '90');
                    break;
                default:
                    lock(false);
            }
        };

        presetSelect.addEventListener('change', (event) => apply(event.target.value));

        apply(presetSelect.value || 'custom');

        return {
            reset(){
                presetSelect.value = 'custom';
                apply('custom');
            }
        };
    }
});
</script>
