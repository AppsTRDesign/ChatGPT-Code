(function(){
function ensureToast(){
let el = document.getElementById('pro-ultra-toast');
if(!el){
el = document.createElement('div');
el.id = 'pro-ultra-toast';
el.className = 'pro-ultra-toast';
document.body.appendChild(el);
}
return el;
}

function toast(message){
const el = ensureToast();
el.textContent = message;
el.classList.add('is-visible');
setTimeout(()=>el.classList.remove('is-visible'), 3200);
}

function lang(key, fallback){
 if(typeof ProUltraLang !== 'undefined' && ProUltraLang[key]){
  return ProUltraLang[key];
 }
 return fallback;
}

document.addEventListener('click', function(event){
const target = event.target.closest('[data-pro-ultra-interaction]');
if(target){
event.preventDefault();
const productId = target.getAttribute('data-product');
const nonce = target.getAttribute('data-nonce') || proUltraAI.nonce;
const type = target.getAttribute('data-type') || 'favorite';
fetch(proUltraAI.ajaxUrl, {
method: 'POST',
headers: {'Content-Type': 'application/x-www-form-urlencoded'},
body: new URLSearchParams({
action: 'pro_ultra_toggle_interaction',
product_id: productId,
interaction_type: type,
security: nonce
})
})
.then((res)=>res.json())
.then((res)=>{
const active = res.success && res.data?.active;
target.classList.toggle('is-active', !!active);
const countEl = target.querySelector('[data-count-target]');
if(countEl && res.data?.count !== undefined){
countEl.textContent = res.data.count;
}
 toast(res.data?.message || (active ? lang('added','Added') : lang('removed','Removed')));
if(res.success && typeof proUltraAIChat !== 'undefined'){
fetch(proUltraAI.ajaxUrl, {
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body: new URLSearchParams({
action: proUltraAIChat.trackEventAction,
security: proUltraAI.nonce,
event_type: type,
product_id: productId
})
 });
}

 return;
 });
}
});

// Archive filtering & pagination.
document.addEventListener('DOMContentLoaded', ()=>{
  if(typeof proUltraArchive === 'undefined'){ return; }
  const form = document.querySelector('[data-archive-form]');
  const list = document.querySelector('[data-archive-list]');
  const pagination = document.querySelector('[data-archive-pagination]');
  const loading = document.querySelector('[data-archive-loading]');
  const aside = document.querySelector('[data-archive-aside]');
  const openFilter = document.querySelector('[data-filter-open]');
  const closeFilter = document.querySelector('[data-filter-close]');
  if(!form || !list){ return; }

  const viewButtons = form.querySelectorAll('[data-view]');
  const viewInput = form.querySelector('input[name="view"]');
  const filterSections = form.querySelectorAll('[data-filter-section]');
  const searchInputs = form.querySelectorAll('[data-filter-search]');

  function setView(view){
   const safeView = view === 'list' ? 'list' : 'grid';
   if(viewInput){ viewInput.value = safeView; }
   document.cookie = `pro_ultra_view=${safeView};path=/;`;
   viewButtons.forEach(btn=>btn.setAttribute('aria-pressed', btn.getAttribute('data-view')===safeView ? 'true' : 'false'));
   document.body.classList.remove('pro-ultra-view-grid','pro-ultra-view-list');
   document.body.classList.add(`pro-ultra-view-${safeView}`);
  }

  if(viewButtons.length){
   viewButtons.forEach(btn=>{
    btn.addEventListener('click', ()=>{
     setView(btn.getAttribute('data-view'));
     submitFilters();
    });
   });
  }

  function setLoading(state){
   if(state){
    if(loading){ loading.setAttribute('aria-hidden','false'); }
    list.classList.add('is-loading');
   }else{
    if(loading){ loading.setAttribute('aria-hidden','true'); }
    list.classList.remove('is-loading');
   }
  }

  function submitFilters(page){
   const data = new FormData(form);
   if(page){ data.set('page', page); }
   setLoading(true);
   fetch(proUltraArchive.ajaxUrl, {method:'POST', body:data})
    .then(res=>res.json())
    .then(res=>{
     if(res.success && res.data){
      list.innerHTML = res.data.html;
      if(pagination){ pagination.innerHTML = res.data.pagination || ''; }
      if(res.data.view){ setView(res.data.view); }
     }else{
      toast(res.data?.message || proUltraArchive.errorText);
     }
    })
    .catch(()=>toast(proUltraArchive.errorText))
    .finally(()=>setLoading(false));
  }

  form.addEventListener('change', (event)=>{
   const target = event.target;
   if(target && target.name !== 'security'){
    form.querySelector('input[name="page"]').value = '1';
    submitFilters();
   }
  });

  if(filterSections.length){
    filterSections.forEach(section=>{
      const toggle = section.querySelector('.pro-ultra-filter__toggle');
      const content = section.querySelector('[data-filter-content]');
      if(toggle && content){
        toggle.addEventListener('click', ()=>{
          const expanded = toggle.getAttribute('aria-expanded') === 'true';
          toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
          section.classList.toggle('is-open', !expanded);
          content.style.maxHeight = expanded ? '' : content.scrollHeight + 'px';
        });
      }
    });
  }

  if(searchInputs.length){
    searchInputs.forEach(input=>{
      input.addEventListener('input', ()=>{
        const term = input.value.toLowerCase();
        const type = input.getAttribute('data-filter-search');
        const items = form.querySelectorAll(`[data-filter-item="${type}"]`);
        items.forEach(item=>{
          const label = item.getAttribute('data-label') || '';
          item.style.display = label.indexOf(term) !== -1 ? '' : 'none';
        });
      });
    });
  }

  if(openFilter && aside){
    const close = closeFilter;
    const toggleMobile = (state)=>{
      aside.classList.toggle('is-open', state);
      document.body.classList.toggle('pro-ultra-filter-open', state);
    };
    openFilter.addEventListener('click', ()=>toggleMobile(true));
    if(close){ close.addEventListener('click', ()=>toggleMobile(false)); }
    aside.addEventListener('click',(e)=>{
      if(e.target === aside){ toggleMobile(false); }
    });
  }

  if(pagination){
   pagination.addEventListener('click', (event)=>{
    const link = event.target.closest('a');
    if(link){
     event.preventDefault();
     let page = link.dataset.page;
     if(!page){
      try{
       const url = new URL(link.href);
       page = url.searchParams.get('paged') || url.searchParams.get('page');
      }catch(e){ page = 1; }
     }
     form.querySelector('input[name="page"]').value = page || '1';
     submitFilters(page || '1');
    }
   });
  }

  const presetView = proUltraArchive.view || 'grid';
  setView(presetView);
 });

document.addEventListener('DOMContentLoaded', ()=>{
  const header = document.querySelector('.pro-ultra-header');
  const navToggle = document.querySelector('[data-mobile-nav]');
  const navOverlay = document.querySelector('[data-nav-overlay]');
  const navClose = document.querySelector('[data-nav-close]');
  if(navToggle && navOverlay){
    const toggleNav = (state)=>{
      navOverlay.classList.toggle('is-open', state);
      document.body.classList.toggle('pro-ultra-nav-open', state);
    };
    navToggle.addEventListener('click', ()=>toggleNav(true));
    if(navClose){ navClose.addEventListener('click', ()=>toggleNav(false)); }
    navOverlay.addEventListener('click', (e)=>{
      if(e.target === navOverlay){ toggleNav(false); }
    });
  }
  if(header){
    let lastScroll = 0;
    window.addEventListener('scroll', ()=>{
      const y = window.pageYOffset || 0;
      if(y > 30 && y > lastScroll){
        header.classList.add('is-condensed');
      }else if(y < 10){
        header.classList.remove('is-condensed');
      }
      lastScroll = y;
    }, {passive:true});
  }
});
  document.addEventListener('submit', function(event){
const form = event.target;
if(form.matches('.pro-ultra-auth form')){
event.preventDefault();
const data = new FormData(form);
data.append('action', form.dataset.action);
fetch(proUltraAI.ajaxUrl, {method:'POST', body: data})
.then((res)=>res.json())
.then((res)=>{
 toast(res.data.message || (res.success ? lang('success','Success') : lang('error','Error')));
if(res.success && res.data.redirect){
window.location.href = res.data.redirect;
}
});
}
});

// AI sales assistant UI.
document.addEventListener('DOMContentLoaded', () => {
const chatToggle = document.querySelectorAll('[data-ai-chat-toggle]');
const chatBox = document.querySelector('[data-ai-chat]');
const chatBody = document.querySelector('[data-ai-chat-body]');
const chatInput = document.getElementById('pro-ultra-ai-chat-input');
const chatSend = document.querySelector('[data-ai-chat-send]');

const assistantEnabled = typeof proUltraAIChat !== 'undefined' && proUltraAIChat.enabled;
let chatHistory = [];

function loadHistory(){
try{
const stored = sessionStorage.getItem('proUltraAIChatHistory');
if(stored){ chatHistory = JSON.parse(stored); chatHistory.forEach(renderMessage); }
}catch(e){ chatHistory = []; }
}

function saveHistory(){
try{ sessionStorage.setItem('proUltraAIChatHistory', JSON.stringify(chatHistory)); }catch(e){}
}

function renderMessage(entry){
  if(!chatBody){ return; }
  const row = document.createElement('div');
  row.className = 'pro-ultra-ai-chat-message ' + (entry.role === 'ai' ? 'is-ai' : 'is-user');
  const bubble = document.createElement('span');
  bubble.className = 'pro-ultra-ai-chat-message__bubble';
  bubble.textContent = entry.text;
  row.appendChild(bubble);
  if(entry.link){
    const link = document.createElement('a');
    link.href = entry.link;
    link.className = 'pro-ultra-ai-chat-link';
    link.textContent = link.href;
    row.appendChild(link);
  }
  if(entry.products && entry.products.length){
    row.appendChild(renderProductCards(entry.products));
  }
  chatBody.appendChild(row);
  chatBody.scrollTop = chatBody.scrollHeight;
}

function renderProductCards(items){
  const wrap = document.createElement('div');
  wrap.className = 'pro-ultra-ai-chat-products';
  items.forEach(item=>{
    const card = document.createElement('article');
    card.className = 'pro-ultra-ai-chat-product';
    if(item.thumb){
      const img = document.createElement('img');
      img.src = item.thumb;
      img.alt = item.title;
      card.appendChild(img);
    }
    const title = document.createElement('h5');
    title.textContent = item.title;
    card.appendChild(title);
    if(item.price){
      const price = document.createElement('p');
      price.className = 'price';
      price.textContent = item.price;
      card.appendChild(price);
    }
    const link = document.createElement('a');
    link.href = item.url;
    link.textContent = (proUltraAIChat && proUltraAIChat.viewLabel) ? proUltraAIChat.viewLabel : 'Görüntüle';
    link.className = 'button';
    card.appendChild(link);
    wrap.appendChild(card);
  });
  return wrap;
}

function toggleChat(){
document.body.classList.toggle('pro-ultra-chat-open');
if(document.body.classList.contains('pro-ultra-chat-open') && chatInput){
chatInput.focus();
}
}

if(chatToggle.length){
chatToggle.forEach(btn => btn.addEventListener('click', toggleChat));
}

function sendChat(message){
if(!assistantEnabled || !proUltraAIChat || !chatBody){ return; }
const loadingText = proUltraAIChat.sendLabel || '...';
if(chatSend){ chatSend.disabled = true; chatSend.textContent = loadingText; }

const userEntry = { role: 'user', text: message };
chatHistory.push(userEntry);
renderMessage(userEntry);
saveHistory();

fetch(proUltraAIChat.endpoint, {
method: 'POST',
headers: {'Content-Type': 'application/x-www-form-urlencoded'},
body: new URLSearchParams({
action: proUltraAIChat.chatAction,
security: proUltraAI.nonce,
message: message,
provider: (typeof proUltraAIWriter !== 'undefined') ? proUltraAIWriter.defaultProv : ''
})
})
.then(res=>res.json())
  .then(res=>{
    const payload = res.data || {};
    const text = res.success && payload ? payload.reply : (payload.message || proUltraAIChat.errorText);
    const aiEntry = { role: 'ai', text: text, products: payload.products || [], link: payload.comparison_link };
    chatHistory.push(aiEntry);
    renderMessage(aiEntry);
    saveHistory();
  })
.catch(()=>{
const aiEntry = { role: 'ai', text: proUltraAIChat.errorText };
chatHistory.push(aiEntry);
renderMessage(aiEntry);
saveHistory();
})
.finally(()=>{
if(chatSend){ chatSend.disabled = false; chatSend.textContent = 'Gönder'; }
if(chatInput){ chatInput.value=''; chatInput.focus(); }
});
}

if(chatSend && chatInput){
chatSend.addEventListener('click', () => {
const val = chatInput.value.trim();
if(!val){ return; }
sendChat(val);
});
chatInput.addEventListener('keypress', (e)=>{
if(e.key === 'Enter'){
e.preventDefault();
const val = chatInput.value.trim();
if(val){ sendChat(val); }
}
});
}

if(assistantEnabled && proUltraAIChat && proUltraAIChat.currentProduct){
fetch(proUltraAIChat.endpoint, {
method: 'POST',
headers: {'Content-Type': 'application/x-www-form-urlencoded'},
body: new URLSearchParams({
action: proUltraAIChat.trackEventAction,
security: proUltraAI.nonce,
event_type: 'visited',
product_id: proUltraAIChat.currentProduct.id
})
});
}

loadHistory();

  const aiButton = document.getElementById('pro-ultra-ai-generate-product');
  if(aiButton && typeof proUltraAIWriter !== 'undefined'){
const titleField = document.getElementById('title');
const shortDesc = document.getElementById('excerpt');
const longDesc = document.getElementById('content');
const featuresArea = document.getElementById('pro-ultra-ai-features');
const keywordsArea = document.getElementById('pro-ultra-ai-keywords');
const benefitsArea = document.getElementById('pro-ultra-ai-benefits');

function setEditorValue(content){
if(typeof tinyMCE !== 'undefined' && tinyMCE.get('content')){
tinyMCE.get('content').setContent(content);
}
if(longDesc){
longDesc.value = content;
}
}

aiButton.addEventListener('click', function(event){
event.preventDefault();
if(!titleField || !titleField.value){
toast(proUltraAIWriter.errorTitle);
return;
}
const defaultText = aiButton.dataset.loadingText || '...';
const original = aiButton.textContent;
aiButton.textContent = defaultText;
aiButton.disabled = true;
const payload = new URLSearchParams({
action: 'pro_ultra_ai_product_write',
security: proUltraAIWriter.nonce,
title: titleField.value,
category: document.getElementById('pro-ultra-ai-category')?.value || '',
provider: document.getElementById('pro-ultra-ai-provider')?.value || proUltraAIWriter.defaultProv,
temperature: document.getElementById('pro-ultra-ai-temperature')?.value || proUltraAIWriter.defaultTemp,
max_tokens: document.getElementById('pro-ultra-ai-max-tokens')?.value || proUltraAIWriter.defaultMax,
language: document.getElementById('pro-ultra-ai-language')?.value || proUltraAIWriter.defaultLang
});

fetch(proUltraAI.ajaxUrl, {
method: 'POST',
headers: {'Content-Type': 'application/x-www-form-urlencoded'},
body: payload
})
.then((res)=>res.json())
.then((res)=>{
if(res.success){
const data = res.data.payload;
const seoTitle = data.seo_baslik || '';
const seoDesc = data.seo_meta_aciklama || data.kisa_aciklama || '';

if(titleField && seoTitle){ titleField.value = seoTitle; }
if(shortDesc && data.kisa_aciklama){ shortDesc.value = data.kisa_aciklama; }
if(data.uzun_aciklama){ setEditorValue(data.uzun_aciklama); }
if(featuresArea && Array.isArray(data.urun_ozellikleri)){
featuresArea.value = data.urun_ozellikleri.join('\n');
}
if(keywordsArea && Array.isArray(data.anahtar_kelimeler)){
keywordsArea.value = data.anahtar_kelimeler.join(', ');
const tagInput = document.getElementById('new-tag-product_tag');
if(tagInput){ tagInput.value = data.anahtar_kelimeler.join(','); }
}
if(benefitsArea && data.ne_ise_yarar_metin){
benefitsArea.value = data.ne_ise_yarar_metin;
}

const setSeoField = (selector, value) => {
const el = document.querySelector(selector);
if(!el || !value){ return; }
el.value = value;
if(typeof jQuery !== 'undefined' && typeof jQuery(el).trigger === 'function'){
jQuery(el).trigger('change');
}
};
setSeoField('#yoast_wpseo_title', seoTitle);
setSeoField('#yoast_wpseo_metadesc', seoDesc);
setSeoField('input[name="rank_math_title"], #rank_math_title', seoTitle);
setSeoField('textarea[name="rank_math_description"], #rank_math_description', seoDesc);
setSeoField('input[name="_aioseo_title"]', seoTitle);
setSeoField('textarea[name="_aioseo_description"]', seoDesc);

toast(res.data.message || proUltraAIWriter.successTitle);
}else{
toast(res.data?.message || proUltraAIWriter.errorTitle);
}
})
.catch(()=>toast(proUltraAIWriter.errorTitle))
.finally(()=>{
aiButton.disabled = false;
aiButton.textContent = original;
});
 });
 }

 // AI görsel işlemci: öne çıkan görsele gözlemci.
 const aiImageToggle = document.getElementById('pro-ultra-ai-image-toggle');
 const thumbInput = document.getElementById('_thumbnail_id');
 const postIdField = document.getElementById('post_ID');
 if(aiImageToggle && thumbInput && postIdField && typeof proUltraAIImage !== 'undefined'){
  let lastValue = thumbInput.value;
  const thumbnailButton = document.getElementById('set-post-thumbnail');

  const triggerProcess = () => {
  const current = thumbInput.value;
  if(!aiImageToggle.checked || !current || current === lastValue){
  return;
  }
  lastValue = current;
  const originalText = thumbnailButton ? thumbnailButton.textContent : '';
  if(thumbnailButton){
  thumbnailButton.textContent = thumbnailButton.dataset.loadingText || proUltraAIImage.loadingText;
  thumbnailButton.disabled = true;
  }

  fetch(proUltraAI.ajaxUrl, {
  method: 'POST',
  headers: {'Content-Type': 'application/x-www-form-urlencoded'},
  body: new URLSearchParams({
  action: 'pro_ultra_ai_process_image',
  security: proUltraAIImage.nonce,
  attachment_id: current,
  product_id: postIdField.value
  })
  }).then((res)=>res.json())
  .then((res)=>{
  if(res.success && res.data){
  if(window.wp && wp.media && wp.media.featuredImage && res.data.attachment_id){
  wp.media.featuredImage.set(res.data.attachment_id);
  thumbInput.value = res.data.attachment_id;
  }
  toast(res.data.message || proUltraAIImage.successText);
  }else{
  toast(res.data?.message || proUltraAIImage.errorText);
  }
  }).catch(()=>toast(proUltraAIImage.errorText))
  .finally(()=>{
  if(thumbnailButton){
  thumbnailButton.disabled = false;
  thumbnailButton.textContent = originalText;
  }
  });
  };

  thumbInput.addEventListener('change', triggerProcess);
  const observer = new MutationObserver(triggerProcess);
  observer.observe(thumbInput, { attributes: true, attributeFilter: ['value'] });
 }

 // Admin: AI raporlama modülü.
 const reportApp = document.querySelector('[data-ai-reports]');
  if(reportApp && typeof proUltraAIReports !== 'undefined'){
   const tableBody = reportApp.querySelector('[data-report-list]');
  const detailBox = reportApp.querySelector('[data-report-detail]');
  const statusBox = reportApp.querySelector('[data-report-status]');
  const spinner = reportApp.querySelector('.spinner');
  const generateBtn = reportApp.querySelector('[data-report-generate]');

  const setStatus = (msg, type='info') => {
   if(!statusBox){ return; }
   statusBox.textContent = msg;
   statusBox.className = 'notice notice-' + type;
   statusBox.style.display = 'block';
  };

  const clearStatus = () => { if(statusBox){ statusBox.style.display='none'; } };

  const renderReportRow = (report) => {
   if(!tableBody){ return; }
   const tr = document.createElement('tr');
   tr.setAttribute('data-report-row', report.id);
   tr.innerHTML = `
    <td>${report.title}</td>
    <td>${new Date(report.created_at * 1000).toLocaleString()}</td>
    <td>
      <a class="button" href="${proUltraAIReports.downloadUrl}?action=pro_ultra_ai_download_report&report_id=${encodeURIComponent(report.id)}&security=${encodeURIComponent(proUltraAIReports.nonce)}">PDF İndir</a>
      <button class="button" data-report-view="${report.id}">Özeti Gör</button>
      <button class="button button-link-delete" data-report-delete="${report.id}">Sil</button>
    </td>
   `;
   const empty = tableBody.querySelector('tr td[colspan]');
   if(empty){ empty.parentElement.remove(); }
   tableBody.prepend(tr);
  };

  const charts = [];
  const chartLabels = (proUltraAIReports.labels && proUltraAIReports.labels.charts) || {};
  const renderCharts = ({stats, topProducts, topCategories}) => {
    if(typeof Chart === 'undefined'){ return; }
    while(charts.length){ const chart = charts.pop(); if(chart && chart.destroy){ chart.destroy(); } }
    const dailyEl = detailBox.querySelector('[data-report-chart="daily"]');
    const catEl = detailBox.querySelector('[data-report-chart="categories"]');
    const prodEl = detailBox.querySelector('[data-report-chart="products"]');

    if(dailyEl && stats.daily_labels && stats.daily_orders){
      const ctx = dailyEl.getContext('2d');
      charts.push(new Chart(ctx, {
        type:'line',
        data:{
          labels: stats.daily_labels,
          datasets:[{
            label: 'Sipariş',
            data: stats.daily_orders,
            borderColor: '#6C63FF',
            backgroundColor: 'rgba(108,99,255,0.15)',
            tension:0.35,
            fill:true,
            pointRadius:4
          },{
            label: 'Gelir',
            data: stats.daily_revenue || [],
            borderColor: '#12B886',
            backgroundColor: 'rgba(18,184,134,0.18)',
            tension:0.35,
            fill:true,
            pointRadius:3,
            yAxisID: 'y1'
          }]
        },
        options:{
          responsive:true,
          maintainAspectRatio:false,
          scales:{
            y:{ beginAtZero:true },
            y1:{ beginAtZero:true, position:'right', grid:{ drawOnChartArea:false } }
          },
          plugins:{ legend:{ display:true } }
        }
      }));
    }

    if(catEl && Array.isArray(topCategories) && topCategories.length){
      const ctx = catEl.getContext('2d');
      charts.push(new Chart(ctx, {
        type:'doughnut',
        data:{
          labels: topCategories.map(item=>item.name),
          datasets:[{
            data: topCategories.map(item=>item.quantity),
            backgroundColor:['#6C63FF','#12B886','#FF7E67','#FFCA3A','#3298DC'],
            borderWidth:0
          }]
        },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
      }));
    }

    if(prodEl && Array.isArray(topProducts) && topProducts.length){
      const ctx = prodEl.getContext('2d');
      charts.push(new Chart(ctx, {
        type:'bar',
        data:{
          labels: topProducts.map(item=>item.name),
          datasets:[{
            label: 'Adet',
            data: topProducts.map(item=>item.quantity),
            backgroundColor:'#3298DC'
          }]
        },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true } } }
      }));
    }
  };

  const renderDetail = (report) => {
   if(!detailBox){ return; }
   const ai = report.ai || {};
   const stats = report.stats || {};
   const topProducts = Array.isArray(stats.top_products) ? stats.top_products : [];
   const topCategories = Array.isArray(stats.top_categories) ? stats.top_categories : [];
   detailBox.innerHTML = `
    <h2>${report.title}</h2>
    <p><strong>Genel Özet:</strong> ${ai.genel_ozet || ''}</p>
    <div class="pro-ultra-report-grid">
      <div>
        <h3>Kategori Bazlı Satış</h3>
        <ul>${(ai.kategori_bazli_satis||[]).map(item=>`<li>${item}</li>`).join('')}</ul>
      </div>
      <div>
        <h3>Fiyat Optimizasyon</h3>
        <ul>${(ai.fiyat_optimizasyon_onerileri||[]).map(item=>`<li>${item}</li>`).join('')}</ul>
      </div>
      <div>
        <h3>Kampanya Önerileri</h3>
        <ul>${(ai.kampanya_onerileri||[]).map(item=>`<li>${item}</li>`).join('')}</ul>
      </div>
      <div>
        <h3>Stok Önceliklendirme</h3>
        <ul>${(ai.stok_onceliklendirme||[]).map(item=>`<li>${item}</li>`).join('')}</ul>
      </div>
    </div>
    <p><strong>Toplam Sipariş:</strong> ${stats.total_orders || 0} | <strong>Toplam Gelir:</strong> ${stats.total_revenue || ''} | <strong>İptal/terk:</strong> ${stats.abandoned_cart_ratio || ''}</p>
    <div class="pro-ultra-report-charts">
      <div class="pro-ultra-report-card">
        <h3>${chartLabels.trends || 'Satış Trendleri'}</h3>
        <canvas data-report-chart="daily"></canvas>
      </div>
      <div class="pro-ultra-report-card">
        <h3>${chartLabels.categories || 'Kategori Payları'}</h3>
        <canvas data-report-chart="categories"></canvas>
      </div>
      <div class="pro-ultra-report-card">
        <h3>${chartLabels.products || 'İlk 5 Ürün'}</h3>
        <canvas data-report-chart="products"></canvas>
      </div>
    </div>
   `;
   detailBox.style.display = 'block';
   renderCharts({stats, topProducts, topCategories});
  };

  const toggleLoading = (state) => {
   if(generateBtn){ generateBtn.disabled = state; }
   if(spinner){ spinner.style.visibility = state ? 'visible' : 'hidden'; }
  };

  const existing = proUltraAIReports.reports || [];
  existing.forEach(renderDetail);

  reportApp.addEventListener('click', (event) => {
   const target = event.target;
   if(target.matches('[data-report-generate]')){
    event.preventDefault();
    clearStatus();
    toggleLoading(true);
    setStatus(proUltraAIReports.labels.creating, 'info');
    fetch(proUltraAI.ajaxUrl, {
     method: 'POST',
     headers: {'Content-Type': 'application/x-www-form-urlencoded'},
     body: new URLSearchParams({
      action: proUltraAIReports.generate,
      security: proUltraAIReports.nonce
     })
    }).then(res=>res.json()).then(res=>{
     if(res.success && res.data && res.data.report){
      renderReportRow(res.data.report);
      renderDetail(res.data.report);
      toast(proUltraAIReports.labels.success);
      setStatus(proUltraAIReports.labels.success, 'success');
     }else{
      toast(res.data?.message || proUltraAIReports.labels.error);
      setStatus(res.data?.message || proUltraAIReports.labels.error, 'error');
     }
    }).catch(()=>{
     toast(proUltraAIReports.labels.error);
     setStatus(proUltraAIReports.labels.error, 'error');
    }).finally(()=>toggleLoading(false));
   }

   if(target.matches('[data-report-delete]')){
    event.preventDefault();
    const id = target.getAttribute('data-report-delete');
    fetch(proUltraAI.ajaxUrl, {
     method: 'POST',
     headers: {'Content-Type': 'application/x-www-form-urlencoded'},
     body: new URLSearchParams({
      action: proUltraAIReports.delete,
      security: proUltraAIReports.nonce,
      report_id: id
     })
    }).then(res=>res.json()).then(res=>{
     if(res.success){
      const row = tableBody ? tableBody.querySelector(`[data-report-row="${id}"]`) : null;
      if(row){ row.remove(); }
      toast(res.data?.message || lang('deleted','Deleted'));
     }else{
      toast(res.data?.message || lang('error','Error'));
     }
    });
   }

  if(target.matches('[data-report-view]')){
    event.preventDefault();
    const id = target.getAttribute('data-report-view');
    fetch(proUltraAI.ajaxUrl, {
     method:'POST',
     headers:{'Content-Type':'application/x-www-form-urlencoded'},
     body:new URLSearchParams({
      action: proUltraAIReports.fetch,
      security: proUltraAIReports.nonce,
      report_id: id
     })
    }).then(res=>res.json()).then(res=>{
     if(res.success && res.data && res.data.report){
      renderDetail(res.data.report);
     }else{
      toast(res.data?.message || proUltraAIReports.labels.noData);
   }
  });
 }

 });
 }

 // Single product AI önerileri.
 const aiWrapper = document.querySelector('[data-ai-suggestions]');
 if(aiWrapper && typeof proUltraSingle !== 'undefined'){
  const list = aiWrapper.querySelector('[data-ai-suggestions-list]');
  const loading = aiWrapper.querySelector('[data-ai-suggestions-loading]');
  const refresh = document.querySelector('[data-ai-refresh]');

  const render = (items=[]) => {
   if(!list){ return; }
   list.innerHTML = '';
   if(!items.length){
    const empty = document.createElement('p');
    empty.className = 'pro-ultra-muted';
    empty.textContent = proUltraSingle.errorText;
    list.appendChild(empty);
    return;
   }

   items.forEach(item => {
    const card = document.createElement('article');
    card.className = 'pro-ultra-ai-card';
    card.innerHTML = `
     <div class="pro-ultra-ai-card__body">
       <h4>${item.title || ''}</h4>
       <p>${item.reason || ''}</p>
       ${item.price ? `<div class="pro-ultra-ai-card__price">${item.price}</div>` : ''}
     </div>
     ${item.link ? `<a class="pro-ultra-ai-card__link" href="${item.link}">${item.cta || 'İncele'}</a>` : ''}
    `;
    list.appendChild(card);
   });
  };

  const toggleLoading = (state) => {
   if(!loading){ return; }
   loading.style.display = state ? 'flex' : 'none';
  };

  const loadSuggestions = () => {
   toggleLoading(true);
   fetch(proUltraSingle.ajaxUrl, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
     action: 'pro_ultra_ai_product_suggest',
     security: proUltraSingle.nonce,
     product_id: proUltraSingle.productId
    })
   }).then(res=>res.json()).then(res=>{
    toggleLoading(false);
    if(res.success && res.data){
     render(res.data.items || []);
     toast(res.data.fallback ? proUltraSingle.errorText : proUltraSingle.success);
    }else{
     toast(proUltraSingle.errorText);
    }
   }).catch(()=>{
    toggleLoading(false);
    toast(proUltraSingle.errorText);
   });
  };

  loadSuggestions();
 if(refresh){
  refresh.addEventListener('click', (e)=>{ e.preventDefault(); loadSuggestions(); });
  }
 }
});

