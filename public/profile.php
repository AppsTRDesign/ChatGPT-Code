<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Profile</title><link rel="stylesheet" href="/styles.css"><script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script></head>
<body>
<div class="auth-wrap"><div class="auth-card glass" style="max-width:820px">
  <h2>Player Profile Hub</h2>
  <select id="langSelect" style="max-width:120px;float:right">
    <option value="tr">TR</option>
    <option value="en">EN</option>
  </select>

  <div class="notif-wrap" id="notifWrap">
    <button id="notifBtn" class="notif-btn">🔔 Bildirimler <span id="notifBadge" class="notif-badge" style="display:none">0</span></button>
    <div id="notifPanel" class="notif-panel" style="display:none"></div>
  </div>

  <div id="playerCard"></div>
  <div class="bar"><div id="xpBar" class="bar-fill"></div></div><small id="xpText" class="muted"></small>
  <div class="bar"><div id="enBar" class="bar-fill energy"></div></div><small id="enText" class="muted"></small>
  <p id="totalEnergyText" class="muted"></p>

  <div id="nationCard" class="glass" style="padding:10px;margin:10px 0"></div>
  <div style="display:flex;gap:8px;align-items:center;margin:8px 0"><select id="nationSelect" style="max-width:220px"></select><button id="changeNationBtn">Ulus Değiştir (1000 Gold)</button></div>
  <div class="bar"><div id="nationCooldownBar" class="bar-fill"></div></div><small id="nationCooldownText" class="muted"></small>

  <div style="display:flex;gap:8px;align-items:center;margin:8px 0"><input id="energyAmountInput" type="number" min="1" step="1" value="100000" style="max-width:180px"><button id="buyEnergyBtn">Buy Energy with Gold</button></div><small id="buyEnergyHint" class="muted"></small>

  <hr><h3>Stat Development</h3>
  <div id="statCard" class="muted"></div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <select id="statSelect"><option value="strength">Strength</option><option value="education">Education</option><option value="endurance">Endurance</option></select>
    <select id="statModeSelect"><option value="coins">Coins (Slow)</option><option value="gold">Gold (Fast)</option></select>
    <button id="startStatBtn">Start Stat</button>
  </div>
  <div class="bar"><div id="statProgressBar" class="bar-fill"></div></div><small id="statCountdown" class="muted"></small>

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

function toast(m,e=false){toastEl.textContent=m;toastEl.className=`toast show ${e?'error':''}`;setTimeout(()=>toastEl.className='toast',2200)}
function fmt(sec){const t=Math.max(0,Number(sec||0));const h=String(Math.floor(t/3600)).padStart(2,'0');const m=String(Math.floor((t%3600)/60)).padStart(2,'0');const s=String(Math.floor(t%60)).padStart(2,'0');return `${h}:${m}:${s}`;}
function detectLang(){const b=(navigator.language||'en').toLowerCase();if(b.startsWith('tr')) return 'tr'; if(b.startsWith('en')) return 'en'; return 'en';}
function t(key,f=''){const p=key.split('.');let c=I18N;for(const k of p)c=c?.[k];return typeof c==='string'?c:(f||key);}

function parseServerDate(s){
  if(!s) return 0;
  const clean = String(s).replace(' ', 'T');
  const d = new Date(clean);
  return Number.isNaN(d.getTime()) ? 0 : Math.floor(d.getTime()/1000);
}

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

function renderNotifications(){
  notifPanel.innerHTML = notifItems.length
    ? notifItems.map(n=>`<div class='notif-item ${Number(n.is_read||0)===0?'unread':''}'>${notificationText(n)}</div>`).join('')
    : `<div class='muted'>Bildirim yok.</div>`;
}

