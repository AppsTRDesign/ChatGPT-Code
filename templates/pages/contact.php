<?php $cfg = settings(); ?>
<section class="container section">
  <h2><?= t('front', 'contact') ?></h2>
  <p class="subtext"><?= t('front', 'contact_desc') ?></p>
  <div class="grid-2">
    <div class="panel">
      <p><strong><?= t('front', 'address') ?>:</strong> <?= htmlspecialchars($cfg['company_address'] ?? '-', ENT_QUOTES) ?></p>
      <p><strong><?= t('front', 'phone') ?>:</strong> <?= htmlspecialchars($cfg['company_phone'] ?? '-', ENT_QUOTES) ?></p>
      <p><strong><?= t('front', 'email') ?>:</strong> <?= htmlspecialchars($cfg['company_email'] ?? '-', ENT_QUOTES) ?></p>
    </div>
    <div class="panel">
      <iframe title="osm" src="<?= htmlspecialchars($cfg['osm_embed_url'] ?? 'https://www.openstreetmap.org/export/embed.html', ENT_QUOTES) ?>"></iframe>
    </div>
  </div>
</section>