// Checkout coupon handling.
document.addEventListener('DOMContentLoaded', () => {
 if (typeof proUltraCheckout === 'undefined') { return; }

 const applyBtn = document.querySelector('[data-checkout-coupon-apply]');
 const codeInput = document.getElementById('pro-ultra-coupon');
 const appliedBox = document.querySelector('[data-checkout-applied]');
 const reviewBox = document.querySelector('[data-checkout-review]');

 const setLoading = (state) => {
  if(applyBtn){
   applyBtn.disabled = !!state;
   applyBtn.classList.toggle('is-loading', !!state);
  }
 };

 const renderReview = (html) => {
  if(reviewBox && html){
   reviewBox.innerHTML = html;
  }
 };

 const renderApplied = (text) => {
  if(appliedBox){ appliedBox.textContent = text || ''; }
 };

 const requestCoupon = (action, code) => {
  const params = new URLSearchParams({
   action,
   security: proUltraCheckout.nonce,
  });
  if(code){ params.append('coupon_code', code); }

  return fetch(proUltraCheckout.ajaxUrl, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: params })
   .then(res=>res.json())
   .then(res=>{
    if(res.success && res.data){
     renderReview(res.data.order_review);
     renderApplied(res.data.cart_total || '');
     toast(res.data.message || proUltraCheckout.labels.success);
     return res;
    }
    throw new Error(res.data?.message || proUltraCheckout.labels.error);
   });
 };

 if(applyBtn && codeInput){
  applyBtn.addEventListener('click', (event)=>{
   event.preventDefault();
   const code = codeInput.value.trim();
   if(!code){ toast(proUltraCheckout.labels.emptiable); return; }
   setLoading(true);
   requestCoupon('pro_ultra_checkout_apply_coupon', code)
    .catch(err=>toast(err.message || proUltraCheckout.labels.error))
    .finally(()=>setLoading(false));
  });
 }

 document.addEventListener('click', (event)=>{
  const link = event.target.closest('.woocommerce-remove-coupon');
  if(link){
   event.preventDefault();
   const code = link.dataset.coupon || '';
   requestCoupon('pro_ultra_checkout_remove_coupon', code).catch(err=>toast(err.message || proUltraCheckout.labels.error));
  }
 });
});

