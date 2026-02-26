<section class="hero">
  <div class="container hero-content">
    <h1><?= t('front', 'hero_title') ?></h1>
    <p><?= t('front', 'hero_desc') ?></p>
    <form id="quickTrack" class="track-form">
      <input type="text" name="tracking_number" placeholder="TRK-2026-00001" required>
      <button type="submit"><?= t('front', 'track') ?></button>
    </form>
  </div>
</section>
<section class="cards container">
  <article><h3><?= t('front', 'service_1_title') ?></h3><p><?= t('front', 'service_1_desc') ?></p></article>
  <article><h3><?= t('front', 'service_2_title') ?></h3><p><?= t('front', 'service_2_desc') ?></p></article>
  <article><h3><?= t('front', 'service_3_title') ?></h3><p><?= t('front', 'service_3_desc') ?></p></article>
</section>
