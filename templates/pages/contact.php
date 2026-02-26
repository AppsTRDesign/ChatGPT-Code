<?php $cfg = settings(); $lat = (float)($cfg['company_latitude'] ?? 41.01); $lng = (float)($cfg['company_longitude'] ?? 28.97); $ymKey = $cfg['yandex_api_key'] ?? 'd0b1a4c0-60eb-4a39-b34a-61c68fffc2d6'; ?>
<section class="container section">
  <h2><?= t('front', 'contact') ?></h2>
  <p class="subtext"><?= t('front', 'contact_desc') ?></p>
  <div class="grid-2">
    <div class="panel">
      <p><strong><?= t('front', 'address') ?>:</strong> <?= htmlspecialchars($cfg['company_address'] ?? '-', ENT_QUOTES) ?></p>
      <p><strong><?= t('front', 'phone') ?>:</strong> <?= htmlspecialchars($cfg['company_phone'] ?? '-', ENT_QUOTES) ?></p>
      <p><strong><?= t('front', 'email') ?>:</strong> <?= htmlspecialchars($cfg['company_email'] ?? '-', ENT_QUOTES) ?></p>
    </div>
    <div class="panel"><div id="contactMap" style="height:340px"></div></div>
  </div>
</section>
<script src="https://api-maps.yandex.ru/2.1/?apikey=<?= htmlspecialchars($ymKey, ENT_QUOTES) ?>&lang=<?= htmlspecialchars($lang, ENT_QUOTES) ?>"></script>
<script>
if (window.ymaps) {
  ymaps.ready(function(){
    const map = new ymaps.Map('contactMap',{center:[<?= $lat ?>,<?= $lng ?>],zoom:10});
    const pin = new ymaps.Placemark([<?= $lat ?>,<?= $lng ?>],{balloonContent:'<?= htmlspecialchars($cfg['company_address'] ?? 'Office', ENT_QUOTES) ?>'});
    map.geoObjects.add(pin);
  });
}
</script>
