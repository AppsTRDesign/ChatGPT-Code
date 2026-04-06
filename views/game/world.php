<?php declare(strict_types=1);
ob_start();
$state = $state ?? [];
$countries = $state['countries'] ?? [];
$map = $state['map'] ?? ['cities' => []];
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4 mb-0">Dünya Modülü</h1><a class="btn btn-outline-light btn-sm" href="/">Dashboard</a></div>
  <div class="row g-3">
    <div class="col-lg-6"><div class="card panel"><div class="card-body"><h2 class="h6">Ülkeler</h2><ul class="list-group"><?php foreach ($countries as $country): ?><li class="list-group-item bg-transparent text-light"><?= htmlspecialchars($country['name'], ENT_QUOTES, 'UTF-8') ?> (<?= (int) $country['player_count'] ?>)</li><?php endforeach; ?></ul></div></div></div>
    <div class="col-lg-6"><div class="card panel"><div class="card-body"><h2 class="h6">Şehirler</h2><ul class="list-group"><?php foreach (($map['cities'] ?? []) as $city): ?><li class="list-group-item bg-transparent text-light"><?= htmlspecialchars($city['country_name'] . ' / ' . $city['name'], ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div></div></div>
  </div>
</div>
<?php
$content = ob_get_clean();
$title = ($config['app_name'] ?? 'Noa Political Wars') . ' - Dünya';
require base_path('views/layouts/base.php');
