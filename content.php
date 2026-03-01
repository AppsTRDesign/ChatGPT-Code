<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$pdo = db();
$pages = $pdo->query('SELECT * FROM pages ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

render_header('İçerikler');
?>
<main class="container">
    <h1>İçerikler</h1>
    <div class="grid">
        <?php foreach ($pages as $page): ?>
            <article class="card">
                <div class="card-body">
                    <h3><?= htmlspecialchars($page['title']) ?></h3>
                    <p><?= htmlspecialchars($page['summary']) ?></p>
                    <a class="btn" href="/page/<?= urlencode($page['slug']) ?>">Detayları Gör</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</main>
<?php
render_footer();
?>
