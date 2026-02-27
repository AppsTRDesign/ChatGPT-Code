<?php
$cfg = settings();
$lat = (float)($cfg['company_latitude'] ?? 41.01);
$lng = (float)($cfg['company_longitude'] ?? 28.97);
$ymKey = $cfg['yandex_api_key'] ?? '';
$ymLang = yandex_locale();
?>
<section class="container section">
  <h2><?= t('front', 'contact') ?></h2>
  <p class="subtext"><?= t('front', 'contact_desc') ?></p>
  <div class="contact-pro">
    <div class="panel contact-card">
      <h3><?= t('front', 'contact_card_title') ?></h3>
      <div class="contact-row"><span class="label"><?= t('front', 'address') ?>:</span><span class="value"><?= htmlspecialchars($cfg['company_address'] ?? '-', ENT_QUOTES) ?></span></div>
      <div class="contact-row"><span class="label"><?= t('front', 'phone') ?>:</span><span class="value"><?= htmlspecialchars($cfg['company_phone'] ?? '-', ENT_QUOTES) ?></span></div>
      <div class="contact-row"><span class="label"><?= t('front', 'email') ?>:</span><span class="value"><?= htmlspecialchars($cfg['company_email'] ?? '-', ENT_QUOTES) ?></span></div>
      <p class="contact-desc"><?= t('front', 'contact_card_desc') ?></p>
    </div>
    <div class="panel"><div id="contactMap" style="height:430px"></div></div>
  </div>
</section>
<script src="https://api-maps.yandex.ru/v3/?apikey=<?= htmlspecialchars($ymKey, ENT_QUOTES) ?>&lang=<?= htmlspecialchars($ymLang, ENT_QUOTES) ?>"></script>
<script>
(async function(){
  if(!window.ymaps3 || !document.getElementById('contactMap')) return;
  await ymaps3.ready;
  const {YMap,YMapDefaultSchemeLayer,YMapDefaultFeaturesLayer,YMapMarker}=ymaps3;
  const map = new YMap(document.getElementById('contactMap'), {location:{center:[<?= $lng ?>,<?= $lat ?>], zoom:10}});
  map.addChild(new YMapDefaultSchemeLayer());
  map.addChild(new YMapDefaultFeaturesLayer());
  const el=document.createElement('div'); el.className='map-pin';
  map.addChild(new YMapMarker({coordinates:[<?= $lng ?>,<?= $lat ?>]},el));
})();
</script>
