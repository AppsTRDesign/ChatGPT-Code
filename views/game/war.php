<?php declare(strict_types=1);
ob_start();
$state = $state ?? [];
$activeWar = $state['active_war'] ?? null;
$warReports = $state['war_reports'] ?? [];
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4 mb-0">Savaş Odası (Gerçek Zamanlı Hazır)</h1><a class="btn btn-outline-light btn-sm" href="/">Dashboard</a></div>
  <?php if ($activeWar): ?><div class="alert alert-warning">Aktif savaş: <?= htmlspecialchars($activeWar['attacker_country_name'] . ' vs ' . $activeWar['defender_country_name'], ENT_QUOTES, 'UTF-8') ?></div><?php else: ?><div class="alert alert-secondary">Aktif savaş yok.</div><?php endif; ?>
  <div class="card panel"><div class="card-body"><h2 class="h6">Savaş Logları</h2><div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>Zaman</th><th>Oyuncu</th><th>Zarar</th></tr></thead><tbody><?php foreach ($warReports as $row): ?><tr><td><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['attacker_user_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $row['damage'] ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
</div>
<?php
$content = ob_get_clean();
$title = ($config['app_name'] ?? 'Noa Political Wars') . ' - Savaş';
require base_path('views/layouts/base.php');
