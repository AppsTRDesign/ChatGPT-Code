<section class="hero">
  <div class="container hero-grid">
    <div>
      <p class="eyebrow"><?= t('front', 'hero_eyebrow') ?></p>
      <h1><?= t('front', 'hero_title') ?></h1>
      <p><?= t('front', 'hero_desc') ?></p>
      <form id="quickTrack" class="track-form glass">
        <input type="text" name="tracking_number" placeholder="TRK-2026-00001" required>
        <button type="submit"><?= t('front', 'track') ?></button>
      </form>
    </div>
    <aside class="hero-card">
      <h3><?= t('front', 'hero_card_title') ?></h3>
      <ul>
        <li><?= t('front', 'hero_card_item_1') ?></li>
        <li><?= t('front', 'hero_card_item_2') ?></li>
        <li><?= t('front', 'hero_card_item_3') ?></li>
      </ul>
    </aside>
  </div>
</section>
<section class="cards container">
  <article><h3><?= t('front', 'service_1_title') ?></h3><p><?= t('front', 'service_1_desc') ?></p></article>
  <article><h3><?= t('front', 'service_2_title') ?></h3><p><?= t('front', 'service_2_desc') ?></p></article>
  <article><h3><?= t('front', 'service_3_title') ?></h3><p><?= t('front', 'service_3_desc') ?></p></article>
</section>
<section class="container pro-band">
  <div>
    <h2><?= t('front', 'cta_title') ?></h2>
    <p><?= t('front', 'cta_desc') ?></p>
  </div>
  <a href="/pricing" class="cta-btn"><?= t('front', 'calculate') ?></a>
</section>
