$(function () {
  $('#mobileToggle').on('click', function () { $('#mobileMenu').toggleClass('open'); });

  $('#quickTrack').on('submit', function (e) {
    e.preventDefault();
    const no = $(this).find('input[name="tracking_number"]').val();
    window.location.href = '/tracking?num=' + encodeURIComponent(no);
  });

  $('#countrySelect').on('change', function () {
    const countryId = $(this).val();
    $.getJSON('/api/categories.php', { country_id: countryId }, function (rows) {
      const target = $('#categorySelect').empty().append('<option value="">--</option>');
      rows.forEach((r) => target.append(`<option value="${r.id}">${r.title}</option>`));
    });
  });

  $('#pricingForm').on('submit', function (e) {
    e.preventDefault();
    $.post('/api/pricing.php', $(this).serialize() + '&csrf=' + window.CSRF_TOKEN, function (res) {
      if (!res.ok) return toastr.error(res.message);
      toastr.success(res.message);
      $('#pricingResult').html(`<div class="panel"><h3>${res.data.category_title}</h3><p>${res.data.country_name}</p><p><strong>${res.data.price_amount} ${res.data.currency_code || 'EUR'}</strong></p><p>${res.data.description || ''}</p></div>`);
    }, 'json');
  });

  async function renderTrackingMap(events) {
    if (!window.ymaps3 || !document.getElementById('trackingMap')) return;
    await ymaps3.ready;
    const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapMarker } = ymaps3;
    const coords = events.filter((e) => e.latitude && e.longitude).map((e) => [parseFloat(e.longitude), parseFloat(e.latitude)]);
    if (!coords.length) return;

    const mapRoot = document.getElementById('trackingMap');
    mapRoot.innerHTML = '';
    const map = new YMap(mapRoot, { location: { center: coords[0], zoom: 5 } });
    map.addChild(new YMapDefaultSchemeLayer());
    map.addChild(new YMapDefaultFeaturesLayer());

    coords.forEach((c) => {
      const el = document.createElement('div');
      el.className = 'map-pin';
      map.addChild(new YMapMarker({ coordinates: c }, el));
    });
  }

  function renderTrackingInfo(res) {
    const s = res.data.shipment;
    const events = res.data.events;
    const list = events.map((ev) => `<li><strong>${ev.status_code}</strong> — ${ev.status_note || '-'} <small>${ev.city || ''}/${ev.country || ''}</small></li>`).join('');
    $('#trackingInfo').html(`
      <h3>${s.tracking_number}</h3>
      <p><strong>Status:</strong> ${s.current_status}</p>
      <p><strong>Route:</strong> ${s.origin_country} / ${s.origin_city} → ${s.destination_country} / ${s.destination_city}</p>
      <div class="group-title">Sender</div>
      <p>${s.sender_name || '-'} ${s.sender_company ? '(' + s.sender_company + ')' : ''} - ${s.sender_phone || '-'}</p>
      <div class="group-title">Receiver</div>
      <p>${s.receiver_name || '-'} - ${s.receiver_phone || '-'}<br>${s.receiver_address || '-'}</p>
      <div class="group-title">Timeline</div>
      <ul>${list || '<li>No event yet.</li>'}</ul>
    `);
  }

  $('#trackingForm').on('submit', function (e) {
    e.preventDefault();
    $.post('/api/track.php', $(this).serialize() + '&csrf=' + window.CSRF_TOKEN, function (res) {
      if (!res.ok) return toastr.error(res.message);
      toastr.success(res.message);
      $('#trackingEmpty').hide();
      $('#trackingDetail').show();
      renderTrackingInfo(res);
      renderTrackingMap(res.data.events);
    }, 'json');
  });

  const num = new URLSearchParams(window.location.search).get('num');
  if (num && $('#trackingForm').length) {
    $('#trackingForm input[name="tracking_number"]').val(num);
  }
});
