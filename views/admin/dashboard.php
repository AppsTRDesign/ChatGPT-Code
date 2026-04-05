<?php declare(strict_types=1); $player = $state['player']; ?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fa-solid fa-screwdriver-wrench"></i> Admin Panel</h1>
        <form action="/admin/logout" method="post">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn btn-outline-danger btn-sm">Çıkış</button>
        </form>
    </div>

    <?php if (isset($_GET['toast'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string) $_GET['toast'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card"><div class="card-body">
                <h2 class="h5">Canlı Oyuncu Verisi</h2>
                <ul class="list-group">
                    <li class="list-group-item">Hazine: <strong><?= (int) ($player['treasury'] ?? 0) ?></strong></li>
                    <li class="list-group-item">Nüfus: <strong><?= (int) ($player['population'] ?? 0) ?></strong></li>
                    <li class="list-group-item">Asker: <strong><?= (int) ($player['soldiers'] ?? 0) ?></strong></li>
                    <li class="list-group-item">Nüfuz: <strong><?= (int) ($player['influence'] ?? 0) ?></strong></li>
                </ul>
            </div></div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card"><div class="card-body">
                <h2 class="h5">Oyun Ayarları</h2>
                <form action="/admin/settings" method="post" class="row g-2">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="col-12">
                        <label class="form-label">Oyun İsmi</label>
                        <input class="form-control" name="game_name" value="<?= htmlspecialchars($settings['game_name'] ?? 'Noa Political Wars', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Vergi Çarpanı</label>
                        <input class="form-control" name="tax_multiplier" type="number" step="0.1" min="0.1" max="5" value="<?= htmlspecialchars($settings['tax_multiplier'] ?? '1', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Eğitim Maliyeti</label>
                        <input class="form-control" name="training_cost" type="number" min="1" max="1000" value="<?= htmlspecialchars($settings['training_cost'] ?? '20', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12"><button class="btn btn-primary w-100">Kaydet</button></div>
                </form>
            </div></div>
        </div>
    </div>
</main>
</body>
</html>
