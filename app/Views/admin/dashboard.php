<?php require __DIR__ . '/../partials/head.php'; ?>
<header class="topbar">
    <div>
        <h1>Kontrol Paneli</h1>
        <p>Gerçek zamanlı web push yönetim konsolu.</p>
    </div>
    <nav>
        <a href="<?= asset('admin/logout') ?>" class="btn-secondary">Çıkış</a>
    </nav>
</header>

<main class="dashboard">
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <section class="grid">
        <article class="card stats">
            <h2>Aboneler</h2>
            <p class="stat-number"><?= count($subscribers) ?></p>
            <p class="stat-helper">Platforma kayıtlı cihaz sayısı.</p>
        </article>
        <article class="card stats">
            <h2>Kampanyalar</h2>
            <p class="stat-number"><?= count($campaigns) ?></p>
            <p class="stat-helper">Gönderilen bildirim kampanyaları.</p>
        </article>
        <article class="card stats">
            <h2>Segmentler</h2>
            <p class="stat-number"><?= count($segments) ?></p>
            <p class="stat-helper">Hedef kitlenizi anlamlandırın.</p>
        </article>
    </section>

    <section class="flex">
        <article class="card flex-item">
            <h2>Yeni Segment Oluştur</h2>
            <form method="post" action="<?= asset('admin/segments/create') ?>" class="form">
                <label for="segment-name">Segment Adı</label>
                <input type="text" id="segment-name" name="name" required>

                <label for="segment-filters">Filtre Notları</label>
                <textarea id="segment-filters" name="filters" rows="3" placeholder="Örn: Cihaz=Mobil, Tag=beta"></textarea>

                <button type="submit" class="btn-primary">Segment Kaydet</button>
            </form>
        </article>

        <article class="card flex-item">
            <h2>Kampanya Yayınla</h2>
            <form method="post" action="<?= asset('admin/campaigns/create') ?>" class="form">
                <label for="campaign-title">Başlık</label>
                <input type="text" id="campaign-title" name="title" required>

                <label for="campaign-message">Mesaj</label>
                <textarea id="campaign-message" name="message" rows="3" required></textarea>

                <label for="campaign-segment">Segment</label>
                <select id="campaign-segment" name="target_segment">
                    <option value="">Tüm aboneler</option>
                    <?php foreach ($segments as $segment): ?>
                        <option value="<?= $segment['id'] ?>"><?= htmlspecialchars($segment['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="campaign-tags">Etiket Filtreleri</label>
                <input type="text" id="campaign-tags" name="target_tags" placeholder="Örn: premium, beta">

                <button type="submit" class="btn-accent">Kampanyayı Gönder</button>
            </form>
        </article>
    </section>

    <section class="card">
        <h2>Son Gönderimler</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Abone</th>
                        <th>Kampanya</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><small><?= htmlspecialchars($log['endpoint']) ?></small></td>
                            <td><?= htmlspecialchars($log['title']) ?></td>
                            <td><span class="badge badge-success"><?= htmlspecialchars($log['status']) ?></span></td>
                            <td><?= htmlspecialchars(date('d.m.Y H:i', strtotime($log['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="4" class="empty">Henüz kayıt bulunmuyor.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2>Aboneler</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Endpoint</th>
                        <th>Cihaz</th>
                        <th>Tarayıcı</th>
                        <th>Zaman Dilimi</th>
                        <th>Etiketler</th>
                        <th>Katılım</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscribers as $subscriber): ?>
                        <tr>
                            <td><small><?= htmlspecialchars($subscriber['endpoint']) ?></small></td>
                            <td><?= htmlspecialchars($subscriber['device']) ?></td>
                            <td><?= htmlspecialchars($subscriber['browser']) ?></td>
                            <td><?= htmlspecialchars($subscriber['timezone']) ?></td>
                            <td><?= htmlspecialchars($subscriber['tags'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(date('d.m.Y H:i', strtotime($subscriber['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($subscribers)): ?>
                        <tr>
                            <td colspan="6" class="empty">Henüz abone bulunmuyor.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
