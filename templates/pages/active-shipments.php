<?php $cfg = settings(); $ymKey = (string)($cfg['yandex_api_key'] ?? ''); ?>
<section class="container section">
  <h2>Active Shipments</h2>
  <p class="subtext">Live global shipment pins with route cards.</p>
  <div class="panel"><div id="activeShipmentsMap" style="height:560px"></div><div id="shipmentPinCard" class="panel" style="display:none;margin-top:15px"></div></div>
</section>
<script src="https://api-maps.yandex.ru/v3/?apikey=<?= htmlspecialchars($ymKey, ENT_QUOTES) ?>&lang=en_US"></script>
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
        $('#shipmentPinCard').show().html(`<h3>${r.tracking_number}</h3><p><strong>From:</strong> ${r.origin_country}</p><p><strong>To:</strong> ${r.destination_country}</p>`);
      });
      map.addChild(new YMapMarker({coordinates:[parseFloat(r.current_longitude), parseFloat(r.current_latitude)]}, pin));
    });
  });
})();
</script>
