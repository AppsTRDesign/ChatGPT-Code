$(function () {
  $('#mobileToggle').on('click', function () {
    $('#mobileMenu').toggleClass('open');
  });

  $('#quickTrack').on('submit', function (e) {
    e.preventDefault();
    const no = $(this).find('input[name="tracking_number"]').val();
    window.location.href = '/tracking?num=' + encodeURIComponent(no);
  });

  $('#countrySelect').on('change', function () {
    const countryId = $(this).val();
    $.getJSON('/api/categories.php', { country_id: countryId }, function (rows) {
      const target = $('#categorySelect').empty();
      target.append('<option value="">--</option>');
      rows.forEach((r) => target.append(`<option value="${r.id}">${r.title}</option>`));
    });
  });

  $('#pricingForm').on('submit', function (e) {
    e.preventDefault();
    $.post('/api/pricing.php', $(this).serialize() + '&csrf=' + window.CSRF_TOKEN, function (res) {
      if (!res.ok) {
        toastr.error(res.message);
        return;
      }
      toastr.success(res.message);
      $('#pricingResult').html(`<div class="panel"><h3>${res.data.category_title}</h3><p>${res.data.country_name}</p><p><strong>${res.data.price_amount} EUR</strong></p><p>${res.data.description}</p></div>`);
    }, 'json');
  });

  $('#trackingForm').on('submit', function (e) {
    e.preventDefault();
    $.post('/api/track.php', $(this).serialize() + '&csrf=' + window.CSRF_TOKEN, function (res) {
      if (!res.ok) {
        toastr.error(res.message);
        return;
      }
      toastr.success(res.message);
      const events = res.data.events.map((ev) => `<li><strong>${ev.status_code}</strong> - ${ev.status_note} (${ev.city}/${ev.country})</li>`).join('');
      $('#trackingResult').html(`<div class="panel"><h3>${res.data.shipment.tracking_number}</h3><p>${res.data.shipment.current_status}</p><ul>${events}</ul></div>`);

      if (window.ymaps && document.getElementById('trackingMap')) {
        ymaps.ready(function () {
          const coords = res.data.events
            .filter((ev) => ev.latitude && ev.longitude)
            .map((ev) => [parseFloat(ev.latitude), parseFloat(ev.longitude)]);
          if (!coords.length) return;
          const map = new ymaps.Map('trackingMap', { center: coords[0], zoom: 5 });
          const routeLine = new ymaps.Polyline(coords, {}, { strokeColor: '#1d4ed8', strokeWidth: 4, strokeOpacity: 0.8 });
          map.geoObjects.add(routeLine);
          coords.forEach((c) => map.geoObjects.add(new ymaps.Placemark(c)));
        });
      }
    }, 'json');
  });
});
