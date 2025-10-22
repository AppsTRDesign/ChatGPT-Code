<section class="section">
    <h2>Genel Durum</h2>
    <div class="cards">
        <div class="card">
            <h3>Cari Sayısı</h3>
            <p><?php echo $cariCount; ?></p>
        </div>
        <div class="card">
            <h3>Stok Sayısı</h3>
            <p><?php echo $stockCount; ?></p>
        </div>
        <div class="card">
            <h3>Fatura Sayısı</h3>
            <p><?php echo $invoiceCount; ?></p>
        </div>
        <div class="card">
            <h3>Nakit Akışı</h3>
            <p><?php echo App\Helpers::formatCurrency($paymentSum); ?></p>
        </div>
    </div>
</section>

<section class="section">
    <div class="flex-between">
        <h2>Kritik Stoklar</h2>
        <a class="input-button" href="index.php?module=stock">Stokları Yönet</a>
    </div>
    <?php if (empty($criticalStocks)): ?>
        <div class="alert">Kritik seviyede stok bulunmuyor.</div>
    <?php else: ?>
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th>Ürün</th>
                    <th>Miktar</th>
                    <th>Kritik Seviye</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($criticalStocks as $stock): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stock['name']); ?></td>
                        <td><?php echo $stock['quantity']; ?></td>
                        <td><?php echo $stock['critical_level']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="section">
    <div class="flex-between">
        <h2>Son Faturalar</h2>
        <a class="input-button" href="index.php?module=invoices">Faturalara Git</a>
    </div>
    <?php if (empty($recentInvoices)): ?>
        <div class="alert">Henüz fatura oluşturulmadı.</div>
    <?php else: ?>
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th>Numara</th>
                    <th>Cari</th>
                    <th>Tip</th>
                    <th>Toplam</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentInvoices as $invoice): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($invoice['invoice_no']); ?></td>
                        <td><?php echo htmlspecialchars($invoice['cari_name']); ?></td>
                        <td><span class="badge"><?php echo strtoupper($invoice['type']); ?></span></td>
                        <td><?php echo App\Helpers::formatCurrency((float)$invoice['total']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