// Cart + mini cart + cross-sell.
document.addEventListener('DOMContentLoaded', () => {
 if (typeof proUltraCart === 'undefined') { return; }

 const miniCart = document.querySelector('[data-mini-cart]');
 const toggleButtons = document.querySelectorAll('[data-cart-toggle]');
 const closeButtons = document.querySelectorAll('[data-cart-close]');
 const itemBox = miniCart ? miniCart.querySelector('[data-mini-cart-items]') : null;
 const totalBox = miniCart ? miniCart.querySelector('[data-cart-total]') : null;
 const countTargets = document.querySelectorAll('[data-cart-count]');
 const suggestionLists = document.querySelectorAll('[data-cart-suggestion-list]');
 const suggestRefresh = document.querySelector('[data-cart-suggest-refresh]');
 const summaryBox = document.querySelector('[data-cart-summary]');

 const toastMsg = (msg) => { if (typeof toast === 'function') { toast(msg); } };

 function toggleMiniCart(open){
  if(!miniCart){ return; }
  document.body.classList.toggle('pro-ultra-mini-cart-open', !!open);
  miniCart.setAttribute('aria-hidden', open ? 'false' : 'true');
 }

 toggleButtons.forEach(btn => btn.addEventListener('click', (e)=>{ e.preventDefault(); toggleMiniCart(true); loadSuggestions(); }));
 closeButtons.forEach(btn => btn.addEventListener('click', (e)=>{ e.preventDefault(); toggleMiniCart(false); }));
 if(miniCart){ miniCart.addEventListener('click', (e)=>{ if(e.target.dataset.cartClose !== undefined || e.target === miniCart.querySelector('[data-cart-close]') || e.target.classList.contains('pro-ultra-mini-cart__backdrop')){ toggleMiniCart(false); } }); }

 function renderSuggestions(items){
  suggestionLists.forEach(list => {
   if(!list){ return; }
   list.innerHTML = '';
   if(!items || !items.length){
    const p = document.createElement('p');
    p.className = 'pro-ultra-muted';
    p.textContent = proUltraCart.labels ? (proUltraCart.labels.empty || 'Öneri yok') : 'Öneri yok';
    list.appendChild(p);
    return;
   }
   items.forEach(item => {
    const card = document.createElement('article');
    card.className = 'pro-ultra-cart__ai-card';
    card.innerHTML = `
     <h4>${item.title || ''}</h4>
     ${item.reason ? `<p>${item.reason}</p>` : ''}
     ${item.price ? `<div class="price">${item.price}</div>` : ''}
     ${item.link ? `<a href="${item.link}" class="button is-small">${item.cta || 'İncele'}</a>` : ''}
    `;
    list.appendChild(card);
   });
  });
 }

 function updateCartUI(payload){
  if(!payload){ return; }
  if(itemBox && payload.items_html){ itemBox.innerHTML = payload.items_html; }
  if(totalBox && payload.total_html){ totalBox.innerHTML = payload.total_html; }
  if(summaryBox && payload.summary){ summaryBox.innerHTML = payload.summary; }
  countTargets.forEach(el => { el.textContent = payload.count || '0'; });
 }

 function requestSuggestions(){
  const params = new URLSearchParams({
   action: proUltraCart.suggestAction,
   security: proUltraCart.nonce
  });
  suggestionLists.forEach(list=>{ list.innerHTML = `<div class="pro-ultra-muted">${proUltraCart.labels.loading}</div>`; });
  fetch(proUltraCart.ajaxUrl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: params})
   .then(res=>res.json())
   .then(res=>{
    if(res.success && res.data){
     renderSuggestions(res.data.items || []);
     if(res.data.fallback){ toastMsg(proUltraCart.labels.error); }
    }else{
     renderSuggestions([]);
     toastMsg(proUltraCart.labels.error);
    }
   })
   .catch(()=>{ renderSuggestions([]); toastMsg(proUltraCart.labels.error); });
 }

 function loadSuggestions(){
  if(suggestionLists.length){ requestSuggestions(); }
 }

 if(suggestRefresh){ suggestRefresh.addEventListener('click', (e)=>{ e.preventDefault(); loadSuggestions(); }); }

 function sendCartRequest(params){
  return fetch(proUltraCart.ajaxUrl, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: params })
   .then(res=>res.json())
   .then(res=>{
    if(res.success && res.data){
     updateCartUI(res.data);
     return res;
    }
    throw new Error(res.data?.message || proUltraCart.labels.error);
   });
 }

 function handleAddToCart(productId, quantity=1, variationId=0, extraParams={}){
  const params = new URLSearchParams({ action:'pro_ultra_ajax_add_to_cart', security: proUltraCart.nonce, product_id: productId, quantity: quantity });
  if(variationId){ params.append('variation_id', variationId); }
  Object.entries(extraParams).forEach(([key,value])=>{ params.append(key, value); });
  return sendCartRequest(params)
   .then(res=>{ toggleMiniCart(true); toastMsg(res.data?.message || proUltraCart.labels.added); loadSuggestions(); })
   .catch(err=>{ toastMsg(err.message || proUltraCart.labels.error); });
 }

 function handleRemove(cartKey){
  const params = new URLSearchParams({ action:'pro_ultra_ajax_remove_cart_item', security: proUltraCart.nonce, cart_item_key: cartKey });
  sendCartRequest(params).then(()=>{ toastMsg(lang('removed','Removed')); loadSuggestions(); });
 }

 document.addEventListener('click', (event)=>{
  const removeBtn = event.target.closest('[data-cart-remove]');
  if(removeBtn){ event.preventDefault(); handleRemove(removeBtn.getAttribute('data-cart-remove')); }

  const addBtn = event.target.closest('.add_to_cart_button');
  if(addBtn){
   event.preventDefault();
   const productId = addBtn.getAttribute('data-product_id') || addBtn.dataset.productId || addBtn.dataset.product_id;
   const qty = addBtn.getAttribute('data-quantity') || addBtn.dataset.quantity || 1;
   if(!productId){ toastMsg(proUltraCart.labels.error); return; }
   handleAddToCart(productId, qty);
  }
 });

 const singleForm = document.querySelector('form.cart');
 if(singleForm){
  singleForm.addEventListener('submit', (event)=>{
   const btn = singleForm.querySelector('button[type="submit"].single_add_to_cart_button');
   if(btn){ event.preventDefault(); }
   const formData = new FormData(singleForm);
   const productId = formData.get('add-to-cart') || singleForm.querySelector('[name="add-to-cart"]').value;
   const quantity = formData.get('quantity') || 1;
   const variationId = formData.get('variation_id') || 0;
   const extra = {};
   formData.forEach((value,key)=>{ if(key.startsWith('attribute_')){ extra[key]=value; } });
   handleAddToCart(productId, quantity, variationId, extra);
  });
 }

 loadSuggestions();
});
})();
