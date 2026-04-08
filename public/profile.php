<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Profile</title><link rel="stylesheet" href="/styles.css"><script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script></head>
<body>
<div class="auth-wrap"><div class="auth-card glass" style="max-width:860px">
  <h2>Player Profile Hub</h2>
  <select id="langSelect" style="max-width:120px;float:right"><option value="tr">TR</option><option value="en">EN</option></select>

  <div class="notif-wrap" id="notifWrap">
    <button id="notifBtn" class="notif-btn">🔔 <span id="notifBtnLabel">Bildirimler</span> <span id="notifBadge" class="notif-badge" style="display:none">0</span></button>
    <div id="notifPanel" class="notif-panel" style="display:none"></div>
  </div>

  <div id="playerCard"></div>
  <div class="bar"><div id="xpBar" class="bar-fill"></div></div><small id="xpText" class="muted"></small>
  <div class="bar"><div id="enBar" class="bar-fill energy"></div></div><small id="enText" class="muted"></small>
  <p id="totalEnergyText" class="muted"></p>

  <div id="nationCard" class="glass" style="padding:10px;margin:10px 0"></div>
  <div style="display:flex;gap:8px;align-items:center;margin:8px 0"><select id="nationSelect" style="max-width:220px"></select><button id="changeNationBtn">Ulus Değiştir (1000 Gold)</button></div>
  <div class="bar"><div id="nationCooldownBar" class="bar-fill"></div></div><small id="nationCooldownText" class="muted"></small>

  <div style="display:flex;gap:8px;align-items:center;margin:8px 0"><input id="energyAmountInput" type="number" min="1" step="1" value="100000" style="max-width:180px"><button id="buyEnergyBtn">Enerjiye Çevir</button></div><small id="buyEnergyHint" class="muted"></small>

  <hr><h3 id="statTitle">Stat Development</h3>
  <div id="generalStats" class="muted" style="margin-bottom:8px"></div>
  <div class="stat-tabs" id="statTabs">
    <button class="stat-tab active" data-stat="strength">💪 <span>Strength</span></button>
    <button class="stat-tab" data-stat="education">📘 <span>Education</span></button>
    <button class="stat-tab" data-stat="endurance">🛡️ <span>Endurance</span></button>
  </div>
  <div id="statCard" class="muted"></div>
  <div class="bar"><div id="statProgressBar" class="bar-fill"></div></div><small id="statCountdown" class="muted"></small>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <button id="startCoinBtn">🪙 Coin ile Başlat</button>
    <button id="startGoldBtn">💎 Gold ile Başlat</button>
    <button id="stopStatBtn">⏹️ Durdur</button>
  </div>

  <h3>Travel</h3>
  <div id="travelCard" class="muted">No active travel.</div>
  <button id="cancelBtn" style="display:none">Cancel Travel</button>
  <p><a href="/dashboard">Dashboard</a> • <a href="/map">Map</a></p>
</div></div>
<div id="toast" class="toast"></div>
<script>
const toastEl = document.getElementById('toast');
let me = null;
let LANG = localStorage.getItem('lang') || '';
let I18N = {};
let isCanceling = false;
let notifItems = [];
let activeStatState = null;
let activeTravelState = null;
let selectedStat = 'strength';
let statPreview = null;

function toast(m,e=false){toastEl.textContent=m;toastEl.className=`toast show ${e?'error':''}`;setTimeout(()=>toastEl.className='toast',2200)}
function fmt(sec){const t=Math.max(0,Number(sec||0));const h=String(Math.floor(t/3600)).padStart(2,'0');const m=String(Math.floor((t%3600)/60)).padStart(2,'0');const s=String(Math.floor(t%60)).padStart(2,'0');return `${h}:${m}:${s}`;}
function detectLang(){const b=(navigator.language||'en').toLowerCase();if(b.startsWith('tr')) return 'tr'; if(b.startsWith('en')) return 'en'; return 'en';}
function t(key,f=''){const p=key.split('.');let c=I18N;for(const k of p)c=c?.[k];return typeof c==='string'?c:(f||key);}
function parseServerDate(s){if(!s) return 0; const d=new Date(String(s).replace(' ','T')); return Number.isNaN(d.getTime())?0:Math.floor(d.getTime()/1000);}
function statLevelFromProfile(p,stat){return Math.max(1, Number(p?.[stat]||0)+1);}
function coinCost(level){return Math.ceil((100 * (level ** 1.8)) * 75);}
function goldCost(level){return Math.ceil((2 * (level ** 1.4)) * 4);}

