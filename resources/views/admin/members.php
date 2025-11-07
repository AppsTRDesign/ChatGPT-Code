<?php ob_start(); ?>
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card glass border-0 h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-secondary mb-3">Üye Şablonları</h2>
                <form data-ajax="true" method="post" action="/admin/members/templates" class="row g-2 align-items-end">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="entity_type" value="member">
                    <div class="col-7">
                        <label class="form-label">Şablon Adı</label>
                        <input type="text" name="name" class="form-control" placeholder="VIP Üyeler">
                    </div>
                    <div class="col-5 d-grid">
                        <button type="submit" class="btn btn-outline-primary">Şablon Oluştur</button>
                    </div>
                </form>
                <div class="table-responsive mt-3">
                    <table class="table table-dark table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Ad</th>
                            <th class="text-center">Üye</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($memberTemplates as $template): ?>
                            <tr>
                                <td><?= htmlspecialchars($template['name']) ?></td>
                                <td class="text-center"><?= (int) ($memberTemplateCounts[$template['id']] ?? 0) ?></td>
                                <td class="text-end">
                                    <form data-ajax="true" method="post" action="/admin/members/templates/<?= (int) $template['id'] ?>/delete">
                                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                        <button class="btn btn-sm btn-outline-danger">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$memberTemplates): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">Henüz üye şablonu bulunmuyor.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card glass border-0 h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-secondary mb-3">Kanal / Grup Şablonları</h2>
                <form data-ajax="true" method="post" action="/admin/members/templates" class="row g-2 align-items-end">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <div class="col-md-6">
                        <label class="form-label">Şablon Adı</label>
                        <input type="text" name="name" class="form-control" placeholder="Kampanya Kanalları">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tür</label>
                        <select name="entity_type" class="form-select">
                            <option value="channel">Kanal</option>
                            <option value="group">Grup</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-outline-primary">Ekle</button>
                    </div>
                </form>
                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <h3 class="fs-6 text-muted">Kanal Şablonları</h3>
                        <div class="table-responsive">
                            <table class="table table-dark table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Ad</th>
                                    <th class="text-center">Kayıt</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($channelTemplates as $template): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($template['name']) ?></td>
                                        <td class="text-center"><?= (int) ($channelTemplateCounts[$template['id']] ?? 0) ?></td>
                                        <td class="text-end">
                                            <form data-ajax="true" method="post" action="/admin/members/templates/<?= (int) $template['id'] ?>/delete">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <button class="btn btn-sm btn-outline-danger">Sil</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$channelTemplates): ?>
                                    <tr><td colspan="3" class="text-center text-muted">Kanal şablonu yok.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h3 class="fs-6 text-muted">Grup Şablonları</h3>
                        <div class="table-responsive">
                            <table class="table table-dark table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Ad</th>
                                    <th class="text-center">Kayıt</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($groupTemplates as $template): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($template['name']) ?></td>
                                        <td class="text-center"><?= (int) ($groupTemplateCounts[$template['id']] ?? 0) ?></td>
                                        <td class="text-end">
                                            <form data-ajax="true" method="post" action="/admin/members/templates/<?= (int) $template['id'] ?>/delete">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <button class="btn btn-sm btn-outline-danger">Sil</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$groupTemplates): ?>
                                    <tr><td colspan="3" class="text-center text-muted">Grup şablonu yok.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card glass border-0 mb-4">
    <div class="card-body">
        <form data-ajax="true" method="post" action="/admin/members/discover">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Telegram Hesabı</label>
                    <select name="account_id" class="form-select">
                        <option value="">Seçiniz</option>
                        <?php foreach ($accounts as $account): ?>
                            <?php $label = $account['label'] ?: $account['phone_number']; ?>
                            <option value="<?= (int) $account['id'] ?>">
                                <?= htmlspecialchars($label . ' [' . ($account['session_status'] ?? 'bekleniyor') . ']') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kanal / Grup Kullanıcı Adı</label>
                    <input type="text" name="channel_username" class="form-control" placeholder="@kanaladi veya @grupadi">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Şablona Kaydet</label>
                    <select name="template_id" class="form-select">
                        <option value="">Opsiyonel</option>
                        <?php foreach ($memberTemplates as $template): ?>
                            <option value="<?= (int) $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-outline-primary">Üyeleri Tara</button>
                    <small class="text-muted mt-2">Aktif hesaplar ile üyeler çekilir ve seçilen şablona işlenir.</small>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card glass border-0 mb-4">
    <div class="card-body">
        <form data-ajax="true" method="post" action="/admin/members">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Telegram ID</label>
                    <input type="text" name="telegram_id" class="form-control" placeholder="123456789">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kullanıcı Adı</label>
                    <input type="text" name="username" class="form-control" placeholder="@kullanici">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Son Aktif</label>
                    <input type="text" name="last_active_at" class="form-control" placeholder="2024-05-01">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Durum</label>
                    <input type="text" name="online_status" class="form-control" placeholder="Online / 5 dk">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary mt-4">Üye Kaydet</button>
                </div>
            </div>
        </form>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="/admin/members/export" class="btn btn-outline-light">CSV Dışa Aktar</a>
            <form data-ajax="true" action="/admin/members/import" method="post" enctype="multipart/form-data" class="d-flex gap-2">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="file" name="file" class="form-control">
                <button class="btn btn-outline-primary" type="submit">İçe Aktar</button>
            </form>
        </div>
    </div>
