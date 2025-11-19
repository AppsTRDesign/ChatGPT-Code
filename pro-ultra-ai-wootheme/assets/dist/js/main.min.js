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
  if(!form || !list){ return; }

  const viewButtons = form.querySelectorAll('[data-view]');
  const viewInput = form.querySelector('input[name="view"]');
  const attributeSelect = form.querySelector('#pro-ultra-attribute');

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
   if(attributeSelect){
    const opt = attributeSelect.selectedOptions[0];
    if(opt && opt.dataset.taxonomy){
     data.append('attribute_tax', opt.dataset.taxonomy);
    }
   }
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
});
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
chatBody.appendChild(row);
chatBody.scrollTop = chatBody.scrollHeight;
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
const text = res.success && res.data ? res.data.reply : (res.data?.message || proUltraAIChat.errorText);
const aiEntry = { role: 'ai', text: text };
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
if(titleField && data.seo_baslik){ titleField.value = data.seo_baslik; }
if(shortDesc && data.kisa_aciklama){ shortDesc.value = data.kisa_aciklama; }
if(data.uzun_aciklama){ setEditorValue(data.uzun_aciklama); }
if(featuresArea && Array.isArray(data.urun_ozellikleri)){
featuresArea.value = data.urun_ozellikleri.map((f)=>`• ${f}`).join('\n');
}
if(keywordsArea && Array.isArray(data.anahtar_kelimeler)){
keywordsArea.value = data.anahtar_kelimeler.join(', ');
const tagInput = document.getElementById('new-tag-product_tag');
if(tagInput){ tagInput.value = data.anahtar_kelimeler.join(','); }
}
if(benefitsArea && data.ne_ise_yarar_metin){
benefitsArea.value = data.ne_ise_yarar_metin;
}
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

  const renderDetail = (report) => {
   if(!detailBox){ return; }
   const ai = report.ai || {};
   const stats = report.stats || {};
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
   `;
   detailBox.style.display = 'block';
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
  if(addBtn && addBtn.dataset.product_id){
   event.preventDefault();
   handleAddToCart(addBtn.dataset.product_id, addBtn.dataset.quantity || 1);
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