async function loadLang(){
  LANG = LANG || detectLang();
  langSelect.value = LANG;
  try{ const p=await fetch(`/api/i18n?lang=${LANG}`).then(r=>r.json()); I18N=p?.data||{}; LANG=p?.lang||LANG; localStorage.setItem('lang',LANG);}catch(_){I18N={};}
  notifBtnLabel.textContent = t('ui.notifications','Notifications');
  statTitle.textContent = t('ui.stat_development','Stat Development');
  stopStatBtn.textContent = `⏹️ ${t('ui.stop_stat','Stop')}`;
}

function updateBadge(count){const c=Math.max(0,Number(count||0)); notifBadge.textContent=String(c); notifBadge.style.display=c>0?'inline-flex':'none'; notifBadge.classList.toggle('pulse',c>0);}
function notificationText(n){const d=n.data||{}; if(n.type==='travel_complete') return `✈️ ${t('ui.travel_completed','Travel complete')}: ${d.from_region_name||('#'+(d.from_region_id||'?'))} → ${d.to_region_name||('#'+(d.to_region_id||'?'))}`; if(n.type==='stat_complete') return `📈 ${t('ui.stat_completed','Stat completed')}: ${d.stat||'stat'} Lv ${d.new_level||'?'}`; return `${n.type}`;}
function renderNotifications(){notifPanel.innerHTML=notifItems.length?notifItems.map(n=>`<div class='notif-item ${Number(n.is_read||0)===0?'unread':''}'>${notificationText(n)}</div>`).join(''):`<div class='muted'>${t('ui.no_notifications','No notifications.')}</div>`;}
async function loadNotifications(){const r=await fetch('/api/player/notifications'); if(!r.ok) return; const j=await r.json(); const d=j?.data||{}; notifItems=Array.isArray(d.items)?d.items:[]; updateBadge(Number(d.unread_count||0)); renderNotifications();}
async function markNotificationsRead(){const r=await fetch('/api/player/notifications/read',{method:'POST'}); if(!r.ok) return false; notifItems=notifItems.map(x=>({...x,is_read:1})); updateBadge(0); renderNotifications(); return true;}
async function loadStatPreview(){
  const r = await fetch(`/api/player/stat-preview?stat=${encodeURIComponent(selectedStat)}`);
  if(!r.ok) return null;
  const j = await r.json();
  return j?.data || null;
}
function fmtMinutes(sec){return `${Math.max(1, Math.ceil(Number(sec||0)/60))} dk`;}

function renderStatPanel(){
  if(!me) return;
  document.querySelectorAll('.stat-tab').forEach(el=>el.classList.toggle('active', el.dataset.stat===selectedStat));
  const level = statLevelFromProfile(me, selectedStat);
  const cCost = Number(statPreview?.coins_cost ?? coinCost(level));
  const gCost = Number(statPreview?.gold_cost ?? goldCost(level));
  const cDur = Number(statPreview?.coins_duration_seconds ?? 0);
  const gDur = Number(statPreview?.gold_duration_seconds ?? 0);
  const activeStat = me.active_stat || null;
  const locked = !!activeStat;
  document.querySelectorAll('.stat-tab').forEach(el => { el.disabled = locked; });

  statCard.textContent = `${t('ui.level','Level')} ${level} • ${t(`ui.${selectedStat}`, selectedStat)} • 🪙 ${cCost} / ${fmtMinutes(cDur)} • 💎 ${gCost} / ${fmtMinutes(gDur)}`;
  startCoinBtn.textContent = `🪙 ${t('ui.start_with_coins','Start with Coins')} (${cCost})`;
  startGoldBtn.textContent = `💎 ${t('ui.start_with_gold','Start with Gold')} (${gCost})`;

  if(activeStat && me.stat_finish_time){
    const endTs=parseServerDate(me.stat_finish_time), startTs=parseServerDate(me.stat_started_at), nowTs=Math.floor(Date.now()/1000);
    const remain=Math.max(0,endTs-nowTs), total=Math.max(1,endTs-startTs);
    const progress=Math.max(0,Math.min(100,Math.round(((total-remain)/total)*100)));
    statProgressBar.style.width=`${progress}%`;
    statCountdown.textContent=`${activeStat} ${t('ui.remaining','remaining')}: ${fmt(remain)}`;
    activeStatState={stat:activeStat,remaining:remain,total};
  } else {
    statProgressBar.style.width='0%';
    statCountdown.textContent=t('ui.no_active_stat','No active development');
    activeStatState=null;
  }

  startCoinBtn.disabled = locked || Number(me.coins) < cCost;
  startGoldBtn.disabled = locked || Number(me.gold) < gCost;
  stopStatBtn.disabled = !locked;
}

