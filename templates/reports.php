<section class="section">
    <div class="flex-between">
        <h2>Finansal Raporlar</h2>
        <div class="utility-links">
            <a href="index.php?module=reports&action=export&type=pdf&start=<?php echo $start; ?>&end=<?php echo $end; ?>">PDF İndir</a>
            <a href="index.php?module=reports&action=export&type=excel&start=<?php echo $start; ?>&end=<?php echo $end; ?>">Excel İndir</a>
            <a href="index.php?module=reports&action=export&type=word&start=<?php echo $start; ?>&end=<?php echo $end; ?>">Word İndir</a>
        </div>
    </div>
    <form method="get" action="index.php">
        <input type="hidden" name="module" value="reports">
        <div class="grid two">
            <div>
                <label>Başlangıç</label>
                <input type="date" name="start" value="<?php echo $start; ?>">
            </div>
            <div>
                <label>Bitiş</label>
                <input type="date" name="end" value="<?php echo $end; ?>">
            </div>
        </div>
        <div>
            <input type="submit" value="Filtrele">
        </div>
    </form>
</section>

<section class="section">
    <h2>Kâr / Zarar</h2>
    <div class="cards">
        <div class="card">
            <h3>Satış Toplamı</h3>
            <p><?php echo App\Helpers::formatCurrency($salesTotal); ?></p>
        </div>
        <div class="card">
            <h3>Alış Toplamı</h3>
            <p><?php echo App\Helpers::formatCurrency($purchasesTotal); ?></p>
        </div>
        <div class="card">
            <h3>Kâr / Zarar</h3>
            <p><?php echo App\Helpers::formatCurrency($profit); ?></p>
        </div>
    </div>
</section>

<section class="section">
    <h2>Stok Durumu</h2>
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>Ürün</th>
                <th>Miktar</th>
                <th>Birim Fiyat</th>
                <th>Toplam Değer</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($stocks as $stock): ?>
                <tr>
                    <td><?php echo htmlspecialchars($stock['name']); ?></td>
                    <td><?php echo $stock['quantity']; ?></td>
                    <td><?php echo App\Helpers::formatCurrency((float)$stock['price']); ?></td>
                    <td><?php echo App\Helpers::formatCurrency((float)$stock['price'] * (float)$stock['quantity']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="section">
    <h2>Cari Bakiyeler</h2>
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>Cari</th>
                <th>Bakiye</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($cariSummary as $cari): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cari['name']); ?></td>
                    <td><?php echo App\Helpers::formatCurrency((float)$cari['balance']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
