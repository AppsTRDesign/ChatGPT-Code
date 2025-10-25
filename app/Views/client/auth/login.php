<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row justify-content-center py-5">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-lg">
            <div class="card-body p-4">
                <h1 class="h4 text-center mb-4">Panele Giriş</h1>
                <form id="login-form" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control form-control-lg" id="username" name="username" required>
                        <div class="invalid-feedback">Kullanıcı adı zorunludur.</div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                        <div class="invalid-feedback">Şifre zorunludur.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">Giriş Yap</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