function notificationText(n){
  const d=n.data||{};
  if(n.type==='travel_complete'){
    const from=d.from_region_name||('#'+(d.from_region_id||'?'));
    const to=d.to_region_name||('#'+(d.to_region_id||'?'));
    return `✈️ Uçuş tamamlandı: ${from} → ${to}`;
  }
  if(n.type==='stat_complete'){
    return `📈 ${String(d.stat||'stat')} geliştirme tamamlandı. Yeni seviye: ${d.new_level||'?'}.`;
  }
  return `${n.type}`;
}

function updateBadge(count){
  const c = Math.max(0, Number(count||0));
  notifBadge.textContent = String(c);
  notifBadge.style.display = c>0 ? 'inline-flex' : 'none';
  notifBadge.classList.toggle('pulse', c>0);
}

async function loadNotifications(){
  const j = await fetch('/api/player/notifications').then(r=>r.json());
  const d = j?.data || {};
  notifItems = Array.isArray(d.items) ? d.items : [];
  updateBadge(Number(d.unread_count||0));
  renderNotifications();
}

async function markNotificationsRead(){
  await fetch('/api/player/notifications/read',{method:'POST'});
  notifItems = notifItems.map(x=>({ ...x, is_read:1 }));
  updateBadge(0);
  renderNotifications();
}

async function load(){
  const res=await fetch('/api/player/me'); if(!res.ok){location.href='/login';return;}
  me=(await res.json()).data;
  const p = me;

  playerCard.innerHTML=`<p><b>${p.username}</b> • Level ${p.level}</p><p>Coins ${Number(p.coins).toFixed(0)} | Gold ${Number(p.gold).toFixed(0)}</p><p>Region: ${p.current_region_name}</p>`;
  nationCard.innerHTML = `<b>Ulus:</b> ${p.nation_name||'-'} ${p.nation_flag_url?`<img src='${p.nation_flag_url}' style='height:14px;vertical-align:middle'>`:''}`;
  const xpPct=Math.round((p.xp/p.xp_to_next)*100); xpBar.style.width=`${xpPct}%`; xpText.textContent=`XP ${p.xp}/${p.xp_to_next}`;
  const ePct=Math.round((p.instant_energy/p.max_instant_energy)*100); enBar.style.width=`${ePct}%`; enText.textContent=`Instant Energy ${p.instant_energy}/${p.max_instant_energy}`;
  totalEnergyText.textContent = `Total Energy Reserve: ${Number(p.total_energy).toFixed(0)}`;

  const requested = Math.max(1, Number(energyAmountInput.value||100000));
  const estimatedGold = Math.ceil((requested * 1000) / 100000);
  buyEnergyHint.textContent = `Cost: ${estimatedGold} gold for +${requested} total energy`;
  buyEnergyBtn.disabled = Number(p.gold) < estimatedGold;

  const remaining = Number(p.nation_change_remaining_seconds||0);
  const total = 30*24*60*60;
  const ratio = Math.max(0, Math.min(1, (total-remaining)/total));
  nationCooldownBar.style.width = `${Math.round(ratio*100)}%`;
  nationCooldownText.textContent = remaining>0 ? `Ulus değişim bekleme: ${fmt(remaining)}` : 'Ulus değişimi hazır';
  changeNationBtn.disabled = remaining>0 || Number(p.gold)<1000;

  if(!nationSelect.dataset.loaded){
    const countries=(await (await fetch('/api/map/countries')).json()).data||[];
    nationSelect.innerHTML=countries.map(c=>`<option value='${c.nation_country_id||c.id}'>${c.country_name||c.name}</option>`).join('');
    nationSelect.dataset.loaded='1';
  }
  if (p.nation_country_id) nationSelect.value=String(p.nation_country_id);

  const activeStat = p.active_stat || null;
  statCard.textContent = `Strength ${p.strength} • Education ${p.education} • Endurance ${p.endurance}`;
  if(activeStat && p.stat_finish_time){
    const endTs = parseServerDate(p.stat_finish_time);
    const startTs = parseServerDate(p.stat_started_at);
    const nowTs = Math.floor(Date.now()/1000);
    const remain = Math.max(0,endTs-nowTs);
    const totalSec = Math.max(1, endTs - startTs);
    const progress = Math.max(0, Math.min(100, Math.round(((totalSec-remain)/totalSec)*100)));
    statProgressBar.style.width = `${progress}%`;
    statCountdown.textContent = `${activeStat} (${p.stat_mode}) kalan: ${fmt(remain)}`;
    startStatBtn.disabled = true;
  } else {
    statProgressBar.style.width = '0%';
    statCountdown.textContent = 'Aktif geliştirme yok';
    startStatBtn.disabled = false;
  }

  if(p.is_traveling && p.travel){
    const tr=p.travel; cancelBtn.style.display='block';
    const st=tr.status==='returning'?t('ui.returning','Returning'):t('ui.traveling','Traveling');
    const from = tr.from_region_name || ('#'+tr.from_region_id);
    const to = tr.to_region_name || ('#'+tr.to_region_id);
    travelCard.innerHTML=`<p>${from} → ${to}</p><p>${st}... ${fmt(tr.remaining_seconds)} ${t('ui.remaining','remaining')}</p><div class='bar'><div id='tBar' class='bar-fill' style='width:${tr.status==='returning' ? 100-(tr.progress_percent||0) : (tr.progress_percent||0)}%'></div></div>`;
    const cancelLocked = isCanceling || tr.status==='returning';
    cancelBtn.disabled = cancelLocked;
    cancelBtn.textContent = cancelLocked ? t('ui.canceling','Canceling...') : t('ui.cancel_travel','Cancel Travel');
  }else{cancelBtn.style.display='none'; travelCard.textContent='No active travel.';}
}

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

