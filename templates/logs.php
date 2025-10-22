<section class="section">
    <h2>Kullanıcı İşlem Logları</h2>
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>Kullanıcı</th>
                <th>İşlem</th>
                <th>Tarih</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                    <td><?php echo $log['created_at']; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
