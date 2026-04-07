<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Profile</title><link rel="stylesheet" href="/styles.css"></head>
<body>
<div class="auth-wrap"><div class="auth-card glass" style="max-width:760px">
  <h2>Player Profile Hub</h2>
  <select id="langSelect" style="max-width:120px;float:right">
    <option value="tr">TR</option>
    <option value="en">EN</option>
  </select>
  <div id="playerCard"></div>
  <div class="bar"><div id="xpBar" class="bar-fill"></div></div><small id="xpText" class="muted"></small>
  <div class="bar"><div id="enBar" class="bar-fill energy"></div></div><small id="enText" class="muted"></small>
  <p id="totalEnergyText" class="muted"></p><div id="nationCard" class="glass" style="padding:10px;margin:10px 0"></div><div style="display:flex;gap:8px;align-items:center;margin:8px 0"><select id="nationSelect" style="max-width:220px"></select><button id="changeNationBtn">Ulus Değiştir (1000 Gold)</button></div><div class="bar"><div id="nationCooldownBar" class="bar-fill"></div></div><small id="nationCooldownText" class="muted"></small>
  <div style="display:flex;gap:8px;align-items:center;margin:8px 0"><input id="energyAmountInput" type="number" min="1" step="1" value="100000" style="max-width:180px"><button id="buyEnergyBtn">Buy Energy with Gold</button></div><small id="buyEnergyHint" class="muted"></small>
  <hr>
  <h3>Travel</h3>
  <div id="travelCard" class="muted">No active travel.</div>
  <button id="cancelBtn" style="display:none">Cancel Travel</button>
  <p><a href="/dashboard">Dashboard</a> • <a href="/map">Map</a></p>
