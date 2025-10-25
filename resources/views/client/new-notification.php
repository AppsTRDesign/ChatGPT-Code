<div class="row g-4 align-items-start" data-templates='<?= json_encode($templates, JSON_UNESCAPED_UNICODE) ?>'>
    <div class="col-xl-7">
        <div class="card p-4 shadow-sm">
            <h5 class="mb-1">Yeni Bildirim Oluştur</h5>
            <p class="text-muted mb-4">Onesignal tarzı gelişmiş hedefleme ile anlık bildirim gönderin.</p>
            <form method="post" class="row g-3" data-dropzone data-dropzone-message="Görseli sürükleyip bırakın" data-dropzone-url="<?= base_url('app/uploads') ?>">
                <div class="col-12">
                    <label class="form-label">Başlık</label>
                    <input type="text" name="title" class="form-control" required />
                </div>
                <div class="col-12">
                    <label class="form-label">Mesaj</label>
                    <textarea name="message" class="form-control" rows="3" required></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Bağlantı (Opsiyonel)</label>
                    <input type="url" name="link" class="form-control" placeholder="https://" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Bildirim Süresi</label>
                    <select class="form-select" name="duration">
                        <option value="session">Kullanıcı kapatana kadar</option>
                        <option value="1h">1 Saat</option>
                        <option value="6h">6 Saat</option>
                        <option value="24h">24 Saat</option>
                        <option value="custom">Özel Süre</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Dil Seçimi</label>
                    <select class="form-select" name="locale">
                        <option value="">Varsayılan</option>
                        <option value="tr">Türkçe</option>
                        <option value="en">İngilizce</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Gönderilecek Site</label>
                    <select class="form-select" name="site_id">
                        <option value="">Site Seçin</option>
                        <?php foreach ($sites ?? [] as $site): ?>
                            <option value="<?= $site['id'] ?>"><?= htmlspecialchars($site['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Platform / Browser / Cihaz</label>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select class="form-select" name="platform[]">
                                <option value="">Tümü</option>
                                <option value="<?= htmlspecialchars($device['platform'] ?? '') ?>"><?= htmlspecialchars($device['platform'] ?? 'Platform') ?></option>
                                <option value="ios">iOS</option>
                                <option value="android">Android</option>
                                <option value="windows">Windows</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="browser[]">
                                <option value="">Tümü</option>
                                <option value="<?= htmlspecialchars($device['browser'] ?? '') ?>"><?= htmlspecialchars($device['browser'] ?? 'Browser') ?></option>
                                <option value="chrome">Chrome</option>
                                <option value="firefox">Firefox</option>
                                <option value="safari">Safari</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="device[]">
                                <option value="">Tümü</option>
                                <option value="<?= htmlspecialchars($device['device'] ?? '') ?>"><?= htmlspecialchars($device['device'] ?? 'Device') ?></option>
                                <option value="desktop">Masaüstü</option>
                                <option value="tablet">Tablet</option>
                                <option value="smartphone">Akıllı Telefon</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Görsel</label>
                    <div class="dropzone rounded" data-dropzone-message="Ana görseli yükleyin"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Logo</label>
                    <div class="dropzone rounded" data-dropzone-message="Logo yükleyin"></div>
                </div>
                <div class="col-12">
                    <label class="form-label">Buton</label>
                    <div class="row g-2">
                        <div class="col-md-6"><input type="text" name="button[label]" class="form-control" placeholder="Buton Metni" /></div>
                        <div class="col-md-6"><input type="url" name="button[url]" class="form-control" placeholder="https://" /></div>
                    </div>
                    <small class="text-muted">Buton eklemezseniz tüm bildirim bağlantısı geçerli olur.</small>
                </div>
                <div class="col-12">
                    <label class="form-label">Şablon</label>
                    <select class="form-select" name="template_id" data-template-selector>
                        <option value="">Varsayılan Şablon</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <span class="badge-soft"><i class="bi bi-geo"></i> Geo hedefleme aktif</span>
                    <button class="btn btn-theme px-4" type="submit"><i class="bi bi-send"></i> Bildirimi Gönder</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card p-4 sticky-top" style="top: 90px;">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="mb-1">Şablon Önizleme</h5>
                    <small class="text-muted">Seçilen şablon anlık olarak yansıtılır.</small>
                </div>
                <span class="badge-soft"><i class="bi bi-phone"></i> Canlı Önizleme</span>
            </div>
            <div class="border rounded-4 p-3 bg-white template-preview" id="templatePreview">Bir şablon seçerek önizleyebilirsiniz.</div>
        </div>
    </div>
</div>
