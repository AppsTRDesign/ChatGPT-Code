<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$pdo = db();
$faqs = $pdo->query('SELECT * FROM faqs ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

render_header('Sıkça Sorulan Sorular');
?>
<main class="container">
    <h1>Sıkça Sorulan Sorular</h1>
    <div class="faq">
        <?php foreach ($faqs as $index => $faq): ?>
            <details <?= $index === 0 ? 'open' : '' ?>>
                <summary><?= htmlspecialchars($faq['question']) ?></summary>
                <p><?= nl2br(htmlspecialchars($faq['answer'])) ?></p>
            </details>
        <?php endforeach; ?>
    </div>
</main>
<?php
render_footer();
?>
