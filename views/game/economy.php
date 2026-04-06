<?php declare(strict_types=1);
ob_start();
$state = $state ?? [];
$resourceMarket = $state['resource_market'] ?? [];
$market = $state['market'] ?? [];
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Ekonomi Modülü (Tam Sürüm)</h1>
    <a class="btn btn-outline-light btn-sm" href="/">Dashboard</a>
  </div>
  <div class="row g-3">
    <div class="col-lg-6"><div class="card panel"><div class="card-body"><h2 class="h6">Kaynak Piyasa Dengesi</h2><div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>Kaynak</th><th>Fiyat</th><th>Kıtlık</th></tr></thead><tbody><?php foreach ($resourceMarket as $row): ?><tr><td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string) $row['price'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string) $row['scarcity_factor'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
    <div class="col-lg-6"><div class="card panel"><div class="card-body"><h2 class="h6">Market Akışı</h2><div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>ID</th><th>Kaynak</th><th>Satıcı</th><th>Toplam</th></tr></thead><tbody><?php foreach ($market as $row): ?><tr><td><?= (int) $row['id'] ?></td><td><?= htmlspecialchars($row['resource_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['seller_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string) ($row['gross_total'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
  </div>
</div>
<?php
$content = ob_get_clean();
$title = ($config['app_name'] ?? 'Noa Political Wars') . ' - Ekonomi';
require base_path('views/layouts/base.php');