function tickLocalCooldowns(){
  if(activeStatState && activeStatState.remaining>0){
    activeStatState.remaining -= 1;
    const progress=Math.max(0,Math.min(100,Math.round(((activeStatState.total-activeStatState.remaining)/Math.max(1,activeStatState.total))*100)));
    statProgressBar.style.width=`${progress}%`;
    statCountdown.textContent=`${activeStatState.stat} ${t('ui.remaining','remaining')}: ${fmt(activeStatState.remaining)}`;
  }
  if(activeTravelState && activeTravelState.remaining>0){
    activeTravelState.remaining -= 1;
    const st=activeTravelState.status==='returning'?t('ui.returning','Returning'):t('ui.traveling','Traveling');
    travelCard.innerHTML=`<p>${activeTravelState.from} → ${activeTravelState.to}</p><p>${st}... ${fmt(activeTravelState.remaining)} ${t('ui.remaining','remaining')}</p><div class='bar'><div class='bar-fill' style='width:${activeTravelState.progress}%'></div></div>`;
  }
}

async function load(){
  const res=await fetch('/api/player/me'); if(!res.ok){location.href='/login';return;}
  me=(await res.json()).data;
  const p=me;
  playerCard.innerHTML=`<p><b>${p.username}</b> • Level ${p.level}</p><p>Coins ${Number(p.coins).toFixed(0)} | Gold ${Number(p.gold).toFixed(0)}</p><p>Region: ${p.current_region_name}</p>`;
  nationCard.innerHTML = `<b>Ulus:</b> ${p.nation_name||'-'} ${p.nation_flag_url?`<img src='${p.nation_flag_url}' style='height:14px;vertical-align:middle'>`:''}`;
  xpBar.style.width=`${Math.round((p.xp/p.xp_to_next)*100)}%`; xpText.textContent=`XP ${p.xp}/${p.xp_to_next}`;
  enBar.style.width=`${Math.round((p.instant_energy/p.max_instant_energy)*100)}%`; enText.textContent=`${t('ui.instant_energy','Instant Energy')} ${p.instant_energy}/${p.max_instant_energy}`;
  totalEnergyText.textContent=`${t('ui.total_energy','Total Energy Reserve')}: ${Number(p.total_energy).toFixed(0)}`;
  generalStats.textContent = `${t('ui.strength','Strength')}: ${p.strength} • ${t('ui.education','Education')}: ${p.education} • ${t('ui.endurance','Endurance')}: ${p.endurance}`;

  const reqEnergy=Math.max(1,Number(energyAmountInput.value||100000));
  const needGold=Math.ceil((reqEnergy*1000)/100000);
  buyEnergyBtn.textContent=t('ui.convert_to_energy','Convert to Energy');
  buyEnergyHint.textContent=t('ui.energy_convert_hint','Cost: {gold} gold for +{energy} energy').replace('{gold}',String(needGold)).replace('{energy}',String(reqEnergy));

  const remaining = Number(p.nation_change_remaining_seconds||0), total=30*24*60*60;
  nationCooldownBar.style.width=`${Math.round(Math.max(0,Math.min(1,(total-remaining)/total))*100)}%`;
  nationCooldownText.textContent=remaining>0?`${t('ui.nation_cooldown','Nation cooldown')}: ${fmt(remaining)}`:t('ui.nation_ready','Nation change ready');
  changeNationBtn.disabled=remaining>0||Number(p.gold)<1000;

  if(!nationSelect.dataset.loaded){const countries=(await (await fetch('/api/map/countries')).json()).data||[]; nationSelect.innerHTML=countries.map(c=>`<option value='${c.nation_country_id||c.id}'>${c.country_name||c.name}</option>`).join(''); nationSelect.dataset.loaded='1';}
  if (p.nation_country_id) nationSelect.value=String(p.nation_country_id);

  statPreview = await loadStatPreview();
  renderStatPanel();

  if(p.is_traveling && p.travel){
    const tr=p.travel; cancelBtn.style.display='block';
    const from=tr.from_region_name||('#'+tr.from_region_id), to=tr.to_region_name||('#'+tr.to_region_id);
    const st=tr.status==='returning'?t('ui.returning','Returning'):t('ui.traveling','Traveling');
    travelCard.innerHTML=`<p>${from} → ${to}</p><p>${st}... ${fmt(tr.remaining_seconds)} ${t('ui.remaining','remaining')}</p><div class='bar'><div class='bar-fill' style='width:${tr.progress_percent||0}%'></div></div>`;
    activeTravelState={from,to,status:tr.status,remaining:Number(tr.remaining_seconds||0),progress:Number(tr.progress_percent||0)};
    cancelBtn.disabled=isCanceling||tr.status==='returning';
  } else {cancelBtn.style.display='none'; travelCard.textContent=t('ui.no_active_travel','No active travel.'); activeTravelState=null;}
}

