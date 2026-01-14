<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/footer.php';

$slug = $_GET['slug'] ?? '';
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = :slug');
$stmt->execute(['slug' => $slug]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    http_response_code(404);
    render_header('İçerik Bulunamadı');
    echo '<main class="container"><p>İçerik bulunamadı.</p></main>';
    render_footer();
    exit;
}

render_header($page['title']);
?>
<main class="container content-page">
    <h1><?= htmlspecialchars($page['title']) ?></h1>
    <p class="summary"><?= htmlspecialchars($page['summary']) ?></p>
    <article class="content-body">
        <?= nl2br(htmlspecialchars($page['content'])) ?>
    </article>
</main>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $page['title'],
    'description' => $page['summary'],
    'datePublished' => $page['created_at'],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => base_url('page.php?slug=' . $page['slug']),
    ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
</script>
<?php
render_footer();
?>
