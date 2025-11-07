<?php
$channels = $channels ?? [];
$channelTemplates = $channelTemplates ?? [];
$groupTemplates = $groupTemplates ?? [];
$channelAssignments = $channelAssignments ?? [];
?>
<div class="table-responsive">
    <table class="table table-dark align-middle mb-0">
        <thead>
        <tr>
            <th>Başlık</th>
            <th>Kullanıcı</th>
            <th>Tür</th>
            <th>Public</th>
            <th>Şablonlar</th>
            <th>İşlem</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($channels as $channel): ?>
            <tr>
                <td><?= htmlspecialchars($channel['title'] ?? '') ?></td>
                <td><?= $channel['username'] ? '@' . htmlspecialchars($channel['username']) : '—' ?></td>
                <td><?= htmlspecialchars($channel['type'] ?? '-') ?></td>
                <td><?= (int) ($channel['is_public'] ?? 0) === 1 ? 'Evet' : 'Hayır' ?></td>
                <td>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($channelAssignments[$channel['id']] ?? [] as $assignment): ?>
                            <form data-ajax="true" method="post" action="/admin/channels/<?= (int) $channel['id'] ?>/assign-template" class="d-inline">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="template_id" value="<?= (int) $assignment['id'] ?>">
                                <input type="hidden" name="action" value="detach">
                                <button class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars($assignment['name']) ?> ×</button>
                            </form>
                        <?php endforeach; ?>
                        <?php if (!($channelAssignments[$channel['id']] ?? [])): ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </div>
                </td>
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
        <?php if (!$channels): ?>
            <tr>
                <td colspan="6" class="text-center text-muted">Henüz kanal veya grup kaydedilmedi.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
