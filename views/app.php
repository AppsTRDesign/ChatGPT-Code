<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NoaSoft QR Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h3 mb-3">NoaSoft QR Menü</h1>
                    <p class="text-muted">Restoranınız için hızlıca QR menü oluşturun. Kayıt olun, menünüzü yönetin ve siparişleri takip edin.</p>
                    <ul class="nav nav-tabs" id="authTabs">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#login">Giriş</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#register">Kayıt</a></li>
                    </ul>
                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="login">
                            <form id="loginForm">
                                <div class="mb-3">
                                    <label class="form-label">E-posta</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Şifre</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button class="btn btn-primary w-100" type="submit">Giriş Yap</button>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="register">
                            <form id="registerForm">
                                <div class="mb-3">
                                    <label class="form-label">Ad Soyad</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">E-posta</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Şifre</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Restoran Adı</label>
                                    <input type="text" name="restaurant_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Plan</label>
                                    <select name="plan_id" class="form-select">
                                        <option value="1">Ücretsiz</option>
                                        <option value="2">Premium</option>
                                    </select>
                                </div>
                                <button class="btn btn-success w-100" type="submit">Kayıt Ol</button>
                            </form>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="/menu/demo-restoran" class="link-secondary">Demo Menü Görüntüle</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/public/js/app.js"></script>
</body>
</html>
