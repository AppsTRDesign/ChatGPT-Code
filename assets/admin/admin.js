$(function () {
  const endpoint = (name) => '/admin/api/' + name + '.php';

  function postForm(selector, api, isMultipart = false) {
    $(document).on('submit', selector, function (e) {
      e.preventDefault();
      if (isMultipart) {
        const fd = new FormData(this);
        $.ajax({
          url: endpoint(api),
          type: 'POST',
          data: fd,
          processData: false,
          contentType: false,
          dataType: 'json',
          success: (res) => res.ok ? toastr.success(res.message) : toastr.error(res.message)
        });
      } else {
        $.post(endpoint(api), $(this).serialize(), function (res) {
          res.ok ? toastr.success(res.message) : toastr.error(res.message);
          if (res.ok) { loadOptions(); loadTables(); }
        }, 'json');
      }
    });
  }

  function fillSelect(id, rows, labelKey = 'name') {
    const el = $(id).empty();
    rows.forEach((r) => el.append(`<option value="${r.id ?? r.code}">${r[labelKey]}</option>`));
  }

  function loadOptions() {
    $.getJSON(endpoint('options'), function (data) {
      fillSelect('#countrySelectAdmin', data.countries, 'name');
      fillSelect('#categorySelectAdmin', data.categories, 'title');
      fillSelect('#priceCountryId', data.countries, 'name');
      fillSelect('#priceCategoryId', data.categories, 'title');
      const shipSel = $('#shipmentTrackingSelect').empty();
      data.shipments.forEach((r) => shipSel.append(`<option value="${r.tracking_number}">${r.tracking_number}</option>`));

      const pageSel = $('#menuPageId').empty();
      data.pages.forEach((p) => pageSel.append(`<option value="${p.id}">${p.id} - ${p.title}</option>`));

      const langSel = $('.lang-options').empty();
      data.languages.forEach((l) => langSel.append(`<option value="${l.code}">${l.code}</option>`));
    });
  }

  function loadTables() {
    $.getJSON(endpoint('pricing_list'), function (rows) {
      const body = $('#pricingTableBody').empty();
      rows.forEach((r) => body.append(`<tr><td>${r.country_name}</td><td>${r.category_title}</td><td>${r.price_amount}</td></tr>`));
    });
  }

  postForm('#pageForm', 'page_save');
  postForm('#menuForm', 'menu_save');
  postForm('#shipmentForm', 'shipment_save');
  postForm('#eventForm', 'event_save');
  postForm('#countryForm', 'country_save');
  postForm('#categoryForm', 'category_save');
  postForm('#countryTranslationForm', 'country_translation_save');
  postForm('#categoryTranslationForm', 'category_translation_save');
  postForm('#priceConfigForm', 'price_save');
  postForm('#settingsForm', 'settings_save', true);
  postForm('#languageForm', 'language_save');
  postForm('#langForm', 'lang_save');
  postForm('#adminPasswordForm', 'admin_password');
  postForm('#translationJsonForm', 'translations_import_json');

  $(document).on('click', '#exportTranslationsJson', function () {
    window.open(endpoint('translations_export_json'), '_blank');
  });

  loadOptions();
  loadTables();

  if (window.ymaps) {
    ymaps.ready(function () {
      if (document.getElementById('mapPicker')) {
        const map = new ymaps.Map('mapPicker', { center: [41.01, 28.97], zoom: 5 });
        let mark;
        map.events.add('click', function (e) {
          const coords = e.get('coords');
          $('#shipmentLat').val(coords[0].toFixed(6));
          $('#shipmentLng').val(coords[1].toFixed(6));
          if (!mark) mark = new ymaps.Placemark(coords);
          else mark.geometry.setCoordinates(coords);
          map.geoObjects.add(mark);
        });
      }

      if (document.getElementById('settingsMap')) {
        const lat = parseFloat($('#companyLat').val() || '41.01');
        const lng = parseFloat($('#companyLng').val() || '28.97');
        const map2 = new ymaps.Map('settingsMap', { center: [lat, lng], zoom: 8 });
        let pin = new ymaps.Placemark([lat, lng]);
        map2.geoObjects.add(pin);
        map2.events.add('click', function (e) {
          const coords = e.get('coords');
          $('#companyLat').val(coords[0].toFixed(6));
          $('#companyLng').val(coords[1].toFixed(6));
          pin.geometry.setCoordinates(coords);
        });
      }
    });
  }
});
