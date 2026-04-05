<?php declare(strict_types=1);
ob_start();
$user = $state['user'];
$resources = $state['resources'] ?? [];
$market = $state['market'] ?? [];
$countries = $state['countries'] ?? [];
$topCity = $state['top_city'] ?? null;
?>
<div class="container py-3 py-md-4">
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><span class="me-1"><?= htmlspecialchars($user['flag_emoji'], ENT_QUOTES, 'UTF-8') ?></span><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></h1>
            <small class="text-secondary"><?= htmlspecialchars($user['country_name'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($user['city_name'], ENT_QUOTES, 'UTF-8') ?></small>
        </div>
        <div class="d-flex gap-2">
            <a href="/admin" class="btn btn-outline-light btn-sm"><i class="fa-solid fa-shield-halved"></i> Admin</a>
            <form method="post" action="/logout"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"><button class="btn btn-outline-danger btn-sm">Çıkış</button></form>
        </div>
    </header>

    <section class="row g-2 mb-2">
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Enerji</p><h2 id="energy"><?= (int) $user['energy'] ?></h2></div></div>
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Seviye</p><h2 id="level"><?= (int) $user['level'] ?></h2></div></div>
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Tecrübe</p><h2 id="experience"><?= (int) $user['experience'] ?></h2></div></div>
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Kuvvet</p><h2 id="strength"><?= (int) $user['strength'] ?></h2></div></div>
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Eğitim</p><h2 id="education"><?= (int) $user['education'] ?></h2></div></div>
        <div class="col-6 col-lg-2"><div class="stat-card"><p>Dayanıklılık</p><h2 id="endurance"><?= (int) $user['endurance'] ?></h2></div></div>
    </section>

    <section class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="card panel"><div class="card-body">
                <h2 class="h5">Çalışma / Savaş</h2>
                <div class="d-grid gap-2">
                    <select id="resourceKey" class="form-select">
                        <?php foreach ($resources as $r): ?>
                            <option value="<?= htmlspecialchars($r['resource_key'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary action-btn" data-action="work">300 Enerji ile Çalış</button>
                    <button class="btn btn-danger action-btn" data-action="battle">300 Enerji ile Savaş</button>
                </div>
                <div class="d-grid gap-2 mt-2">
                    <button class="btn btn-outline-info action-btn" data-action="upgrade" data-stat="strength">Kuvvet +1 (5 LP + 5 Gold)</button>
                    <button class="btn btn-outline-info action-btn" data-action="upgrade" data-stat="education">Eğitim +1 (5 LP + 5 Gold)</button>
                    <button class="btn btn-outline-info action-btn" data-action="upgrade" data-stat="endurance">Dayanıklılık +1 (5 LP + 5 Gold)</button>
                </div>
            </div></div>

            <div class="card panel mt-3"><div class="card-body">
                <h2 class="h6">Top Şehir Bonusu</h2>
                <?php if ($topCity): ?>
                    <p class="mb-0"><?= htmlspecialchars($topCity['country_name'] . ' / ' . $topCity['name'], ENT_QUOTES, 'UTF-8') ?> (Skor: <?= (int) $topCity['score'] ?>)</p>
                    <small class="text-secondary">Bu şehirde enerji %40 hızlı dolar, üretim %25 artar.</small>
                <?php else: ?>
                    <p class="mb-0">Top şehir hesaplanamadı.</p>
                <?php endif; ?>
            </div></div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card panel"><div class="card-body">
                <h2 class="h5">Kaynak Envanteri</h2>
                <div class="table-responsive"><table class="table table-dark table-sm align-middle mb-0"><thead><tr><th>Kaynak</th><th>Miktar</th><th>Birim</th><th>Taban Fiyat</th></tr></thead><tbody id="resourceTable">
                <?php foreach ($resources as $r): ?>
                    <tr><td><?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $r['quantity'] ?></td><td><?= htmlspecialchars($r['unit'], ENT_QUOTES, 'UTF-8') ?></td><td><?= number_format((float) $r['base_price'], 2, ',', '.') ?></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
            </div></div>
        </div>
    </section>

    <section class="row g-3 mt-1">
        <div class="col-12 col-xl-6">
            <div class="card panel"><div class="card-body">
                <h2 class="h5">Global Market</h2>
                <div class="row g-2 mb-2">
                    <div class="col-4"><input class="form-control" id="offerResourceId" type="number" min="1" placeholder="Kaynak ID"></div>
                    <div class="col-4"><input class="form-control" id="offerQty" type="number" min="1" placeholder="Miktar"></div>
                    <div class="col-4"><input class="form-control" id="offerPrice" type="number" min="0.01" step="0.01" placeholder="Fiyat"></div>
                    <div class="col-12"><button class="btn btn-warning w-100 action-btn" data-action="market-create">İlan Aç</button></div>
                </div>
                <div class="table-responsive"><table class="table table-dark table-sm"><thead><tr><th>ID</th><th>Kaynak</th><th>Satıcı</th><th>Miktar</th><th>Fiyat</th><th></th></tr></thead><tbody id="marketTable">
                <?php foreach ($market as $m): ?>
                    <tr><td><?= (int) $m['id'] ?></td><td><?= htmlspecialchars($m['resource_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($m['seller_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $m['quantity'] ?></td><td><?= number_format((float) $m['price_per_unit'], 2, ',', '.') ?></td><td><button class="btn btn-sm btn-success action-btn" data-action="market-buy" data-offer-id="<?= (int) $m['id'] ?>">Al</button></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
            </div></div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card panel"><div class="card-body">
                <h2 class="h5">Ulus Nüfusları</h2>
                <ul class="list-group list-group-flush">
                    <?php foreach ($countries as $c): ?>
                        <li class="list-group-item d-flex justify-content-between bg-transparent text-light"><span><?= htmlspecialchars($c['flag_emoji'] . ' ' . $c['name'], ENT_QUOTES, 'UTF-8') ?></span><strong><?= (int) $c['player_count'] ?> oyuncu</strong></li>
                    <?php endforeach; ?>
                </ul>
            </div></div>
        </div>
    </section>

    <section class="card panel mt-3">
        <div class="card-body">
            <h2 class="h5">Dünya Haritası</h2>
            <div id="worldMap" style="height:380px"></div>
            <small class="text-secondary">Şimdilik Türkiye, Almanya, Rusya ve ABD şehir dağılımı aktif.</small>
        </div>
    </section>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="appToast" class="toast text-bg-dark border-0"><div class="d-flex"><div class="toast-body" id="toastBody">Hazır.</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>
</div>

<script>
window.GAME_BOOTSTRAP = <?= json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php
$content = ob_get_clean();
$title = ($config['app_name'] ?? 'Noa Political Wars') . ' - Dashboard';
require base_path('views/layouts/base.php');
