<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="row justify-content-center py-5">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card border-0 shadow-lg">
            <div class="card-body p-4">
                <h1 class="h4 text-center mb-4">Yeni Üyelik</h1>
                <p class="text-muted small text-center">Kayıt işlemi için lütfen yöneticinizle iletişime geçin.</p>
                <form class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" disabled placeholder="Yönetici tarafından oluşturulur">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" class="form-control" disabled placeholder="Yönetici tarafından atanır">
                    </div>
                    <button type="button" class="btn btn-primary w-100" onclick="window.location='/contact'">İletişime Geç</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
