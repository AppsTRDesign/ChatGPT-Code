<?php ob_start(); ?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card p-4">
            <h5>Destek Talebi</h5>
            <form class="row g-3">
                <div class="col-12">
                    <label class="form-label">Konu</label>
                    <input type="text" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Mesaj</label>
                    <textarea class="form-control" rows="4" required></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="button">Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/base.php'; ?>
