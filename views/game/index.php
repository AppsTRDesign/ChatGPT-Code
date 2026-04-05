<?php declare(strict_types=1);
ob_start();
$player = $state['player'];
?>
<div class="container py-3 py-md-5">
    <header class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1"><i class="fa-solid fa-landmark-flag me-2"></i><?= htmlspecialchars($player['nation_name'] ?? 'Noa Republic', ENT_QUOTES, 'UTF-8') ?></h1>
            <small class="text-secondary">Siyaset • Savaş • Strateji</small>
        </div>
        <a href="/admin" class="btn btn-outline-light btn-sm"><i class="bi bi-shield-lock"></i> Admin</a>
    </header>

    <section class="row g-3" id="stateCards">
        <div class="col-6 col-lg-3"><div class="stat-card"><i class="bi bi-cash-stack"></i><p>Hazine</p><h2 id="treasury"><?= (int) ($player['treasury'] ?? 0) ?></h2></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><i class="bi bi-people"></i><p>Nüfus</p><h2 id="population"><?= (int) ($player['population'] ?? 0) ?></h2></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><i class="fa-solid fa-person-rifle"></i><p>Asker</p><h2 id="soldiers"><?= (int) ($player['soldiers'] ?? 0) ?></h2></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><i class="bi bi-megaphone"></i><p>Nüfuz</p><h2 id="influence"><?= (int) ($player['influence'] ?? 0) ?></h2></div></div>
    </section>

    <section class="row g-3 mt-1">
        <div class="col-12 col-lg-6">
            <div class="card panel">
                <div class="card-body">
                    <h2 class="h5"><i class="bi bi-clipboard2-pulse"></i> Hızlı Eylemler</h2>
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-primary action-btn" data-action="collect"><i class="bi bi-coin"></i> Vergi Topla</button>
                        <div class="input-group">
                            <input id="trainAmount" class="form-control" type="number" min="1" max="250" value="10">
                            <button class="btn btn-warning action-btn" data-action="train"><i class="fa-solid fa-helmet-un"></i> Asker Eğit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card panel">
                <div class="card-body">
                    <h2 class="h5"><i class="bi bi-phone"></i> API Hazır</h2>
                    <p class="small text-secondary mb-1">Mobil client için JSON endpoint:</p>
                    <code>/api/state</code><br>
                    <code>/api/action/train</code><br>
                    <code>/api/action/collect</code>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="appToast" class="toast text-bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastBody">Hazır.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = ($config['app_name'] ?? 'Noa Political Wars') . ' - Oyun';
require base_path('views/layouts/base.php');
