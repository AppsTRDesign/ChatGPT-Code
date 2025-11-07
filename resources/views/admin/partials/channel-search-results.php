<?php
$results = $results ?? [];
?>
<?php if (!$results): ?>
    <div class="text-muted small">Henüz arama yapılmadı.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-dark table-sm align-middle mb-0">
            <thead>
            <tr>
                <th>Başlık</th>
                <th>Kullanıcı</th>
                <th>Tür</th>
                <th>Public</th>
                <th>Şablonlar</th>
                <th>Şablona Ekle</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($results as $channel): ?>
                <tr>
                    <td><?= htmlspecialchars($channel['title'] ?? '') ?></td>
                    <td><?= $channel['username'] ? '@' . htmlspecialchars($channel['username']) : '—' ?></td>
                    <td><?= htmlspecialchars($channel['type'] ?? '-') ?></td>
                    <td><?= (int) ($channel['is_public'] ?? 0) === 1 ? 'Evet' : 'Hayır' ?></td>
                    <td><?= htmlspecialchars($channel['template_names'] ?? '—') ?></td>
                    <td>
                        <form data-ajax="true" method="post" action="/admin/channels/<?= (int) $channel['id'] ?>/assign-template" class="d-flex gap-2">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <select name="template_id" class="form-select form-select-sm">
                                <option value="">Şablon seç</option>
                                <?php if (($channel['type'] ?? '') === 'group'): ?>
                                    <?php foreach ($groupTemplates as $template): ?>
                                        <option value="<?= (int) $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($channelTemplates as $template): ?>
                                        <option value="<?= (int) $template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <button class="btn btn-sm btn-outline-primary" type="submit">Ekle</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
