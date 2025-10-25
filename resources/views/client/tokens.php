<?php ob_start(); ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">API Tokenlarım</h5>
            <button class="btn btn-primary btn-sm" id="generateToken">Token Oluştur</button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
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
                        <td class="text-break"><code><?= $token['token'] ?></code></td>
                        <td><?= $token['is_active'] ? 'Aktif' : 'Pasif' ?></td>
                        <td><?= $token['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    document.getElementById('generateToken')?.addEventListener('click', function () {
        fetch('/api/tokens', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(resp => resp.json()).then(data => {
            if (data.token) {
                Swal.fire('Başarılı', 'Yeni token oluşturuldu', 'success');
                const tr = document.createElement('tr');
                tr.innerHTML = `<td class="text-break"><code>${data.token.token}</code></td><td>Aktif</td><td>${data.token.created_at}</td>`;
                document.getElementById('tokenTable').prepend(tr);
            }
        });
    });
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
