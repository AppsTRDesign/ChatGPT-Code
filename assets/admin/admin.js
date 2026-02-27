$(function () {
  const endpoint = (name) => '/admin/api/' + name + '.php';
  const statusOptions = [
    'pending_approval','approved','preparing','prepared','in_transit_to_destination','arrived_destination','distribution_center','shipment_center','out_for_delivery','delivered','cancelled'
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

  ['page_save','menu_save','shipment_save','event_save','country_save','category_save','country_translation_save','category_translation_save','price_save','language_save','lang_save','translations_import_json','admin_password'].forEach((api)=>{
    const map={page_save:'#pageForm',menu_save:'#menuForm',shipment_save:'#shipmentForm',event_save:'#eventForm',country_save:'#countryForm',category_save:'#categoryForm',country_translation_save:'#countryTranslationForm',category_translation_save:'#categoryTranslationForm',price_save:'#priceConfigForm',language_save:'#languageForm',lang_save:'#langForm',translations_import_json:'#translationJsonForm',admin_password:'#adminPasswordForm'};
    if($(map[api]).length) submit(map[api],api,api==='settings_save');
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

      if ($('#menuSystemKey').length) {
        const sys = d.system_links || [];
        fill('#menuSystemKey', sys, 'key', r => `${r.label} (${r.url})`);
      }
      fill('#shipmentTrackingSelect', d.shipments, 'tracking_number', r=>r.tracking_number, true);
      fill('#shipmentTrackingSelect2', d.shipments, 'tracking_number', r=>r.tracking_number);
      fill('#countrySelectAdmin,#countrySelectAdmin2,#priceCountryId', d.countries, 'id', r=>`${r.name} (${r.currency_code||'-'})`);
      fill('#categorySelectAdmin,#categorySelectAdmin2,#priceCategoryId', d.categories, 'id', r=>r.title);
      $('.lang-options').each(function(){ fill(this, d.languages, 'code', r=>`${r.code} - ${r.name}`); });
      fill('#jsonLang', d.languages, 'code', r=>`${r.code}`);

      const statusSel=$('#statusCodeSelect').empty();
      statusOptions.forEach(s=>statusSel.append(`<option value="${s}">${s}</option>`));

      const ctb=$('#countryTableBody').empty(); d.countries.forEach(c=>ctb.append(`<tr><td>${c.id}</td><td>${c.name}</td><td>${c.currency_code||''}</td></tr>`));
      const catb=$('#categoryTableBody').empty(); d.categories.forEach(c=>catb.append(`<tr><td>${c.id}</td><td>${c.title}</td><td>${c.description||''}</td></tr>`));
      const ltb=$('#languageTableBody').empty(); d.languages.forEach(l=>ltb.append(`<tr><td>${l.code}</td><td>${l.name}</td><td>${l.sort_order||''}</td></tr>`));

    });
  }

  function loadPricing(){ $.getJSON(endpoint('pricing_list'),res=>{ if(!res.ok)return; const b=$('#pricingTableBody').empty(); res.data.forEach(r=>b.append(`<tr><td>${r.country_name}</td><td>${r.currency_code||'-'}</td><td>${r.category_title}</td><td>${r.price_amount}</td></tr>`));}); }
  function loadShipments(){ $.getJSON(endpoint('shipment_list'),res=>{ if(!res.ok)return; const b=$('#shipmentTableBody').empty(); res.data.forEach(r=>b.append(`<tr><td>${r.id}</td><td>${r.tracking_number}</td><td>${r.origin_country} → ${r.destination_country}</td><td>${r.current_status}</td><td><button class='act-edit' data-id='${r.id}'>Düzenle</button> <button class='act-del' data-id='${r.id}'>Sil</button> <button class='act-status' data-tr='${r.tracking_number}'>Durum</button></td></tr>`));}); }
  function loadPages(){ $.getJSON(endpoint('page_list'),res=>{ if(!res.ok)return; const b=$('#pageTableBody').empty(); res.data.forEach(r=>b.append(`<tr><td>${r.id}</td><td>${r.title}</td><td>${r.slug}</td><td><button class='page-edit' data-id='${r.id}'>Düzenle</button> <button class='page-del' data-id='${r.id}'>Sil</button></td></tr>`));}); }
  let menuRows = [];
  const systemLinkMap = {
    tracking: {label:'Tracking',url:'/tracking'},
    pricing: {label:'Pricing',url:'/pricing'},
    contact: {label:'Contact',url:'/contact'},
    'active-shipments': {label:'Active Shipments',url:'/active-shipments'}
  };

  function menuLabel(row){
    if (row.title) return row.title;
    if (row.item_type === 'page') return row.page_title || ('Page #' + row.page_id);
    if (row.item_type === 'system') return (systemLinkMap[row.system_key]||{}).label || row.system_key;
    return row.url || 'Custom';
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
      return '<ul class="menu-children" data-parent="'+pid+'">'+list.map(item=>`<li class="menu-node" data-id="${item.id}"><div class="menu-node-row"><span class="drag-handle">↕</span><strong>${menuLabel(item)}</strong><small>${item.item_type}</small><div class="menu-node-actions"><button type="button" class="menu-edit btn-sm" data-id="${item.id}">Düzenle</button><button type="button" class="menu-del btn-sm btn-danger" data-id="${item.id}">Sil</button></div></div>${draw(item.id)}</li>`).join('')+'</ul>';
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
    $('#menuPageId').closest('select, .field').toggle(type==='page');
    $('#menuSystemKey').closest('select, .field').toggle(type==='system');
    $('#menuForm input[name="url"]').toggle(type==='custom');
  }

  function loadMenus(){
    $.getJSON(endpoint('menu_list'),res=>{
      if(!res.ok)return;
      menuRows=res.data||[];
      renderMenuTree();
    });
  }

  function loadAll(){ loadOptions(); loadPricing(); loadShipments(); loadPages(); loadMenus(); }
  loadAll();

  $(document).on('change','#menuItemType',toggleMenuTypeFields);

  $(document).on('click','#saveMenuOrder',function(){
    const tree=[];
    $('.menu-children').each(function(){
      const parentId=parseInt($(this).data('parent'),10)||0;
      $(this).children('.menu-node').each(function(idx){
        tree.push({id:parseInt($(this).data('id'),10), parent_id: parentId || null, sort_order: idx+1});
      });
    });
    $.post(endpoint('menu_reorder'), {csrf:window.CSRF_TOKEN, tree:tree}, (res)=>{toast(res);loadMenus();},'json');
  });
  $(document).on('click','.act-del',function(){ $.post(endpoint('shipment_delete'), {csrf:window.CSRF_TOKEN,id:$(this).data('id')}, (res)=>{toast(res);loadShipments();}, 'json'); });
  $(document).on('click','.act-edit',function(){ $.getJSON(endpoint('shipment_get'), {id:$(this).data('id')}, (res)=>{ if(!res.ok)return toast(res); const d=res.data; Object.keys(d).forEach(k=>$('#shipmentForm [name='+k+']').val(d[k])); $('#shipmentForm [name=existing_tracking]').val(d.tracking_number); $('html,body').animate({scrollTop:$('#shipmentForm').offset().top-80},300);}); });
  $(document).on('click','.act-status',function(){ $('#shipmentTrackingSelect2').val($(this).data('tr')); $('html,body').animate({scrollTop:$('#eventForm').offset().top-80},300); });
  $(document).on('click','.page-del',function(){ $.post(endpoint('page_delete'), {csrf:window.CSRF_TOKEN,id:$(this).data('id')}, (res)=>{toast(res);loadPages();}, 'json');});
  $(document).on('click','.page-edit',function(){ $('#pageForm [name=page_id]').val($(this).data('id')); $('html,body').animate({scrollTop:$('#pageForm').offset().top-80},300); });


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
    toggleMenuTypeFields();
    $('html,body').animate({scrollTop:$('#menuForm').offset().top-80},300);
  });
  $(document).on('click','.menu-del',function(){
    $.post(endpoint('menu_delete'), {csrf:window.CSRF_TOKEN,id:$(this).data('id')}, (res)=>{toast(res);loadMenus();loadOptions();}, 'json');
  });
  $(document).on('click','#menuFormReset',function(){
    $('#menuForm')[0].reset(); $('#menuItemId').val(''); toggleMenuTypeFields();
  });

  $('#langJsonLoadForm').on('submit', function(e){ e.preventDefault(); $.getJSON(endpoint('translations_by_lang'), {lang:$('#jsonLang').val()}, (res)=>{ if(!res.ok)return toast(res); $('#jsonEditor').val(JSON.stringify({[$('#jsonLang').val()]:res.data},null,2)); });});
  $('#exportTranslationsJson').on('click', ()=>window.open(endpoint('translations_export_json'),'_blank'));

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
        $.getJSON('/api/yandex_geocode.php', {address:q, lang:'tr_TR'}, function(resp){
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

  toggleMenuTypeFields();

  initV3Map('mapPicker','#shipmentLat','#shipmentLng','#mapSearchInput','#mapSearchBtn');
  initV3Map('settingsMap','#companyLat','#companyLng','#settingsMapSearchInput','#settingsMapSearchBtn');
});
