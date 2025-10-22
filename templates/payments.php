<section class="section">
    <div class="flex-between">
        <h2>Ödeme &amp; Tahsilat</h2>
        <div class="utility-links">
            <a href="index.php?module=payments&action=export&type=pdf">PDF İndir</a>
            <a href="index.php?module=payments&action=export&type=excel">Excel İndir</a>
            <a href="index.php?module=payments&action=export&type=word">Word İndir</a>
        </div>
    </div>
    <form method="post" data-ajax="true" action="index.php?module=payments&action=create">
        <div class="grid two">
            <div>
                <label>Cari</label>
                <select name="cari_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($caris as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Fatura</label>
                <select name="invoice_id">
                    <option value="">Serbest</option>
                    <?php foreach ($invoices as $inv): ?>
                        <option value="<?php echo $inv['id']; ?>"><?php echo htmlspecialchars($inv['invoice_no']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Tip</label>
                <select name="type" required>
                    <option value="tahsilat">Tahsilat</option>
                    <option value="tediye">Tediye</option>
                </select>
            </div>
            <div>
                <label>Tarih</label>
                <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div>
                <label>Tutar</label>
                <input type="number" step="0.01" name="amount" required>
            </div>
        </div>
        <div>
            <label>Not</label>
            <textarea name="notes"></textarea>
        </div>
        <div>
            <input type="submit" value="Kaydet">
        </div>
    </form>
</section>

<section class="section">
    <h2>Kayıtlı Ödemeler</h2>
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>Tarih</th>
                <th>Cari</th>
                <th>Tip</th>
                <th>Fatura</th>
                <th>Tutar</th>
                <th>Not</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $row): ?>
                <tr>
                    <td><?php echo $row['payment_date']; ?></td>
                    <td><?php echo htmlspecialchars($row['cari_name']); ?></td>
                    <td><span class="badge"><?php echo strtoupper($row['type']); ?></span></td>
                    <td><?php echo htmlspecialchars($row['invoice_no']); ?></td>
                    <td><?php echo App\Helpers::formatCurrency((float)$row['amount']); ?></td>
                    <td><?php echo htmlspecialchars($row['notes']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
