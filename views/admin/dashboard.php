<?php declare(strict_types=1);
$countries = $world['countries'] ?? [];
$cities = $world['cities'] ?? [];
$resources = $world['resources'] ?? [];
$cr = $world['country_resources'] ?? [];
$mapLayers = $world['map_layers'] ?? [];
$cityPois = $world['city_pois'] ?? [];
?>
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
        <h1 class="h3">Dünya Yönetimi</h1>
        <form action="/admin/logout" method="post"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"><button class="btn btn-outline-danger btn-sm">Çıkış</button></form>
    </div>
    <?php if (isset($_GET['toast'])): ?><div class="alert alert-success"><?= htmlspecialchars((string) $_GET['toast'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <div class="row g-3">
        <div class="col-12 col-lg-4"><div class="card"><div class="card-body">
            <h2 class="h6">Ülke Ekle</h2>
            <form action="/admin/world/country" method="post" class="row g-2">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-3"><input name="code" class="form-control" placeholder="TR"></div>
                <div class="col-6"><input name="name" class="form-control" placeholder="Türkiye"></div>
                <div class="col-3"><input name="flag" class="form-control" placeholder="🇹🇷"></div>
                <div class="col-12"><button class="btn btn-primary w-100">Kaydet</button></div>
            </form>
        </div></div></div>

        <div class="col-12 col-lg-4"><div class="card"><div class="card-body">
            <h2 class="h6">Şehir Ekle</h2>
            <form action="/admin/world/city" method="post" class="row g-2">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-12"><select name="country_id" class="form-select"><?php foreach ($countries as $country): ?><option value="<?= (int) $country['id'] ?>"><?= htmlspecialchars($country['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="col-12"><input name="name" class="form-control" placeholder="Şehir adı"></div>
                <div class="col-6"><input name="lat" type="number" step="0.000001" class="form-control" placeholder="lat"></div>
                <div class="col-6"><input name="lng" type="number" step="0.000001" class="form-control" placeholder="lng"></div>
                <div class="col-12"><button class="btn btn-primary w-100">Kaydet</button></div>
            </form>
        </div></div></div>

        <div class="col-12 col-lg-4"><div class="card"><div class="card-body">
            <h2 class="h6">Kaynak Dağıtımı Ekle</h2>
            <form action="/admin/world/resource" method="post" class="row g-2">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-12"><select name="country_id" class="form-select"><?php foreach ($countries as $country): ?><option value="<?= (int) $country['id'] ?>"><?= htmlspecialchars($country['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="col-12"><select name="resource_id" class="form-select"><?php foreach ($resources as $resource): ?><option value="<?= (int) $resource['id'] ?>"><?= htmlspecialchars($resource['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="col-12"><input name="daily_yield" class="form-control" type="number" min="1" placeholder="Günlük üretim"></div>
                <div class="col-12"><button class="btn btn-primary w-100">Kaydet</button></div>
            </form>
        </div></div></div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-12 col-lg-6"><div class="card"><div class="card-body">
            <h2 class="h6">Harita Katmanı Ekle/Güncelle</h2>
            <form action="/admin/world/map-layer" method="post" class="row g-2">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-12"><select name="country_id" class="form-select"><?php foreach ($countries as $country): ?><option value="<?= (int) $country['id'] ?>"><?= htmlspecialchars($country['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="col-6"><input name="layer_key" class="form-control" placeholder="influence"></div>
                <div class="col-6"><input name="color_hex" class="form-control" placeholder="#0D6EFD"></div>
                <div class="col-6"><input name="intensity" type="number" step="0.1" min="0.1" max="5" class="form-control" placeholder="1.0"></div>
                <div class="col-6"><input name="note" class="form-control" placeholder="Not"></div>
                <div class="col-12"><button class="btn btn-primary w-100">Kaydet</button></div>
            </form>
        </div></div></div>

        <div class="col-12 col-lg-6"><div class="card"><div class="card-body">
            <h2 class="h6">Şehir POI Ekle</h2>
            <form action="/admin/world/city-poi" method="post" class="row g-2">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-12"><select name="city_id" class="form-select"><?php foreach ($cities as $city): ?><option value="<?= (int) $city['id'] ?>"><?= htmlspecialchars($city['country_name'] . ' / ' . $city['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="col-4"><input name="poi_type" class="form-control" placeholder="industry"></div>
                <div class="col-8"><input name="title" class="form-control" placeholder="Başlık"></div>
                <div class="col-12"><input name="description" class="form-control" placeholder="Açıklama"></div>
                <div class="col-12"><button class="btn btn-primary w-100">Kaydet</button></div>
            </form>
        </div></div></div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-12 col-lg-6"><div class="card"><div class="card-body"><h2 class="h6">Ülkeler</h2><ul class="list-group"><?php foreach ($countries as $country): ?><li class="list-group-item"><?= htmlspecialchars($country['flag_emoji'] . ' ' . $country['name'] . ' (' . $country['code'] . ')', ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div></div></div>
        <div class="col-12 col-lg-6"><div class="card"><div class="card-body"><h2 class="h6">Şehirler</h2><ul class="list-group"><?php foreach ($cities as $city): ?><li class="list-group-item"><?= htmlspecialchars($city['country_name'] . ' / ' . $city['name'], ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div></div></div>
    </div>

    <div class="card mt-3"><div class="card-body"><h2 class="h6">Ülke Kaynak Dağılımı</h2><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Ülke</th><th>Kaynak</th><th>Günlük Üretim</th></tr></thead><tbody><?php foreach ($cr as $row): ?><tr><td><?= htmlspecialchars($row['country_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['resource_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $row['daily_yield'] ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>

    <div class="row g-3 mt-2">
        <div class="col-12 col-lg-6"><div class="card"><div class="card-body"><h2 class="h6">Harita Katmanları</h2><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Katman</th><th>Ülke</th><th>Renk</th><th>Yoğunluk</th></tr></thead><tbody><?php foreach ($mapLayers as $layer): ?><tr><td><?= htmlspecialchars($layer['layer_key'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($layer['country_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($layer['color_hex'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string) $layer['intensity'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
        <div class="col-12 col-lg-6"><div class="card"><div class="card-body"><h2 class="h6">Şehir POI Listesi</h2><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Ülke/Şehir</th><th>Tür</th><th>Başlık</th></tr></thead><tbody><?php foreach ($cityPois as $poi): ?><tr><td><?= htmlspecialchars($poi['country_name'] . ' / ' . $poi['city_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($poi['poi_type'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($poi['title'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
    </div>
</main>
</body>
</html>
