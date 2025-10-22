<section class="section">
    <div class="flex-between">
        <h2>Yeni Fatura / Fiş Oluştur</h2>
        <div class="utility-links">
            <a href="index.php?module=invoices&action=export&type=pdf">PDF İndir</a>
            <a href="index.php?module=invoices&action=export&type=excel">Excel İndir</a>
            <a href="index.php?module=invoices&action=export&type=word">Word İndir</a>
        </div>
    </div>
    <form method="post" action="index.php?module=invoices&action=create" id="invoice-form">
        <div class="grid two">
            <div>
                <label>Fatura No</label>
                <input type="text" name="invoice_no" required>
            </div>
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
                <label>Tip</label>
                <select name="type" required>
                    <option value="satis">Satış</option>
                    <option value="alis">Alış</option>
                </select>
            </div>
            <div>
                <label>Düzenleme Tarihi</label>
                <input type="date" name="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div>
                <label>Vade Tarihi</label>
                <input type="date" name="due_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div>
                <label>Para Birimi</label>
                <select name="currency">
                    <option value="TRY">TRY</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                </select>
            </div>
        </div>
        <div>
            <label>Notlar</label>
            <textarea name="notes" placeholder="Fatura notu"></textarea>
        </div>

        <h3>Kalemler</h3>
        <div id="items-container">
            <div class="grid three invoice-item">
                <div>
                    <label>Stok</label>
                    <select name="items[0][stock_item_id]">
                        <option value="">Serbest Kalem</option>
                        <?php foreach ($stockItems as $item): ?>
                            <option value="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>"><?php echo htmlspecialchars($item['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Açıklama</label>
                    <input type="text" name="items[0][description]" placeholder="Ürün açıklaması">
                </div>
                <div>
                    <label>Miktar</label>
                    <input type="number" step="0.01" name="items[0][quantity]" value="1">
                </div>
                <div>
                    <label>Birim Fiyat</label>
                    <input type="number" step="0.01" name="items[0][unit_price]" value="0">
                </div>
                <div>
                    <label>KDV %</label>
                    <input type="number" step="0.01" name="items[0][vat_rate]" value="18">
                </div>
                <div>
                    <label>Toplam</label>
                    <input type="number" step="0.01" name="items[0][total]" value="0">
                </div>
            </div>
        </div>
        <div class="utility-links">
            <a href="#" id="add-item">+ Kalem Ekle</a>
        </div>
        <div>
            <label>Fatura Toplamı</label>
            <input type="number" step="0.01" name="total" required>
        </div>
        <div>
            <input type="submit" value="Faturayı Kaydet">
        </div>
    </form>
</section>

<section class="section">
    <h2>Faturalar</h2>
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>No</th>
                <th>Cari</th>
                <th>Tip</th>
                <th>Tarih</th>
                <th>Vade</th>
                <th>Toplam</th>
                <th>Kalemler</th>
                <th>İşlemler</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><?php echo htmlspecialchars($invoice['invoice_no']); ?></td>
                    <td><?php echo htmlspecialchars($invoice['cari_name']); ?></td>
                    <td><span class="badge"><?php echo strtoupper($invoice['type']); ?></span></td>
                    <td><?php echo $invoice['issue_date']; ?></td>
                    <td><?php echo $invoice['due_date']; ?></td>
                    <td><?php echo App\Helpers::formatCurrency((float)$invoice['total'], $invoice['currency']); ?></td>
                    <td>
                        <ul class="list-inline">
                            <?php foreach ($invoice['items'] as $item): ?>
                                <li><?php echo htmlspecialchars($item['stock_name'] ?: $item['description']); ?> - <?php echo $item['quantity']; ?> x <?php echo $item['unit_price']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="index.php?module=invoices&action=delete&id=<?php echo $invoice['id']; ?>" onclick="return confirm('Fatura silinsin mi?');">Sil</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
const itemsContainer = document.getElementById('items-container');
const addItemBtn = document.getElementById('add-item');
let itemIndex = 1;
addItemBtn?.addEventListener('click', function (event) {
    event.preventDefault();
    const template = document.querySelector('.invoice-item');
    const clone = template.cloneNode(true);
    clone.querySelectorAll('input, select').forEach((input) => {
        const name = input.getAttribute('name');
        if (!name) {
            return;
        }
        const newName = name.replace(/items\[[0-9]+\]/, 'items[' + itemIndex + ']');
        input.setAttribute('name', newName);
        if (input.tagName === 'INPUT') {
            if (input.type === 'number') {
                input.value = '0';
            } else {
                input.value = '';
            }
        } else {
            input.selectedIndex = 0;
        }
    });
    itemsContainer.appendChild(clone);
    itemIndex++;
});
</script>