startStatBtn.onclick=async()=>{
  const stat=statSelect.value; const mode=statModeSelect.value;
  const r=await fetch('/api/player/start-stat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({stat,mode})});
  const j=await r.json();
  if(j.error){toast(j.message||j.error,true);return;}
  toast('Stat geliştirme başlatıldı');
  load();
};

changeNationBtn.onclick=async()=>{
  const countryId=Number(nationSelect.value||0);
  if(!countryId){toast('Geçersiz ulus',true);return;}
  const r=await fetch('/api/player/change-nation',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({country_id:countryId})});
  const j=await r.json();
  if(j.error){toast(j.message||j.error,true);return;}
  toast('Ulus değiştirildi');
  load();
};

notifBtn.onclick=async()=>{
  const isOpen = notifPanel.style.display === 'block';
  notifPanel.style.display = isOpen ? 'none' : 'block';
  if (!isOpen) await markNotificationsRead();
};

function initSocket(){
  try {
    const socket = io(window.location.origin, { path: '/socket.io', transports:['websocket'], withCredentials:true, auth: { user_id: me?.id || '' } });
    socket.on('travel_complete', ()=> load());
    socket.on('stat_complete', ()=> load());
    socket.on('stat_progress', (evt)=>{
      if(!evt) return;
      const remain = Number(evt.remaining_seconds||0);
      statProgressBar.style.width = `${Number(evt.progress_percent||0)}%`;
      statCountdown.textContent = `${evt.active_stat||'stat'} (${evt.mode||''}) kalan: ${fmt(remain)}`;
      startStatBtn.disabled = true;
    });
    socket.on('notification_count', (evt)=> updateBadge(Number(evt?.unread_count||0)));
    socket.on('notification', (evt)=>{
      notifItems.unshift({
        type: evt?.type || 'unknown',
        data: evt?.data || {},
        is_read: 0,
        created_at: new Date().toISOString()
      });
      updateBadge(Number(evt?.unread_count||0));
      renderNotifications();
      toast('Yeni bildirim geldi');
    });
  } catch (_) {}
}

langSelect.onchange=()=>{LANG=langSelect.value||'en'; loadLang().then(load);};
energyAmountInput.oninput=()=>load();
setInterval(load,1000);
loadLang().then(async()=>{await load(); await loadNotifications(); initSocket();});
</script></body></html>
