<section class="card" data-token-list>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">API Tokenlarım</h5>
                <small class="text-muted">Tokenleriniz ile API üzerinden bildirim gönderin.</small>
            </div>
            <button class="btn btn-theme btn-sm" data-generate-token data-url="<?= base_url('api/tokens') ?>"><i class="bi bi-plus-circle"></i> Token Oluştur</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Durum</th>
                        <th>Oluşturulma</th>
                    </tr>
                </thead>
                <tbody id="tokenTable">
                    <?php foreach ($tokens as $token): ?>
                        <tr>
                            <td class="text-break"><code><?= htmlspecialchars($token['token']) ?></code></td>
                            <td><span class="badge-soft"><?= $token['is_active'] ? 'Aktif' : 'Pasif' ?></span></td>
                            <td><?= htmlspecialchars($token['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tokens)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">Henüz token oluşturmadınız.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