</div></div>
<div id="toast" class="toast"></div>
<script>
function toast(m,e=false){toast.textContent=m;toast.className=`toast show ${e?'error':''}`;setTimeout(()=>toast.className='toast',2200)}
function fmt(sec){const s=Math.max(0,Number(sec||0));const m=String(Math.floor(s/60)).padStart(2,'0');const ss=String(s%60).padStart(2,'0');return `${m}:${ss}`;}
let LANG = localStorage.getItem('lang') || '';
let I18N = {};
let isCanceling = false;
function detectLang(){const b=(navigator.language||'en').toLowerCase();if(b.startsWith('tr')) return 'tr'; if(b.startsWith('en')) return 'en'; return 'en';}
async function loadLang(){
  LANG = LANG || detectLang();
  langSelect.value = LANG;
  try{
    const p=await fetch(`/api/i18n?lang=${LANG}`).then(r=>r.json());
    I18N=p?.data||{};
    LANG=p?.lang||LANG;
    localStorage.setItem('lang',LANG);
  }catch(_){I18N={};}
}
function t(key,f=''){const p=key.split('.');let c=I18N;for(const k of p)c=c?.[k];return typeof c==='string'?c:(f||key);}
async function load(){
  const res=await fetch('/api/player/me'); if(!res.ok){location.href='/login';return;}
  const p=(await res.json()).data;
  playerCard.innerHTML=`<p><b>${p.username}</b> • Level ${p.level}</p><p>Coins ${Number(p.coins).toFixed(0)} | Gold ${Number(p.gold).toFixed(0)}</p><p>Region: ${p.current_region_name}</p>`;
  nationCard.innerHTML = `<b>Ulus:</b> ${p.nation_name||'-'} ${p.nation_flag_url?`<img src='${p.nation_flag_url}' style='height:14px;vertical-align:middle'>`:''}`;
  const xpPct=Math.round((p.xp/p.xp_to_next)*100); xpBar.style.width=`${xpPct}%`; xpText.textContent=`XP ${p.xp}/${p.xp_to_next}`;
  const ePct=Math.round((p.instant_energy/p.max_instant_energy)*100); enBar.style.width=`${ePct}%`; enText.textContent=`Instant Energy ${p.instant_energy}/${p.max_instant_energy}`;
  totalEnergyText.textContent = `Total Energy Reserve: ${Number(p.total_energy).toFixed(0)}`;
  const requested = Math.max(1, Number(energyAmountInput.value||100000));
  const estimatedGold = Math.ceil((requested * 1000) / 100000);
  buyEnergyHint.textContent = `Cost: ${estimatedGold} gold for +${requested} total energy`;
  const canBuyEnergy = Number(p.gold) >= estimatedGold;
  buyEnergyBtn.disabled = !canBuyEnergy;
  buyEnergyBtn.textContent = canBuyEnergy ? t('ui.buy_energy','Buy Energy with Gold') : `Need ${estimatedGold} gold`;
  const remaining = Number(p.nation_change_remaining_seconds||0);
  const total = 30*24*60*60;
  const ratio = Math.max(0, Math.min(1, (total-remaining)/total));
  nationCooldownBar.style.width = `${Math.round(ratio*100)}%`;
  const d=Math.floor(remaining/86400), h=Math.floor((remaining%86400)/3600), m=Math.floor((remaining%3600)/60);
  nationCooldownText.textContent = remaining>0 ? `Ulus değişim bekleme: ${d}g ${h}s ${m}d` : 'Ulus değişimi hazır';
  changeNationBtn.disabled = remaining>0 || Number(p.gold)<1000;
  if(!nationSelect.dataset.loaded){
    const countries=(await (await fetch('/api/map/countries')).json()).data||[];
    nationSelect.innerHTML=countries.map(c=>`<option value='${c.nation_country_id||c.id}'>${c.country_name||c.name}</option>`).join('');
    nationSelect.dataset.loaded='1';
  }
  if (p.nation_country_id) nationSelect.value=String(p.nation_country_id);

  if(p.is_traveling && p.travel){
    const t=p.travel; cancelBtn.style.display='block';
    const st=t.status==='returning'?tLang('ui.returning','Returning'):tLang('ui.traveling','Traveling');
    travelCard.innerHTML=`<p>From ${t.from_region_id} → ${t.to_region_id}</p><p>${st}... ${fmt(t.remaining_seconds)} ${tLang('ui.remaining','remaining')}</p><div class='bar'><div id='tBar' class='bar-fill' style='width:${t.status==='returning' ? 100-(t.progress_percent||0) : (t.progress_percent||0)}%'></div></div>`;
    const cancelLocked = isCanceling || t.status==='returning';
    cancelBtn.disabled = cancelLocked;
    cancelBtn.textContent = cancelLocked ? t('ui.canceling','Canceling...') : t('ui.cancel_travel','Cancel Travel');
  }else{cancelBtn.style.display='none'; travelCard.textContent='No active travel.';}
}
function tLang(k,f){return t(k,f);}
cancelBtn.onclick=async()=>{
  if(isCanceling || cancelBtn.disabled){toast(t('toast.cancel_in_progress','Canceling...'),true);return;}
  isCanceling=true; cancelBtn.disabled=true; cancelBtn.textContent=t('ui.canceling','Canceling...');
  const r=await fetch('/api/player/cancel-travel',{method:'POST'});const j=await r.json();
  if(j.error){toast(j.message||t(`errors.${j.error}`,j.error),true);isCanceling=false;cancelBtn.disabled=false;cancelBtn.textContent=t('ui.cancel_travel','Cancel Travel');return;}
  toast(j.message||t('toast.travel_reversed','Travel canceled, return started.'));
  isCanceling=false;cancelBtn.disabled=false;cancelBtn.textContent=t('ui.cancel_travel','Cancel Travel');load();
};
buyEnergyBtn.onclick=async()=>{
  const requested = Math.max(1, Number(energyAmountInput.value||100000));
  const r=await fetch('/api/player/buy-energy',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({energy_amount:requested})});
  const j=await r.json();
  if(j.error){toast(j.message||t(`errors.${j.error}`,j.error),true);return;}
  toast(j.message||t('toast.buy_energy_success','Energy purchased successfully.'));load();
};
energyAmountInput.oninput=()=>load();
changeNationBtn.onclick=async()=>{
  const countryId=Number(nationSelect.value||0);
  if(!countryId){toast('Geçersiz ulus',true);return;}
  const r=await fetch('/api/player/change-nation',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({country_id:countryId})});
  const j=await r.json();
  if(j.error){toast(j.message||j.error,true);return;}
  toast('Ulus değiştirildi');
  load();
};
langSelect.onchange=()=>{LANG=langSelect.value||'en'; loadLang().then(load);};
setInterval(load,1000); loadLang().then(load);
</script></body></html>
