<section class="section">
    <div class="flex-between">
        <h2>Cari Hesap Yönetimi</h2>
        <div class="utility-links">
            <a href="index.php?module=caris&action=export&type=pdf">PDF İndir</a>
            <a href="index.php?module=caris&action=export&type=excel">Excel İndir</a>
            <a href="index.php?module=caris&action=export&type=word">Word İndir</a>
        </div>
    </div>
    <form method="post" data-ajax="true" action="index.php?module=caris&action=<?php echo $cari ? 'update&id=' . $cari['id'] : 'create'; ?>">
        <div class="grid two">
            <div>
                <label>Ad</label>
                <input type="text" name="name" required value="<?php echo $cari['name'] ?? ''; ?>">
            </div>
            <div>
                <label>Tip</label>
                <select name="type" required>
                    <option value="musteri" <?php echo (($cari['type'] ?? '') === 'musteri') ? 'selected' : ''; ?>>Müşteri</option>
                    <option value="tedarikci" <?php echo (($cari['type'] ?? '') === 'tedarikci') ? 'selected' : ''; ?>>Tedarikçi</option>
                </select>
            </div>
            <div>
                <label>E-posta</label>
                <input type="email" name="email" value="<?php echo $cari['email'] ?? ''; ?>">
            </div>
            <div>
                <label>Telefon</label>
                <input type="text" name="phone" value="<?php echo $cari['phone'] ?? ''; ?>">
            </div>
            <div>
                <label>Grup</label>
                <input type="text" name="group_name" value="<?php echo $cari['group_name'] ?? ''; ?>">
            </div>
            <div>
                <label>Adres</label>
                <textarea name="address"><?php echo $cari['address'] ?? ''; ?></textarea>
            </div>
        </div>
        <div>
            <input type="submit" value="<?php echo $cari ? 'Cariyi Güncelle' : 'Yeni Cari Ekle'; ?>">
        </div>
    </form>
</section>

<section class="section">
    <h2>Kayıtlı Cariler</h2>
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>Ad</th>
                <th>Tip</th>
                <th>Grup</th>
                <th>Telefon</th>
                <th>E-posta</th>
                <th>Bakiye</th>
                <th>İşlemler</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($caris as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><span class="badge"><?php echo strtoupper($row['type']); ?></span></td>
                    <td><?php echo htmlspecialchars($row['group_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo App\Helpers::formatCurrency((float)$row['balance']); ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="index.php?module=caris&action=edit&id=<?php echo $row['id']; ?>">Düzenle</a>
                            <button type="button" class="link-button delete-button" data-delete-url="index.php?module=caris&amp;action=delete&amp;id=<?php echo $row['id']; ?>" data-id="<?php echo $row['id']; ?>">Sil</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
