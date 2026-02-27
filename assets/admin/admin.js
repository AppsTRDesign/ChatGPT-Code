$(function () {
  const endpoint = (name) => '/admin/api/' + name + '.php';
  const statusOptions = [
    {code:'pending_approval',label:'Onay bekleniyor'},
    {code:'approved',label:'Onaylandı'},
    {code:'preparing',label:'Hazırlanıyor'},
    {code:'prepared',label:'Hazırlandı'},
    {code:'in_transit_to_destination',label:'Varış istikametinde'},
    {code:'arrived_destination',label:'Varış noktasına ulaştı'},
    {code:'distribution_center',label:'Dağıtım merkezinde'},
    {code:'shipment_center',label:'Gönderi merkezinde'},
    {code:'out_for_delivery',label:'Gönderiye çıkarıldı'},
    {code:'delivered',label:'Teslim edildi'},
    {code:'cancelled',label:'İptal edildi'}
  ];

  $('#mobileSideBtn').on('click', () => $('#adminSidebar').toggleClass('open'));
  $('.side-toggle').on('click', function(){ $(this).parent().toggleClass('open'); });

  let quill = null;
  if ($('#pageEditor').length && window.Quill) {
    quill = new Quill('#pageEditor', { theme: 'snow' });
  }

  function toast(res){res.ok?toastr.success(res.message):toastr.error(res.message)}

  function submit(selector, api, multipart=false) {
    $(document).on('submit', selector, function(e){
      e.preventDefault();
      if(selector==='#pageForm' && quill){ $('#pageEditorInput').val(quill.root.innerHTML); }
      if(multipart){
        const fd=new FormData(this);
        $.ajax({url:endpoint(api),method:'POST',data:fd,processData:false,contentType:false,dataType:'json'}).done((res)=>{toast(res);loadAll();});
      }else{
        $.post(endpoint(api), $(this).serialize(), (res)=>{toast(res);loadAll();}, 'json');
      }
    });
  }

  ['page_save','menu_save','menu_translation_save','shipment_save','event_save','country_save','category_save','country_translation_save','category_translation_save','price_save','translations_import_json','admin_password','transport_mode_save','weight_price_save','document_save'].forEach((api)=>{
    const map={page_save:'#pageForm',menu_save:'#menuForm',shipment_save:'#shipmentForm',event_save:'#eventForm',country_save:'#countryForm',category_save:'#categoryForm',country_translation_save:'#countryTranslationForm',category_translation_save:'#categoryTranslationForm',price_save:'#priceConfigForm',translations_import_json:'#translationJsonForm',admin_password:'#adminPasswordForm',menu_translation_save:'#menuTranslationForm',transport_mode_save:'#transportModeForm',weight_price_save:'#weightPriceForm',document_save:'#documentForm'};
    if($(map[api]).length) submit(map[api],api,api==='settings_save' || api==='document_save');
  });
  submit('#settingsForm','settings_save',true);

  function fill(sel,rows,val,label,keepEmpty=false){
    const e=$(sel); if(!e.length)return;
    const empty=keepEmpty?e.find('option[value=""]').first().prop('outerHTML')||'':'';
    e.html(empty);
    rows.forEach(r=>e.append(`<option value="${r[val]}">${label(r)}</option>`));
  }

  function loadOptions(){
    $.getJSON(endpoint('options'), (res)=>{
      if(!res.ok) return;
      const d=res.data;
      fill('#menuPageId', d.pages, 'id', r=>`${r.id} - ${r.title}`);
      fill('#menuTranslationId', d.menus || [], 'id', r=>`#${r.id} ${r.title || r.page_title || '-'}`);
      fill('#priceTransportModeId,#weightModeId', d.transport_modes || [], 'id', r=>`${r.title} (${r.multiplier})`);

      if ($('#menuSystemKey').length) {
        const sys = d.system_links || [];
        fill('#menuSystemKey', sys, 'key', r => `${r.label} (${r.url})`);
      }
      fill('#shipmentTrackingSelect', d.shipments, 'tracking_number', r=>r.tracking_number, true);
      fill('#shipmentTrackingSelect2', d.shipments, 'tracking_number', r=>r.tracking_number);
      fill('#countrySelectAdmin,#countrySelectAdmin2,#priceCountryId,#shipmentOriginCountryId,#shipmentDestinationCountryId,#eventCountryId', d.countries, 'id', r=>`${r.name} (${r.currency_code||'-'})`);
      fill('#categorySelectAdmin2,#priceCategoryId', d.categories, 'id', r=>r.title);
      $('.lang-options').each(function(){ fill(this, d.languages, 'code', r=>`${r.code} - ${r.name}`); });
      fill('#jsonLang', d.languages, 'code', r=>`${r.code}`);

      const statusSel=$('#statusCodeSelect').empty();
      const shipmentStatusSel=$('#shipmentStatusCodeSelect').empty();
      statusOptions.forEach(s=>{ statusSel.append(`<option value="${s.code}">${s.label}</option>`); shipmentStatusSel.append(`<option value="${s.code}">${s.label}</option>`); });

      const ctb=$('#countryTableBody').empty(); d.countries.forEach(c=>ctb.append(`<tr><td>${c.id}</td><td>${c.name}</td><td>${c.currency_code||''}</td><td>${c.currency_symbol||''}</td><td><button class='country-edit' data-id='${c.id}'>Düzenle</button> <button class='country-del' data-id='${c.id}'>Sil</button></td></tr>`));
      const catb=$('#categoryTableBody').empty(); d.categories.forEach(c=>catb.append(`<tr><td>${c.id}</td><td>${c.title}</td><td>${c.description||''}</td><td>-</td></tr>`));
      const ltb=$('#languageTableBody').empty(); d.languages.forEach(l=>ltb.append(`<tr><td>${l.code}</td><td>${l.name}</td><td>${l.sort_order||''}</td><td>${Number(l.is_active)===1?'Aktif':'Pasif'}</td><td><button class='lang-edit' data-code='${l.code}'>Düzenle</button> <button class='lang-del' data-code='${l.code}'>Sil</button></td></tr>`));

      const mtb=$('#transportModeTableBody').empty(); (d.transport_modes||[]).forEach(m=>mtb.append(`<tr><td>${m.id}</td><td>${m.mode_key}</td><td>${m.title}</td><td>${m.multiplier}</td><td><button class='mode-edit' data-id='${m.id}'>Düzenle</button> <button class='mode-del' data-id='${m.id}'>Sil</button></td></tr>`));

    });
  }

  function loadPricing(){ $.getJSON(endpoint('pricing_list'),res=>{ if(!res.ok)return; const b=$('#pricingTableBody').empty(); res.data.forEach(r=>b.append(`<tr><td>${r.country_name}</td><td>${r.currency_code||'-'}</td><td>${r.category_title}</td><td>${r.mode_title||r.mode_key||'-'}</td><td>${r.weight_count||0}</td></tr>`));}); }
  function loadWeightPrices(){ $.getJSON(endpoint('weight_price_list'),res=>{ if(!res.ok)return; const b=$('#weightPriceTableBody').empty(); res.data.forEach(r=>b.append(`<tr><td>${r.id}</td><td>${r.mode_title}</td><td>${r.weight_limit}</td><td>${r.price_amount}</td><td><button class='wp-edit' data-id='${r.id}'>Düzenle</button> <button class='wp-del' data-id='${r.id}'>Sil</button></td></tr>`));}); }
  function loadShipments(){ $.getJSON(endpoint('shipment_list'),res=>{ if(!res.ok)return; const b=$('#shipmentTableBody').empty(); res.data.forEach(r=>b.append(`<tr><td>${r.id}</td><td>${r.tracking_number}</td><td>${r.origin_country} → ${r.destination_country}</td><td>${r.current_status}</td><td><button class='act-del' data-id='${r.id}'>Sil</button> <button class='act-status' data-tr='${r.tracking_number}'>Durum</button></td></tr>`));}); }
  function loadPages(){ $.getJSON(endpoint('page_list'),res=>{ if(!res.ok)return; const b=$('#pageTableBody').empty(); res.data.forEach(r=>b.append(`<tr><td>${r.id}</td><td>${r.title}</td><td>${r.slug}</td><td><button class='page-edit' data-id='${r.id}'>Düzenle</button> <button class='page-del' data-id='${r.id}'>Sil</button></td></tr>`));}); }
  let menuRows = [];
  const systemLinkMap = {
    tracking: {label:'Tracking',url:'/tracking'},
    pricing: {label:'Pricing',url:'/pricing'},
    contact: {label:'Contact',url:'/contact'},
    'active-shipments': {label:'Active Shipments',url:'/active-shipments'},
    'documents': {label:'Documents',url:'/documents'}
  };

  function menuLabel(row){
    if (row.title) return row.title;
    if (row.item_type === 'page') return row.page_title || ('Sayfa #' + row.page_id);
    if (row.item_type === 'system') return (systemLinkMap[row.system_key]||{}).label || row.system_key;
    return row.url || 'Özel Link';
  }

  function bindSortableMenus(){
    $('.menu-children').each(function(){
      if (this.dataset.sortableBound) return;
      new Sortable(this,{group:'menusTree',animation:150,draggable:'> .menu-node'});
      this.dataset.sortableBound='1';
    });
  }

  function renderMenuTree(){
    const tree=$('#menuTree'); if(!tree.length) return;
    const byParent={}; menuRows.forEach(r=>{const k=r.parent_id||0;(byParent[k]=byParent[k]||[]).push(r);});
    Object.values(byParent).forEach(arr=>arr.sort((a,b)=>(a.sort_order-b.sort_order)||(a.id-b.id)));
    const draw=(pid=0)=>{
      const list=(byParent[pid]||[]); if(!list.length) return '<ul class="menu-children" data-parent="'+pid+'"></ul>';
      return '<ul class="menu-children" data-parent="'+pid+'">'+list.map(item=>`<li class="menu-node" data-id="${item.id}"><div class="menu-node-row"><span class="drag-handle">↕</span><strong>${menuLabel(item)}</strong><small>${item.item_type}</small><span class="menu-status ${Number(item.is_active)===1?'':'off'}">${Number(item.is_active)===1?'Aktif':'Pasif'}</span><div class="menu-node-actions"><button type="button" class="menu-edit btn-sm" data-id="${item.id}">Düzenle</button><button type="button" class="menu-del btn-sm btn-danger" data-id="${item.id}">Sil</button></div></div>${draw(item.id)}</li>`).join('')+'</ul>';
    };
    tree.html(draw(0));
    bindSortableMenus();

    const tbody=$('#menuTableBody').empty();
    menuRows.forEach(r=>{
      const parent=menuRows.find(x=>x.id==r.parent_id);
      tbody.append(`<tr><td>${r.id}</td><td>${menuLabel(r)}</td><td>${r.item_type}</td><td>${parent?menuLabel(parent):'-'}</td><td><button type="button" class="menu-edit" data-id="${r.id}">Düzenle</button> <button type="button" class="menu-del" data-id="${r.id}">Sil</button></td></tr>`);
    });

    const parentSel=$('#menuParentId');
    if(parentSel.length){
      const keep=parentSel.val()||'';
      parentSel.html('<option value="">Üst Menü Yok (Ana Menü)</option>');
      menuRows.forEach(r=>parentSel.append(`<option value="${r.id}">${menuLabel(r)}</option>`));
      parentSel.val(keep);
    }
  }

  function toggleMenuTypeFields(){
    const type=$('#menuItemType').val();
    $('#menuPageField').toggle(type==='page');
    $('#menuSystemField').toggle(type==='system');
    $('#menuUrlField').toggle(type==='custom');
    $('#menuTitleField').show();
  }

  function loadMenus(){
    $.getJSON(endpoint('menu_list'),res=>{
      if(!res.ok)return;
      menuRows=res.data||[];
      renderMenuTree();
    });
  }


  function loadDocuments(){ $.getJSON(endpoint('document_list'),res=>{ if(!res.ok)return; const b=$('#documentTableBody').empty(); res.data.forEach(r=>b.append(`<tr><td>${r.id}</td><td>${r.title||'-'}</td><td><a href='${r.file_path}' target='_blank'>${r.file_name||'Dosya'}</a></td><td><button class='doc-edit' data-id='${r.id}'>Düzenle</button> <button class='doc-del' data-id='${r.id}'>Sil</button></td></tr>`));}); }

  const translationPager = {page:1,totalPages:1,lang:''};
  function loadTranslationRows(resetPage=false){
    if(!$('#translationTableBody').length) return;
    if(resetPage) translationPager.page = 1;
    translationPager.lang = $('#languageEditCode').val() || '';
    if(!translationPager.lang) return;
    $.getJSON(endpoint('translations_list'), {lang:translationPager.lang, q:$('#translationSearch').val()||'', page:translationPager.page, per_page:20}, (res)=>{
      if(!res.ok) return toast(res);
      const b=$('#translationTableBody').empty();
      (res.data.rows||[]).forEach(r=>b.append(`<tr><td>${r.id}</td><td>${r.group_name}</td><td>${r.key_name}</td><td>${(r.text_value||'').replace(/</g,'&lt;')}</td><td><button type='button' class='tr-edit' data-group='${r.group_name}' data-key='${r.key_name}' data-text="${String(r.text_value||'').replace(/"/g,'&quot;')}">Düzenle</button></td></tr>`));
      translationPager.page = res.data.pagination.page;
      translationPager.totalPages = res.data.pagination.total_pages;
      $('#translationPageInfo').text(`${translationPager.page} / ${translationPager.totalPages}`);
    });
  }

  function loadAll(){ loadOptions(); loadPricing(); loadWeightPrices(); loadShipments(); loadPages(); loadMenus(); loadTranslationRows(); loadDocuments(); }
  loadAll();

  const qp = new URLSearchParams(window.location.search);
  const editShipmentId = qp.get('edit_id');
  if (qp.get('tab') === 'shipments' && qp.get('tracking') && $('#shipmentTrackingSelect2').length) {
    $('#shipmentTrackingSelect2').val(qp.get('tracking'));
  }
  if (qp.get('tab') === 'countries' && qp.get('edit_id') && $('#countryForm').length) {
    $.getJSON(endpoint('country_get'), {id:qp.get('edit_id')}, (res)=>{ if(!res.ok)return; const d=res.data; $('#countryForm [name=country_id]').val(d.id); $('#countryForm [name=name]').val(d.name); $('#countryForm [name=currency_code]').val(d.currency_code); $('#countryForm [name=currency_symbol]').val(d.currency_symbol||'$'); $('#countryForm [name=is_active]').val(String(d.is_active??1)); });
  }


  $(document).on('change','#menuItemType',toggleMenuTypeFields);

  $(document).on('click','#saveMenuOrder',function(){
    const tree=[];
    const walk = ($ul, parentId=null) => {
      $ul.children('.menu-node').each(function(idx){
        const id=parseInt($(this).data('id'),10);
        tree.push({id:id, parent_id: parentId, sort_order: idx+1});
        const $child=$(this).children('.menu-children').first();
        if($child.length){ walk($child, id); }
      });
    };
    walk($('#menuTree > .menu-children').first(), null);
    $.post(endpoint('menu_reorder'), {csrf:window.CSRF_TOKEN, tree:tree}, (res)=>{toast(res);loadMenus();},'json');
  });
  $(document).on('click','.act-del',function(){ $.post(endpoint('shipment_delete'), {csrf:window.CSRF_TOKEN,id:$(this).data('id')}, (res)=>{toast(res);loadShipments();}, 'json'); });
  $(document).on('click','.act-status',function(){ window.location.href='?tab=shipments&sub=status&tracking='+encodeURIComponent($(this).data('tr')); });
  $(document).on('click','.page-del',function(){ $.post(endpoint('page_delete'), {csrf:window.CSRF_TOKEN,id:$(this).data('id')}, (res)=>{toast(res);loadPages();}, 'json');});
  $(document).on('click','.page-edit',function(){ window.location.href='?tab=pages&sub=edit&id='+$(this).data('id'); });


  $(document).on('click','.menu-edit',function(){
    const id=$(this).data('id');
    const m=menuRows.find(x=>x.id==id); if(!m) return;
    $('#menuItemId').val(m.id);
    $('#menuItemType').val(m.item_type || 'page');
    $('#menuPageId').val(m.page_id || '');
    $('#menuSystemKey').val(m.system_key || '');
    $('#menuForm [name="title"]').val(m.title || '');
    $('#menuForm [name="url"]').val(m.url || '');
    $('#menuParentId').val(m.parent_id || '');
    $('#menuForm [name="is_active"]').val(String(m.is_active ?? 1));
    applyAutoLabels();
    toggleMenuTypeFields();
    $('html,body').animate({scrollTop:$('#menuForm').offset().top-80},300);
  });
  $(document).on('click','.menu-del',function(){
    $.post(endpoint('menu_delete'), {csrf:window.CSRF_TOKEN,id:$(this).data('id')}, (res)=>{toast(res);loadMenus();loadOptions();}, 'json');
  });
  $(document).on('click','#menuFormReset',function(){
    $('#menuForm')[0].reset(); $('#menuItemId').val(''); toggleMenuTypeFields();
  });




  $(document).on('click','.country-edit',function(){ window.location.href='?tab=countries&sub=new&edit_id='+$(this).data('id'); });
  $(document).on('click','.country-del',function(){
    $.post(endpoint('country_delete'), {csrf:window.CSRF_TOKEN,id:$(this).data('id')}, (res)=>{toast(res);loadAll();}, 'json');
  });



  $(document).on('click','.wp-edit',function(){
    $.getJSON(endpoint('weight_price_get'), {id:$(this).data('id')}, (res)=>{
      if(!res.ok) return toast(res);
      const d=res.data;
      $('#weightPriceForm [name=id]').val(d.id);
      $('#weightPriceForm [name=transport_mode_id]').val(d.transport_mode_id);
      $('#weightPriceForm [name=weight_limit]').val(d.weight_limit);
      $('#weightPriceForm [name=price_amount]').val(d.price_amount);
    });
  });
  $(document).on('click','.wp-del',function(){
    $.post(endpoint('weight_price_delete'), {csrf:window.CSRF_TOKEN,id:$(this).data('id')}, (res)=>{toast(res);loadWeightPrices();}, 'json');
  });

  $(document).on('click','.mode-edit',function(){
    $.getJSON(endpoint('transport_mode_get'), {id:$(this).data('id')}, (res)=>{
      if(!res.ok) return toast(res);
      const d=res.data; $('#transportModeForm [name=mode_id]').val(d.id); $('#transportModeForm [name=mode_key]').val(d.mode_key); $('#transportModeForm [name=title]').val(d.title); $('#transportModeForm [name=multiplier]').val(d.multiplier); $('#transportModeForm [name=is_active]').val(String(d.is_active ?? 1));
    });
  });
  $(document).on('click','.mode-del',function(){
    $.post(endpoint('transport_mode_delete'), {csrf:window.CSRF_TOKEN,id:$(this).data('id')}, (res)=>{toast(res);loadAll();}, 'json');
  });



  if (qp.get('tab') === 'pages' && qp.get('sub') === 'edit' && $('#pageForm').length) {
    const id=qp.get('id');
    if(id){
      $.getJSON(endpoint('page_get'), {id:id}, (res)=>{
        if(!res.ok) return toast(res);
        $('#pageId').val(res.data.id);
        $('#pageForm [name=lang_code]').val(res.data.lang_code || 'en');
        $('#pageForm [name=title]').val(res.data.title || '');
        if(quill){ quill.root.innerHTML = res.data.content_html || ''; }
      });
    }
  }

  if (qp.get('tab') === 'languages' && qp.get('sub') === 'edit' && $('#languageEditForm').length) {
    const code = qp.get('code');
    if(code){ setTimeout(()=>loadLanguageEditor(code), 250); }
  }
  if (qp.get('tab') === 'languages' && qp.get('sub') === 'new' && $('#newLanguageJson').length) {
    $.getJSON(endpoint('translations_by_lang'), {lang:'en'}, (res)=>{
      if(res.ok){ $('#newLanguageJson').val(JSON.stringify({en:res.data}, null, 2)); }
    });
  }

  if (qp.get('tab') === 'documents' && qp.get('sub') === 'new' && qp.get('edit_id') && $('#documentForm').length) {
    $.getJSON(endpoint('document_get'), {id:qp.get('edit_id')}, (res)=>{
      if(!res.ok) return toast(res);
      $('#documentId').val(res.data.id);
      $('#documentCurrentFile').text('Mevcut dosya: ' + (res.data.file_name || '-'));
    });
  }

  $(document).on('click','.doc-edit',function(){ window.location.href='?tab=documents&sub=new&edit_id='+$(this).data('id'); });
  $(document).on('click','.doc-del',function(){
    $.post(endpoint('document_delete'), {csrf:window.CSRF_TOKEN,id:$(this).data('id')}, (res)=>{toast(res);loadDocuments();}, 'json');
  });

  $(document).on('click','#deleteLogoBtn',function(){
    $('#settingsForm [name=delete_logo]').remove();
    $('#settingsForm').append('<input type="hidden" name="delete_logo" value="1">');
    $('#settingsForm [name=logo_path]').val('');
    toastr.info('Logo silinmek üzere işaretlendi. Kaydet ile onaylayın.');
  });
  $(document).on('click','#deleteFaviconBtn',function(){
    $('#settingsForm [name=delete_favicon]').remove();
    $('#settingsForm').append('<input type="hidden" name="delete_favicon" value="1">');
    $('#settingsForm [name=favicon_path]').val('');
    toastr.info('Favicon silinmek üzere işaretlendi. Kaydet ile onaylayın.');
  });


  $(document).on('click','.lang-edit',function(){ window.location.href='?tab=languages&sub=edit&code='+encodeURIComponent($(this).data('code')); });
  $(document).on('click','.lang-del',function(){
    $.post(endpoint('language_delete'), {csrf:window.CSRF_TOKEN,code:$(this).data('code')}, (res)=>{toast(res);loadAll();}, 'json');
  });

  function loadLanguageEditor(code){
    if(!$('#languageEditForm').length || !code) return;
    $.getJSON(endpoint('language_get'), {code:code}, (res)=>{
      if(!res.ok) return toast(res);
      $('#languageEditCode').val(res.data.code);
      $('#languageEditName').val(res.data.name);
      $('#languageEditSort').val(res.data.sort_order);
      $('#languageEditActive').val(String(res.data.is_active ?? 1));
      $.getJSON(endpoint('translations_by_lang'), {lang:res.data.code}, (r2)=>{
        if(r2.ok){ $('#jsonEditor').val(JSON.stringify({[res.data.code]:r2.data}, null, 2)); loadTranslationRows(true); }
      });
    });
  }

  $(document).on('submit','#languageEditForm', function(e){
    e.preventDefault();
    const formData = $(this).serializeArray();
    $.post(endpoint('language_save'), formData, (res)=>{
      toast(res);
      if(!res.ok) return;
      $.post(endpoint('translations_import_json'), {csrf:window.CSRF_TOKEN, json_payload:$('#jsonEditor').val()}, (r2)=>{ toast(r2); loadAll(); }, 'json');
    }, 'json');
  });
  $(document).on('click','#deleteLanguageBtn', function(){
    const code = $('#languageEditCode').val();
    if(!code) return;
    $.post(endpoint('language_delete'), {csrf:window.CSRF_TOKEN,code:code}, (res)=>{toast(res); if(res.ok) window.location.href='?tab=languages&sub=list';}, 'json');
  });

  $(document).on('submit','#languageForm', function(e){
    if(!$('#newLanguageJson').length) return;
    e.preventDefault();
    const baseJson = $('#newLanguageJson').val().trim();
    const code = ($(this).find('[name=code]').val()||'').toLowerCase();
    const name = $(this).find('[name=name]').val()||'';
    const sort = $(this).find('[name=sort_order]').val()||10;
    const active = $(this).find('[name=is_active]').val()||1;
    $.post(endpoint('language_save'), {csrf:window.CSRF_TOKEN,code:code,name:name,sort_order:sort,is_active:active}, (res)=>{
      toast(res);
      if(!res.ok) return;
      if(baseJson){
        let parsed={};
        try{ parsed=JSON.parse(baseJson); }catch(_e){ toastr.error('JSON formatı hatalı'); return; }
        if(parsed.en && !parsed[code]) parsed[code]=parsed.en;
        $.post(endpoint('translations_import_json'), {csrf:window.CSRF_TOKEN, json_payload:JSON.stringify(parsed)}, (r2)=>{ toast(r2); window.location.href='?tab=languages&sub=list'; }, 'json');
      } else {
        window.location.href='?tab=languages&sub=list';
      }
    }, 'json');
  });


  
  function applyAutoLabels(){ $('form').each(function(){ $(this).find('input,select,textarea').each(function(){ if($(this).attr('type')==='hidden') return; if($(this).prev('label').length) return; const ph=$(this).attr('placeholder'); const nm=$(this).attr('name')||''; const clean=nm.replace(/_/g,' ').replace(/\b\w/g,(m)=>m.toUpperCase()); const txt=ph||clean; if(txt){ $(this).before('<label class=\'form-label\'>'+txt+'</label>'); } }); }); }

  async function initV3Map(mapId, latSel, lngSel, searchInputId, searchBtnId) {
    if (!window.ymaps3 || !document.getElementById(mapId)) return;
    await ymaps3.ready;
    const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapMarker, YMapListener } = ymaps3;
    let lat=parseFloat($(latSel).val()||'41.01'), lng=parseFloat($(lngSel).val()||'28.97');
    const map = new YMap(document.getElementById(mapId), { location: { center: [lng, lat], zoom: 5 } });
    map.addChild(new YMapDefaultSchemeLayer()); map.addChild(new YMapDefaultFeaturesLayer());
    const mkEl=document.createElement('div'); mkEl.className='map-pin';
    let marker=new YMapMarker({coordinates:[lng,lat]},mkEl); map.addChild(marker);
    map.addChild(new YMapListener({layer:'any',onClick:(_o,e)=>{const [x,y]=e.coordinates;$(latSel).val(y.toFixed(6));$(lngSel).val(x.toFixed(6));map.removeChild(marker); const el=document.createElement('div');el.className='map-pin'; marker=new YMapMarker({coordinates:[x,y]},el); map.addChild(marker);}}));
    if(searchInputId && searchBtnId){
      $(searchBtnId).off('click').on('click', function(){
        const q=$(searchInputId).val(); if(!q) return;
        $.getJSON('/api/yandex_geocode.php', {address:q, lang:(document.documentElement.lang||'en_US')}, function(resp){
          const pos = resp?.response?.GeoObjectCollection?.featureMember?.[0]?.GeoObject?.Point?.pos;
          if(!pos) return toastr.error('Adres bulunamadı');
          const [x,y]=pos.split(' ').map(Number);
          $(latSel).val(y.toFixed(6)); $(lngSel).val(x.toFixed(6));
          map.setLocation({center:[x,y], zoom:9});
          map.removeChild(marker); const el=document.createElement('div');el.className='map-pin'; marker=new YMapMarker({coordinates:[x,y]},el); map.addChild(marker);
        });
      });
    }
  }

  applyAutoLabels();
  toggleMenuTypeFields();

  initV3Map('mapPickerOrigin','#shipmentOriginLat','#shipmentOriginLng','#mapSearchOriginInput','#mapSearchOriginBtn');
  initV3Map('mapPickerDestination','#shipmentDestinationLat','#shipmentDestinationLng','#mapSearchDestinationInput','#mapSearchDestinationBtn');
  initV3Map('eventMap','#eventLat','#eventLng','#eventMapSearchInput','#eventMapSearchBtn');
  initV3Map('settingsMap','#companyLat','#companyLng','#settingsMapSearchInput','#settingsMapSearchBtn');
});
