<?php $cfg = settings(); $ymKey = (string)($cfg['yandex_api_key'] ?? ''); ?>
<section class="container section">
  <h2><?= t('front', 'active_shipments') ?></h2>
  <p class="subtext"><?= t('front', 'active_shipments_desc') ?></p>
  <div class="panel"><div id="activeShipmentsMap" style="height:560px"></div><div id="shipmentPinCard" class="panel" style="display:none;margin-top:15px"></div></div>
</section>
<script>window.ACTIVE_LABELS={from:'<?= addslashes(t('front','from')) ?>',to:'<?= addslashes(t('front','to')) ?>'};</script>
<script src="https://api-maps.yandex.ru/v3/?apikey=<?= htmlspecialchars($ymKey, ENT_QUOTES) ?>&lang=<?= htmlspecialchars(yandex_locale(), ENT_QUOTES) ?>"></script>
<script>
(async function(){
  if(!window.ymaps3) return;
  await ymaps3.ready;
  const {YMap,YMapDefaultSchemeLayer,YMapDefaultFeaturesLayer,YMapMarker}=ymaps3;
  const map = new YMap(document.getElementById('activeShipmentsMap'), {location:{center:[15,20],zoom:2}});
  map.addChild(new YMapDefaultSchemeLayer());
  map.addChild(new YMapDefaultFeaturesLayer());

  $.getJSON('/api/active_shipments.php', function(rows){
    rows.forEach(function(r){
      if(!r.current_latitude || !r.current_longitude){return;}
      const pin = document.createElement('div');
      pin.style.width='42px';pin.style.height='42px';pin.style.background="url('/assets/icons/ship-pin.svg') center/contain no-repeat";
      pin.title = r.tracking_number;
      pin.addEventListener('click', function(){
        const mask=(v)=> (v&&v.length>3)?(v.slice(0,3)+'*****'):(v||'***');
        $('#shipmentPinCard').show().html(`<h3>${mask(r.tracking_number)}</h3><p><strong>${window.ACTIVE_LABELS?.from||'From'}:</strong> ${r.origin_country}</p><p><strong>${window.ACTIVE_LABELS?.to||'To'}:</strong> ${r.destination_country}</p>`);
      });
      map.addChild(new YMapMarker({coordinates:[parseFloat(r.current_longitude), parseFloat(r.current_latitude)]}, pin));
    });
  });
})();
</script>
