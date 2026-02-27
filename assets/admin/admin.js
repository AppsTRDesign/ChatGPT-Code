$(function () {
  const endpoint = (name) => '/admin/api/' + name + '.php';

  $('#mobileSideBtn').on('click', function(){ $('#adminSidebar').toggleClass('open'); });
  $('.side-toggle').on('click', function(){ $(this).parent().toggleClass('open'); });

  if ($('#pageEditor').length) {
    tinymce.init({ selector: '#pageEditor', height: 420, menubar: true, plugins: 'link table lists code image', toolbar: 'undo redo | blocks | bold italic | bullist numlist | link image table | code' });
  }

  function submitForm(selector, api, multipart = false) {
    $(document).on('submit', selector, function (e) {
      e.preventDefault();
      if (selector === '#pageForm' && tinymce.get('pageEditor')) {
        tinymce.get('pageEditor').save();
      }
      if (multipart) {
        const fd = new FormData(this);
        $.ajax({ url: endpoint(api), method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
          .done((res) => { res.ok ? toastr.success(res.message) : toastr.error(res.message); loadOptions(); loadPricingTable(); });
      } else {
        $.post(endpoint(api), $(this).serialize(), function (res) {
          res.ok ? toastr.success(res.message) : toastr.error(res.message);
          loadOptions(); loadPricingTable();
        }, 'json');
      }
    });
  }

  submitForm('#pageForm', 'page_save');
  submitForm('#menuForm', 'menu_save');
  submitForm('#shipmentForm', 'shipment_save');
  submitForm('#eventForm', 'event_save');
  submitForm('#countryForm', 'country_save');
  submitForm('#categoryForm', 'category_save');
  submitForm('#countryTranslationForm', 'country_translation_save');
  submitForm('#categoryTranslationForm', 'category_translation_save');
  submitForm('#priceConfigForm', 'price_save');
  submitForm('#settingsForm', 'settings_save', true);
  submitForm('#languageForm', 'language_save');
  submitForm('#langForm', 'lang_save');
  submitForm('#translationJsonForm', 'translations_import_json');
  submitForm('#adminPasswordForm', 'admin_password');

  $('#langJsonLoadForm').on('submit', function (e) {
    e.preventDefault();
    $.getJSON(endpoint('translations_by_lang'), { lang: $('#jsonLang').val() }, function (res) {
      if (!res.ok) { toastr.error(res.message); return; }
      $('#jsonEditor').val(JSON.stringify({ [$('#jsonLang').val()]: res.data }, null, 2));
    });
  });

  $('#exportTranslationsJson').on('click', function () { window.open(endpoint('translations_export_json'), '_blank'); });

  function optionSet(selector, rows, valueKey, labelCb) {
    const el = $(selector); if (!el.length) return;
    const first = el.find('option[value=""]').length ? '<option value="">' + el.find('option[value=""]').text() + '</option>' : '';
    el.html(first);
    rows.forEach((r) => el.append(`<option value="${r[valueKey]}">${labelCb(r)}</option>`));
  }

  function loadOptions() {
    $.getJSON(endpoint('options'), function (res) {
      if (!res.ok) return;
      const data = res.data;
      optionSet('#countrySelectAdmin', data.countries, 'id', (r) => `${r.name} (${r.currency_code || '-'})`);
      optionSet('#countrySelectAdmin2', data.countries, 'id', (r) => r.name);
      optionSet('#priceCountryId', data.countries, 'id', (r) => `${r.name} (${r.currency_code || '-'})`);
      optionSet('#categorySelectAdmin', data.categories, 'id', (r) => r.title);
      optionSet('#categorySelectAdmin2', data.categories, 'id', (r) => r.title);
      optionSet('#priceCategoryId', data.categories, 'id', (r) => r.title);
      optionSet('#menuPageId', data.pages, 'id', (r) => `${r.id} - ${r.title}`);
      optionSet('#shipmentTrackingSelect', data.shipments, 'tracking_number', (r) => r.tracking_number);
      optionSet('#shipmentTrackingSelect2', data.shipments, 'tracking_number', (r) => r.tracking_number);
      $('.lang-options').each(function(){ optionSet(this, data.languages, 'code', (r) => `${r.code} - ${r.name}`); });
    });
  }

  function loadPricingTable() {
    $.getJSON(endpoint('pricing_list'), function (res) {
      if (!res.ok) return;
      const body = $('#pricingTableBody').empty();
      res.data.forEach((r) => body.append(`<tr><td>${r.country_name}</td><td>${r.currency_code || '-'}</td><td>${r.category_title}</td><td>${r.price_amount}</td></tr>`));
    });
  }

  loadOptions();
  loadPricingTable();

  async function initV3Map(elementId, latSel, lngSel) {
    if (!window.ymaps3 || !document.getElementById(elementId)) return;
    await ymaps3.ready;
    const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapListener, YMapMarker } = ymaps3;
    const lat = parseFloat($(latSel).val() || '41.01');
    const lng = parseFloat($(lngSel).val() || '28.97');

    const map = new YMap(document.getElementById(elementId), { location: { center: [lng, lat], zoom: 5 } });
    map.addChild(new YMapDefaultSchemeLayer());
    map.addChild(new YMapDefaultFeaturesLayer());

    const markerEl = document.createElement('div');
    markerEl.style.width = '16px'; markerEl.style.height = '16px'; markerEl.style.borderRadius = '50%'; markerEl.style.background = '#dc2626'; markerEl.style.border = '2px solid #fff';
    let marker = new YMapMarker({ coordinates: [lng, lat] }, markerEl);
    map.addChild(marker);

    map.addChild(new YMapListener({
      layer: 'any',
      onClick: (_obj, event) => {
        const [clng, clat] = event.coordinates;
        $(latSel).val(clat.toFixed(6));
        $(lngSel).val(clng.toFixed(6));
        map.removeChild(marker);
        const m2 = document.createElement('div');
        m2.style.width='16px';m2.style.height='16px';m2.style.borderRadius='50%';m2.style.background='#dc2626';m2.style.border='2px solid #fff';
        marker = new YMapMarker({ coordinates: [clng, clat] }, m2);
        map.addChild(marker);
      }
    }));
  }

  initV3Map('mapPicker', '#shipmentLat', '#shipmentLng');
  initV3Map('settingsMap', '#companyLat', '#companyLng');
});
