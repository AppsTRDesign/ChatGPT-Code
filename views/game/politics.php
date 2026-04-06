<?php declare(strict_types=1);
ob_start();
$state = $state ?? [];
$parties = $state['parties'] ?? [];
$election = $state['election'] ?? null;
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4 mb-0">Siyaset Merkezi (Tam Sürüm)</h1><a class="btn btn-outline-light btn-sm" href="/">Dashboard</a></div>
  <?php if ($election): ?><div class="alert alert-info">Açık seçim #<?= (int) $election['id'] ?> - Bitiş: <?= htmlspecialchars($election['ends_at'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
  <div class="card panel"><div class="card-body"><h2 class="h6">Partiler</h2><div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>Parti</th><th>Üye</th></tr></thead><tbody><?php foreach ($parties as $row): ?><tr><td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $row['member_count'] ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
</div>
<?php
$content = ob_get_clean();
$title = ($config['app_name'] ?? 'Noa Political Wars') . ' - Siyaset';
require base_path('views/layouts/base.php');
