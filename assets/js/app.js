$(function () {

  /* ==========================================================
   * LANGUAGE DROPDOWN (Desktop + Mobile)
   * ========================================================== */
  const $langToggle = $('#langToggle');
  const $langMenu   = $('#langMenu');

  $langToggle.on('click', function (e) {
    e.stopPropagation();
    $langMenu.toggleClass('open');
  });

  // dışarı tıklanınca kapat
  $(document).on('click', function () {
    $langMenu.removeClass('open');
  });

  // menü içi tıklamalar kapanmasın
  $langMenu.on('click', function (e) {
    e.stopPropagation();
  });

  /* ==========================================================
   * MOBILE MENU
   * ========================================================== */
  const $mobileToggle = $('#mobileToggle');
  const $mobileMenu   = $('#mobileMenu');

  $mobileToggle.on('click', function (e) {
    e.stopPropagation();
    $mobileMenu.toggleClass('open');
    $('body').toggleClass('menu-open');
  });

  // mobil menü dışına tıklanınca kapat
  $(document).on('click', function (e) {
    if (
      !$(e.target).closest('#mobileMenu').length &&
      !$(e.target).closest('#mobileToggle').length
    ) {
      $mobileMenu.removeClass('open');
      $('body').removeClass('menu-open');
    }
  });

  /* ==========================================================
   * DESKTOP DROPDOWN MENU (PRO MODE)
   * ========================================================== */
  const DESKTOP_BP = 980;

  $('.desktop-menu .has-dropdown > a').on('click', function (e) {
    if (window.innerWidth <= DESKTOP_BP) return;

    e.preventDefault();
    e.stopPropagation();

    const $item = $(this).parent();

    // diğerlerini kapat
    $('.desktop-menu .has-dropdown').not($item).removeClass('open');

    // toggle current
    $item.toggleClass('open');
  });

  // dropdown dışına tıklanınca kapat
  $(document).on('click', function () {
    if (window.innerWidth > DESKTOP_BP) {
      $('.desktop-menu .has-dropdown').removeClass('open');
    }
  });

  // dropdown içine tıklanınca kapanmasın
  $('.desktop-menu .menu-dropdown').on('mouseenter', function () {
    $(this).closest('.menu-item').addClass('open');
  });

  /* ==========================================================
   * NEWSLETTER
   * ========================================================== */
  $('#newsletterForm').on('submit', function (e) {
    e.preventDefault();

    const email = ($(this).find('[name=email]').val() || '').trim();
    if (!/^\S+@\S+\.\S+$/.test(email)) {
      toastr.error('Geçerli bir e-posta girin');
      return;
    }

    $.post('/api/subscribe.php', {
      csrf: window.CSRF_TOKEN,
      email: email
    }, function (res) {
      if (!res.ok) return toastr.error(res.message);
      toastr.success(res.message);
      $('#newsletterForm')[0].reset();
    }, 'json');
  });

  /* ==========================================================
   * QUICK TRACK
   * ========================================================== */
  $('#quickTrack').on('submit', function (e) {
    e.preventDefault();
    const no = $(this).find('input[name="tracking_number"]').val();
    if (!no) return;
    window.location.href = '/tracking?num=' + encodeURIComponent(no);
  });

  /* ==========================================================
   * PRICING FORM
   * ========================================================== */
  $('#countrySelect').on('change', function () {
    const countryId = $(this).val();
    if (!countryId) return;

    $.getJSON('/api/categories.php', { country_id: countryId }, function (rows) {
      const $target = $('#categorySelect').empty()
        .append('<option value="">--</option>');

      rows.forEach(r => {
        $target.append(`<option value="${r.id}">${r.title}</option>`);
      });
    });
  });

  $('#pricingForm').on('submit', function (e) {
    e.preventDefault();

    $.post(
      '/api/pricing.php',
      $(this).serialize() + '&csrf=' + window.CSRF_TOKEN,
      function (res) {
        if (!res.ok) return toastr.error(res.message);

        toastr.success(res.message);
        $('#pricingResult').html(`
          <div class="panel">
            <h3>${res.data.category_title}</h3>
            <p>${res.data.country_name}</p>
            <p><strong>${res.data.ucret_usd} USD</strong></p>
            <p>Chargeable: ${res.data.ucret_kilo} kg |
               Volumetric: ${res.data.hacimsel_kilo} kg</p>
            <p>${res.data.description || ''}</p>
          </div>
        `);
      },
      'json'
    );
  });

  async function renderTrackingMap(events, shipment) {
    if (!window.ymaps3 || !document.getElementById('trackingMap')) return;
    await ymaps3.ready;
    const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapMarker, YMapFeature } = ymaps3;
    let coords = events.filter((e) => e.latitude && e.longitude).map((e) => [parseFloat(e.longitude), parseFloat(e.latitude)]);
    if (!coords.length && shipment.current_latitude && shipment.current_longitude) {
      coords = [[parseFloat(shipment.current_longitude), parseFloat(shipment.current_latitude)]];
    }

    const origin = shipment.origin_latitude && shipment.origin_longitude ? [parseFloat(shipment.origin_longitude), parseFloat(shipment.origin_latitude)] : null;
    const destination = shipment.destination_latitude && shipment.destination_longitude ? [parseFloat(shipment.destination_longitude), parseFloat(shipment.destination_latitude)] : null;
    if (origin) coords.push(origin);
    if (destination) coords.unshift(destination);
    if (!coords.length) return;

    const mapRoot = document.getElementById('trackingMap');
    mapRoot.innerHTML = '';
    const map = new YMap(mapRoot, { location: { center: coords[0], zoom: coords.length > 1 ? 4 : 8 } });
    map.addChild(new YMapDefaultSchemeLayer());
    map.addChild(new YMapDefaultFeaturesLayer());

    if (coords.length > 1) {
      map.addChild(new YMapFeature({
        geometry: { type: 'LineString', coordinates: coords },
        style: { stroke: [{ color: '#1d4ed8', width: 4 }] }
      }));
    }

    coords.forEach((c) => {
      const el = document.createElement('div');
      el.className = 'map-pin';
      map.addChild(new YMapMarker({ coordinates: c }, el));
    });
  }

  function renderTrackingInfo(res) {
    const s = res.data.shipment;
    const events = res.data.events;
    const L = window.TRACK_LABELS || {};
    const badge = (st,label)=>`<span class='status-badge status-${st}'>${label||st}</span>`;
    const list = events.map((ev) => `<li>${badge(ev.status_code, ev.status_label)} ${ev.status_note || '-'} <small>${ev.city || ''}/${ev.country || ''}</small></li>`).join('');
    $('#trackingInfo').html(`
      <h3>${s.tracking_number}</h3>
      <p><button id='downloadTrackPdf' data-tr='${s.tracking_number}' type='button'>PDF İndir</button></p>
      <p><strong>${L.status || 'Status'}:</strong> ${s.status_label || s.current_status}</p>
      <p><strong>${L.current_location || 'Current Location'}:</strong> ${s.current_latitude || '-'}, ${s.current_longitude || '-'}</p>
      <p><strong>${L.route || 'Route'}:</strong> ${s.origin_country} / ${s.origin_city} → ${s.destination_country} / ${s.destination_city}</p>
      <div class="group-title">${L.sender || 'Sender'}</div>
      <p>${s.sender_name || '-'} ${s.sender_company ? '(' + s.sender_company + ')' : ''} - ${s.sender_phone || '-'}</p>
      <div class="group-title">${L.receiver || 'Receiver'}</div>
      <p>${s.receiver_name || '-'} - ${s.receiver_phone || '-'}<br>${s.receiver_address || '-'}</p>
      <div class="group-title">${L.timeline || 'Timeline'}</div>
      <p><strong>${L.description || 'Description'}:</strong> ${s.description || '-'}</p><ul>${list || `<li>${L.no_event || 'No event yet.'}</li>`}</ul>
    `);
  }

  /* ==========================================================
   * TRACKING
   * ========================================================== */
  $('#trackingForm').on('submit', function (e) {
    e.preventDefault();

    $.post(
      '/api/track.php',
      $(this).serialize() + '&csrf=' + window.CSRF_TOKEN,
      function (res) {
        if (!res.ok) return toastr.error(res.message);

        toastr.success(res.message);
        $('#trackingEmpty').hide();
        $('#trackingDetail').show();
        renderTrackingInfo(res);
        renderTrackingMap(res.data.events, res.data.shipment);
      },
      'json'
    );
  });

  const num = new URLSearchParams(window.location.search).get('num');
  if (num && $('#trackingForm').length) {
    $('#trackingForm input[name="tracking_number"]').val(num);
  }

  if ($('#documentsList').length) {
    $.getJSON('/api/documents.php', function(rows){
      const box = $('#documentsList').empty();
      rows.forEach((d)=>{
        const isPdf = (d.mime_type || '').includes('pdf') || String(d.file_path||'').toLowerCase().endsWith('.pdf');
        const preview = isPdf
          ? `<iframe src="${d.file_path}" title="${d.title}"></iframe>`
          : `<a data-fancybox="docs" href="${d.file_path}"><img src="${d.file_path}" alt="${d.title}"></a>`;
        box.append(`<article class="panel doc-card"><h3>${d.title}</h3>${preview}</article>`);
      });
      if (window.Fancybox) {
        Fancybox.bind('[data-fancybox="docs"]', {});
      }
    });
  }

});

/* ==========================================================
 * PDF DOWNLOAD
 * ========================================================== */
$(document).on('click', '#downloadTrackPdf', function () {
  const tr = $(this).data('tr');
  window.location.href =
    '/api/track_pdf.php?tracking_number=' + encodeURIComponent(tr);
});
