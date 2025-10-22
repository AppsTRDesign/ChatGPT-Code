<section class="section">
    <h2>Banka Hesapları</h2>
    <form method="post" data-ajax="true" action="index.php?module=banks&action=create">
        <div class="grid three">
            <div>
                <label>Banka Adı</label>
                <input type="text" name="bank_name" required>
            </div>
            <div>
                <label>IBAN</label>
                <input type="text" name="iban">
            </div>
            <div>
                <label>Hesap No</label>
                <input type="text" name="account_no">
            </div>
            <div>
                <label>Başlangıç Bakiye</label>
                <input type="number" step="0.01" name="balance" value="0">
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
            <input type="submit" value="Hesap Ekle">
        </div>
    </form>
</section>

<section class="section">
    <h2>Hesap Listesi</h2>
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>Banka</th>
                <th>IBAN</th>
                <th>Hesap No</th>
                <th>Bakiye</th>
                <th>Para Birimi</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($accounts as $account): ?>
                <tr>
                    <td><?php echo htmlspecialchars($account['bank_name']); ?></td>
                    <td><?php echo htmlspecialchars($account['iban']); ?></td>
                    <td><?php echo htmlspecialchars($account['account_no']); ?></td>
                    <td><?php echo App\Helpers::formatCurrency((float)$account['balance'], $account['currency']); ?></td>
                    <td><?php echo htmlspecialchars($account['currency']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="section">
    <h2>Banka Hareketleri</h2>
    <form method="post" data-ajax="true" action="index.php?module=banks&action=flow">
        <div class="grid three">
            <div>
                <label>Hesap</label>
                <select name="bank_account_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['bank_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Tür</label>
                <select name="type" required>
                    <option value="giris">Giriş</option>
                    <option value="cikis">Çıkış</option>
                </select>
            </div>
            <div>
                <label>Tutar</label>
                <input type="number" step="0.01" name="amount" required>
            </div>
            <div>
                <label>Tarih</label>
                <input type="date" name="flow_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div>
                <label>Açıklama</label>
                <input type="text" name="description">
            </div>
        </div>
        <div>
            <input type="submit" value="Hareket Kaydet">
        </div>
    </form>
    <div class="table-scroll" style="margin-top:1.5rem;">
        <table>
            <thead>
            <tr>
                <th>Tarih</th>
                <th>Hesap</th>
                <th>Tür</th>
                <th>Tutar</th>
                <th>Açıklama</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($flows as $flow): ?>
                <tr>
                    <td><?php echo $flow['flow_date']; ?></td>
                    <td><?php echo htmlspecialchars($flow['bank_name']); ?></td>
                    <td><span class="badge"><?php echo strtoupper($flow['type']); ?></span></td>
                    <td><?php echo App\Helpers::formatCurrency((float)$flow['amount']); ?></td>
                    <td><?php echo htmlspecialchars($flow['description']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
