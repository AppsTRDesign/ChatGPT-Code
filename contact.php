<?php
require_once __DIR__ . '/config.php';
include __DIR__ . '/templates/header.php';
?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card card-glass p-4">
                    <h2 class="h4 mb-4">İletişim</h2>
                    <p class="text-white-50">Sorularınız, işbirliği talepleriniz veya destek ihtiyaçlarınız için formu doldurabilirsiniz.</p>
                    <form data-ajax-form action="<?= BASE_URL ?>/api/contact.php" method="post" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="contactName">Ad Soyad</label>
                                <input type="text" class="form-control" id="contactName" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="contactEmail">E-posta</label>
                                <input type="email" class="form-control" id="contactEmail" name="email" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="contactMessage">Mesajınız</label>
                                <textarea class="form-control" id="contactMessage" name="message" rows="4" required></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-gradient mt-4">Gönder</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/templates/footer.php'; ?>
