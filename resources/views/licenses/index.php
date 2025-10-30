<?php
$title = 'Lisanslar';
ob_start();
?>
<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Lisanslar</h1>
            <a href="/licenses/create" class="btn btn-primary btn-sm">Yeni Lisans</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped" id="licenses-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Ürün</th>
                    <th>Tip</th>
                    <th>Durum</th>
                    <th>Son Tarih</th>
                    <th>Koltuk</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($licenses as $license): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($license->key_public, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($license->product?->name ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($license->type, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($license->status, ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo htmlspecialchars($license->expires_at?->toDateString() ?? 'Sınırsız', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $license->seats; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$scripts[] = '$("#licenses-table").DataTable();';
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
