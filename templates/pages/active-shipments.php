<?php $cfg = settings(); $ymKey = $cfg['yandex_api_key'] ?? 'd0b1a4c0-60eb-4a39-b34a-61c68fffc2d6'; ?>
<section class="container section">
  <h2>Active Shipments</h2>
  <p class="subtext">Live global shipment pins with route cards.</p>
  <div class="panel"><div id="activeShipmentsMap" style="height:560px"></div></div>
</section>
<script src="https://api-maps.yandex.ru/2.1/?apikey=<?= htmlspecialchars($ymKey, ENT_QUOTES) ?>&lang=en_US"></script>
<script>
if (window.ymaps) {
  ymaps.ready(function(){
    const map = new ymaps.Map('activeShipmentsMap',{center:[20,15],zoom:2});
    $.getJSON('/api/active_shipments.php', function(rows){
      rows.forEach(function(r){
        if(!r.current_latitude || !r.current_longitude){return;}
        const html = `<div style="min-width:210px"><strong>${r.tracking_number}</strong><div style="margin-top:8px">From: ${r.origin_country}</div><div>To: ${r.destination_country}</div></div>`;
        const p = new ymaps.Placemark([parseFloat(r.current_latitude), parseFloat(r.current_longitude)], {balloonContent: html}, {
          iconLayout: 'default#image',
          iconImageHref: '/assets/icons/ship-pin.svg',
          iconImageSize: [42, 42],
          iconImageOffset: [-21, -42]
        });
        map.geoObjects.add(p);
      });
    });
  });
}
</script>