</div>

<div class="card glass border-0 mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark align-middle mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Telegram ID</th>
                    <th>Kullanıcı Adı</th>
                    <th>Public</th>
                    <th>Kaynak Kanal</th>
                    <th>Son Aktif</th>
                    <th>Durum</th>
                    <th>Şablonlar</th>
                    <th>İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= (int) $member['id'] ?></td>
                        <td><?= htmlspecialchars($member['telegram_id']) ?></td>
                        <td><?= htmlspecialchars($member['username']) ?></td>
                        <td><?= (int) $member['is_public'] === 1 ? 'Evet' : 'Hayır' ?></td>
                        <td><?= htmlspecialchars($member['joined_from_channel']) ?></td>
                        <td><?= htmlspecialchars($member['last_active_at']) ?></td>
                        <td><?= htmlspecialchars($member['online_status']) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($memberAssignments[$member['id']] ?? [] as $assignment): ?>
                                    <form data-ajax="true" method="post" action="/admin/members/<?= (int) $member['id'] ?>/assign-template" class="d-inline">
                                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="template_id" value="<?= (int) $assignment['id'] ?>">
                                        <input type="hidden" name="action" value="detach">
                                        <button class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars($assignment['name']) ?> ×</button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                            <form data-ajax="true" method="post" action="/admin/members/<?= (int) $member['id'] ?>/assign-template" class="d-flex gap-2 mt-2">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <select name="template_id" class="form-select form-select-sm">
                                    <option value="">Şablon seç</option>
                                    <?php foreach ($memberTemplates as $template): ?>
                                        <option value="<?= (int) $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Ekle</button>
                            </form>
                        </td>
                        <td>
                            <form data-ajax="true" class="d-inline" method="post" action="/admin/members/<?= (int) $member['id'] ?>/delete">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <button class="btn btn-sm btn-outline-danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$members): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">Henüz üye kaydı yok.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card glass border-0 mb-4">
    <div class="card-body">
        <h2 class="h6 text-uppercase text-secondary mb-3">Kanal ve Grup Araması</h2>
        <form data-ajax="true" method="post" action="/admin/channels/search" class="row g-3 align-items-end">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="col-md-3">
                <label class="form-label">Telegram Hesabı</label>
                <select name="account_id" class="form-select">
                    <option value="">Seçiniz</option>
                    <?php foreach ($accounts as $account): ?>
                        <?php $label = $account['label'] ?: $account['phone_number']; ?>
                        <option value="<?= (int) $account['id'] ?>">
                            <?= htmlspecialchars($label . ' [' . ($account['session_status'] ?? 'bekleniyor') . ']') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Arama İfadesi</label>
                <input type="text" name="query" class="form-control" placeholder="eğitim, kampanya, teknoloji">
            </div>
            <div class="col-md-3">
                <label class="form-label">Şablona Kaydet</label>
                <select name="template_id" class="form-select">
                    <option value="">Opsiyonel</option>
                    <?php if ($channelTemplates): ?>
                        <optgroup label="Kanal Şablonları">
                            <?php foreach ($channelTemplates as $template): ?>
                                <option value="<?= (int) $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php if ($groupTemplates): ?>
                        <optgroup label="Grup Şablonları">
                            <?php foreach ($groupTemplates as $template): ?>
                                <option value="<?= (int) $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-outline-primary">Arama Yap</button>
                <small class="text-muted mt-2">Sonuçlar aşağıda listelenir ve kaydedilir.</small>
            </div>
        </form>
        <div id="channel-search-results" class="mt-4">
            <?php include resource_path('views/admin/partials/channel-search-results.php'); ?>
        </div>
    </div>
</div>

<div class="card glass border-0">
    <div class="card-body">
        <h2 class="h6 text-uppercase text-secondary mb-3">Kaydedilen Kanallar ve Gruplar</h2>
        <div id="saved-channel-table">
            <?php include resource_path('views/admin/partials/saved-channels-table.php'); ?>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include resource_path('views/layouts/app.php');
