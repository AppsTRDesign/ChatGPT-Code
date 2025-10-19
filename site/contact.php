<?php
$title = 'İletişim | NoaSoft Converter';
$active = 'contact';
include __DIR__ . '/includes/header.php';
?>
<section class="ns-tool">
    <h1>Bizimle İletişime Geçin</h1>
    <p>Öneri, destek ya da iş birliği talepleriniz için formu doldurmanız yeterli.</p>
    <form class="ns-form" id="contactForm">
        <div class="ns-grid">
            <div class="ns-field">
                <label for="contact_name">Adınız</label>
                <input type="text" id="contact_name" name="name" required placeholder="Adınız Soyadınız" />
            </div>
            <div class="ns-field">
                <label for="contact_email">E-posta</label>
                <input type="email" id="contact_email" name="email" required placeholder="ornek@mail.com" />
            </div>
            <div class="ns-field">
                <label for="contact_subject">Konu</label>
                <input type="text" id="contact_subject" name="subject" required placeholder="Konu" />
            </div>
            <div class="ns-field ns-col-2">
                <label for="contact_message">Mesaj</label>
                <textarea id="contact_message" name="message" rows="5" required placeholder="Mesajınızı buraya yazın"></textarea>
            </div>
        </div>
        <div class="ns-actions">
            <button type="submit" class="ns-btn ns-btn-primary" id="contactSubmit">Gönder</button>
        </div>
    </form>
</section>
<?php
include __DIR__ . '/includes/footer.php';
?>
<script>
document.getElementById('contactForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = e.currentTarget;
    const button = document.getElementById('contactSubmit');
    button.disabled = true;
    button.textContent = 'Gönderiliyor...';

    const formData = new FormData(form);

    try {
        const response = await fetch('/ajax/contact.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            Swal.fire('Gönderildi', data.message || 'Mesajınız başarıyla iletildi.', 'success');
            form.reset();
        } else {
            Swal.fire('Hata', data.message || 'Mesaj gönderilirken bir sorun oluştu.', 'error');
        }
    } catch (error) {
        Swal.fire('Hata', 'Sunucuya erişilemedi.', 'error');
    } finally {
        button.disabled = false;
        button.textContent = 'Gönder';
    }
});
</script>
