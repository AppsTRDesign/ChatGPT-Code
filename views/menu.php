<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Menü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/css/styles.css" rel="stylesheet">
</head>
<body>
<div class="container py-4" id="menuApp" data-slug="<?= htmlspecialchars($slug ?? '') ?>" data-api-key="<?= htmlspecialchars($menuApiKey ?? '') ?>">
    <div class="text-center mb-4">
        <h1 id="restaurantName"></h1>
        <p class="text-muted" id="restaurantDescription"></p>
        <div class="d-flex justify-content-center gap-2" id="languageSwitcher">
            <button class="btn btn-outline-secondary btn-sm" data-lang="tr">TR</button>
            <button class="btn btn-outline-secondary btn-sm" data-lang="en">EN</button>
            <button class="btn btn-outline-secondary btn-sm" data-lang="ar">AR</button>
            <button class="btn btn-outline-secondary btn-sm" data-lang="fr">FR</button>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-8">
            <div id="menuCategories"></div>
        </div>
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header">Siparişiniz</div>
                <div class="card-body">
                    <div id="cartItems"></div>
                    <div class="d-flex justify-content-between fw-semibold mt-3">
                        <span>Toplam</span>
                        <span id="cartTotal">0</span>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Masa Numarası</label>
                        <input type="text" id="tableNumber" class="form-control" placeholder="Örn: 5">
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Notunuz</label>
                        <textarea id="customerNote" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-primary w-100 mt-3" id="submitOrder">Siparişi Gönder</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/public/js/menu.js"></script>
</body>
</html>
