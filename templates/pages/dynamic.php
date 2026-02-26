<section class="container section">
  <h2><?= htmlspecialchars($payload['page']['title'] ?? '', ENT_QUOTES) ?></h2>
  <div class="panel editor-content"><?= $payload['page']['content_html'] ?? '' ?></div>
</section>
