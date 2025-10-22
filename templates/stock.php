<section class="section">
    <div class="flex-between">
        <h2>Stok Yönetimi</h2>
        <div class="utility-links">
            <a href="index.php?module=stock&action=export&type=pdf">PDF İndir</a>
            <a href="index.php?module=stock&action=export&type=excel">Excel İndir</a>
            <a href="index.php?module=stock&action=export&type=word">Word İndir</a>
        </div>
    </div>
    <form method="post" action="index.php?module=stock&action=<?php echo $item ? 'update&id=' . $item['id'] : 'create'; ?>">
        <div class="grid three">
            <div>
                <label>SKU</label>
                <input type="text" name="sku" value="<?php echo $item['sku'] ?? ''; ?>">
            </div>
            <div>
                <label>Ürün Adı</label>
                <input type="text" name="name" required value="<?php echo $item['name'] ?? ''; ?>">
            </div>
            <div>
                <label>Kategori</label>
                <input type="text" name="category" value="<?php echo $item['category'] ?? ''; ?>">
            </div>
            <div>
                <label>Stok Miktarı</label>
                <input type="number" step="0.01" name="quantity" value="<?php echo $item['quantity'] ?? 0; ?>">
            </div>
            <div>
                <label>Kritik Seviye</label>
                <input type="number" step="0.01" name="critical_level" value="<?php echo $item['critical_level'] ?? 0; ?>">
            </div>
            <div>
                <label>Birim Fiyat</label>
                <input type="number" step="0.01" name="price" value="<?php echo $item['price'] ?? 0; ?>">
            </div>
        </div>
        <div>
            <label>Açıklama</label>
            <textarea name="description"><?php echo $item['description'] ?? ''; ?></textarea>
        </div>
        <div>
            <input type="submit" value="<?php echo $item ? 'Stoku Güncelle' : 'Yeni Stok Ekle'; ?>">
        </div>
    </form>
</section>

<section class="section">
    <h2>Stok Listesi</h2>
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>SKU</th>
                <th>Ürün</th>
                <th>Kategori</th>
                <th>Miktar</th>
                <th>Kritik</th>
                <th>Birim Fiyat</th>
                <th>İşlemler</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['sku']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                    <td><?php echo $row['quantity']; ?><?php if ($row['critical']): ?><span class="badge">Kritik</span><?php endif; ?></td>
                    <td><?php echo $row['critical_level']; ?></td>
                    <td><?php echo App\Helpers::formatCurrency((float)$row['price']); ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="index.php?module=stock&action=edit&id=<?php echo $row['id']; ?>">Düzenle</a>
                            <a href="index.php?module=stock&action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('Stok silinsin mi?');">Sil</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
