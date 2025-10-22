<section class="section">
    <h2>Sistem Ayarları</h2>
    <form method="post" action="index.php?module=settings&action=update">
        <div class="grid two">
            <div>
                <label>Firma Adı</label>
                <input type="text" name="company_name" required value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>">
            </div>
            <div>
                <label>Fatura Şablonu</label>
                <select name="invoice_template">
                    <option value="standart" <?php echo (($settings['invoice_template'] ?? '') === 'standart') ? 'selected' : ''; ?>>Standart</option>
                    <option value="minimal" <?php echo (($settings['invoice_template'] ?? '') === 'minimal') ? 'selected' : ''; ?>>Minimal</option>
                    <option value="modern" <?php echo (($settings['invoice_template'] ?? '') === 'modern') ? 'selected' : ''; ?>>Modern</option>
                </select>
            </div>
        </div>
        <div>
            <label>Adres</label>
            <textarea name="address"><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
        </div>
        <div>
            <input type="submit" value="Ayarları Kaydet">
        </div>
    </form>
</section>

<section class="section">
    <h2>Veritabanı Yedekleme</h2>
    <p>Tek tıkla MySQL verilerinizi içeren SQL dökümünü indirerek yedekleyebilirsiniz.</p>
    <a class="input-button" href="index.php?module=settings&action=backup">Yedek SQL İndir</a>
</section>