cancelBtn.onclick=async()=>{if(isCanceling||cancelBtn.disabled){toast(t('toast.cancel_in_progress','Canceling...'),true);return;} isCanceling=true; const r=await fetch('/api/player/cancel-travel',{method:'POST'}); const j=await r.json(); isCanceling=false; if(j.error){toast(j.message||j.error,true);return;} toast(j.message||t('toast.travel_reversed','Travel canceled, return started.')); load();};
buyEnergyBtn.onclick=async()=>{const reqEnergy=Math.max(1,Number(energyAmountInput.value||100000)); const needGold=Math.ceil((reqEnergy*1000)/100000); if(Number(me?.gold||0)<needGold){toast(t('errors.not_enough_gold_amount','Not enough gold for requested amount.').replace('{gold}',String(needGold)),true); return;} const r=await fetch('/api/player/buy-energy',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({energy_amount:reqEnergy})}); const j=await r.json(); if(j.error){toast(j.message||j.error,true);return;} toast(j.message||t('toast.buy_energy_success','Energy purchased successfully.')); load();};
startCoinBtn.onclick=async()=>{const r=await fetch('/api/player/start-stat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({stat:selectedStat,mode:'coins'})}); const j=await r.json(); if(j.error){toast(j.message||j.error,true);return;} toast(t('toast.stat_started','Stat development started.')); load();};
startGoldBtn.onclick=async()=>{const r=await fetch('/api/player/start-stat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({stat:selectedStat,mode:'gold'})}); const j=await r.json(); if(j.error){toast(j.message||j.error,true);return;} toast(t('toast.stat_started','Stat development started.')); load();};
stopStatBtn.onclick=async()=>{const r=await fetch('/api/player/stop-stat',{method:'POST'}); const j=await r.json(); if(j.error){toast(j.message||j.error,true);return;} activeStatState=null; statProgressBar.style.width='0%'; statCountdown.textContent=t('ui.no_active_stat','No active development'); toast(t('toast.stat_stopped','Stat development stopped.')); load();};
changeNationBtn.onclick=async()=>{const countryId=Number(nationSelect.value||0); if(!countryId){toast(t('errors.invalid_request','Invalid request'),true);return;} const r=await fetch('/api/player/change-nation',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({country_id:countryId})}); const j=await r.json(); if(j.error){toast(j.message||j.error,true);return;} toast(t('toast.success','Success')); load();};
notifBtn.onclick=async()=>{const open=notifPanel.style.display==='block'; notifPanel.style.display=open?'none':'block'; if(!open){const ok=await markNotificationsRead(); if(!ok) toast('Bildirimler okunamadı (404)',true);}};
document.querySelectorAll('.stat-tab').forEach(btn=>btn.onclick=async()=>{selectedStat=btn.dataset.stat||'strength'; statPreview = await loadStatPreview(); renderStatPanel();});

function initSocket(){
  try {
    const socket = io(window.location.origin, { path: '/socket.io', transports:['websocket'], withCredentials:true, auth:{user_id: me?.id || ''} });
    socket.on('stat_progress', evt=>{
      if(!evt) return;
      const remain = Number(evt.remaining_seconds||0);
      const pct = Number(evt.progress_percent||0);
      if (activeStatState && remain > activeStatState.remaining) return; // prevent backward jitter
      const total = activeStatState?.total || Math.max(1, Math.round(remain / Math.max(0.01, 1 - (pct / 100))));
      activeStatState={stat:evt.active_stat||'stat',remaining:remain,total};
      statCountdown.textContent=`${activeStatState.stat} ${t('ui.remaining','remaining')}: ${fmt(activeStatState.remaining)}`;
      statProgressBar.style.width=`${pct}%`;
      startCoinBtn.disabled=true; startGoldBtn.disabled=true; stopStatBtn.disabled=false;
    });
    socket.on('stat_complete', ()=>load());
    socket.on('travel_complete', ()=>load());
    socket.on('notification_count', evt=>updateBadge(Number(evt?.unread_count||0)));
    socket.on('notification', evt=>{notifItems.unshift({type:evt?.type||'unknown',data:evt?.data||{},is_read:0}); updateBadge(Number(evt?.unread_count||0)); renderNotifications();});
  } catch (_) {}
}

langSelect.onchange=()=>{LANG=langSelect.value||'en'; loadLang().then(load);};
energyAmountInput.oninput=()=>{if(me) load();};
setInterval(tickLocalCooldowns,1000);
setInterval(load,5000);
loadLang().then(async()=>{await load(); await loadNotifications(); initSocket();});
</script></body></html>
