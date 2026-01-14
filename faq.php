<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

render_header('Sıkça Sorulan Sorular');
?>
<main class="container">
    <h1>Sıkça Sorulan Sorular</h1>
    <div class="faq">
        <details open>
            <summary>Teslimat süreleri nasıl?</summary>
            <p>Aynı gün teslimat için 12:00'ye kadar verilen siparişler aynı gün teslim edilir.</p>
        </details>
        <details>
            <summary>PayTR ile ödeme güvenli mi?</summary>
            <p>PayTR altyapısı sayesinde kart bilgileriniz güvenli bir şekilde işlenir.</p>
        </details>
        <details>
            <summary>Ürün görselleri gerçek mi?</summary>
            <p>Tüm görseller gerçek aranjmanlardan oluşur, stok durumuna göre eşdeğer ürün gönderilir.</p>
        </details>
    </div>
</main>
<?php
render_footer();
?>
