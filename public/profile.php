<!doctype html>
<html>
<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width,initial-scale=1'>
  <title>Profile</title>
  <link rel='stylesheet' href='/assets/css/ui.css'>
  <link rel='stylesheet' href='/assets/css/components.css'>
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css'>
  <style>
    .profile-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
    .actions{display:flex;gap:8px;flex-wrap:wrap}
    .kv{display:flex;justify-content:space-between;gap:10px;margin:4px 0}
    @media (max-width:1100px){.profile-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<script src='https://cdn.socket.io/4.7.5/socket.io.min.js'></script>
<script src='/assets/js/ui.js'></script>
<script>
UX.shell('/profile', `
  <div class='profile-grid'>
    <div class='glass-card' id='baseCard'></div>
    <div class='glass-card' id='econCard'></div>
    <div class='glass-card' id='nationCard'></div>
  </div>
  <div class='glass-card' style='margin-top:12px'>
    <h3>Stat Lab</h3>
    <div id='globalStats' class='sub'></div>
    <div class='tabs' id='statTabs'>
      <button class='tab active' data-s='strength'>💪 Strength</button>
      <button class='tab' data-s='education'>📘 Education</button>
      <button class='tab' data-s='endurance'>🛡 Endurance</button>
    </div>
    <div id='statInfo' class='sub'></div>
    <div class='progress'><i id='statBar' style='width:0%'></i></div>
    <small id='statCountdown' class='sub'></small>
    <div class='actions' style='margin-top:8px'>
      <button id='startCoins' class='btn'>Start (Coins)</button>
      <button id='startGold' class='btn'>Start (Gold)</button>
      <button id='stopStat' class='btn alt'>Stop</button>
    </div>
  </div>
  <div class='glass-card' style='margin-top:12px'>
    <h3>Travel</h3>
    <div id='travelCard' class='sub'>Loading...</div>
    <div class='actions' style='margin-top:8px'>
      <a class='btn' href='/map'>Open Map</a>
      <button id='cancelTravel' class='btn alt'>Cancel Travel</button>
    </div>
  </div>
`);

let me = null;
let countries = [];
let selectedStat = 'strength';
let activePreview = null;
let timer = null;

const id = (x) => document.getElementById(x);

function fmtSec(sec){
  const s = Math.max(0, Number(sec || 0));
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const ss = s % 60;
  return [h,m,ss].map((x) => String(x).padStart(2,'0')).join(':');
}

function renderBase() {
  id('baseCard').innerHTML = `
    <h3>${me.username}</h3>
    <div class='sub'>Level ${me.level} • ${me.current_region_name || '-'}</div>
    <div class='progress'><i style='width:${Math.max(0, Math.min(100, Math.round((Number(me.xp || 0) / Math.max(1, Number(me.xp_to_next || 1))) * 100)))}%'></i></div>
    <small class='sub'>XP ${UX.fmt(me.xp)} / ${UX.fmt(me.xp_to_next)}</small>
    <div class='kv'><span>Strength</span><b>${me.strength}</b></div>
    <div class='kv'><span>Education</span><b>${me.education}</b></div>
    <div class='kv'><span>Endurance</span><b>${me.endurance}</b></div>
  `;
}

function renderEconomy() {
  id('econCard').innerHTML = `
    <h3>Economy</h3>
    <div class='kpi'>🪙 ${UX.fmt(me.coins)}</div>
    <div class='kpi'>💎 ${UX.fmt(me.gold)}</div>
    <div class='sub'>Instant Energy: ${UX.fmt(me.instant_energy)} / ${UX.fmt(me.max_instant_energy)}</div>
    <div class='sub'>Total Energy: ${UX.fmt(me.total_energy)}</div>
    <div class='actions' style='margin-top:8px'>
      <button id='buyEnergy100k' class='btn'>Buy 100k Energy</button>
    </div>
  `;
  id('buyEnergy100k').onclick = async () => {
    try {
      const res = await UX.api('/api/player/buy-energy', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({energy_amount: 100000})
      });
      alert(`Energy purchased: +${UX.fmt(res.total_energy_added)} (Gold -${UX.fmt(res.gold_spent)})`);
      await load();
    } catch (e) {
      alert(e.message || 'Purchase failed');
    }
  };
}

function renderNation() {
  const options = countries.map((c) => `<option value='${c.nation_country_id}'>${c.country_name}</option>`).join('');
  const cooldown = Number(me.nation_change_remaining_seconds || 0);
  id('nationCard').innerHTML = `
    <h3>Nation</h3>
    <div class='sub'>Current: ${me.nation_name || '-'}</div>
    <small class='sub'>Cooldown: ${cooldown > 0 ? fmtSec(cooldown) : 'Ready'}</small>
    <div class='actions' style='margin-top:8px'>
      <select id='nationSelect' class='search' style='max-width:220px'>${options}</select>
      <button id='changeNation' class='btn'>Change Nation (1000 Gold)</button>
    </div>
  `;
  const nationSelect = id('nationSelect');
  nationSelect.value = String(me.nation_country_id || '');
  id('changeNation').onclick = async () => {
    if (cooldown > 0) {
      alert('Nation change is on cooldown.');
      return;
    }
    try {
      await UX.api('/api/player/change-nation', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({country_id: Number(nationSelect.value)})
      });
      alert('Nation changed successfully.');
      await load();
    } catch (e) {
      alert(e.message || 'Nation change failed');
    }
  };
}

function renderTravel() {
  const t = me.travel;
  const active = Boolean(me.is_traveling && t);
  id('travelCard').innerHTML = active
    ? `<div><b>${t.from_region_name || t.from_region_id} → ${t.to_region_name || t.to_region_id}</b></div>
       <div>Status: ${t.status}</div>
       <div>Remaining: ${fmtSec(t.remaining_seconds)}</div>
       <div>Distance: ${Math.round(Number(t.distance_km || 0))} km</div>`
    : 'No active travel.';

  id('cancelTravel').style.display = active ? 'inline-block' : 'none';
  id('cancelTravel').onclick = async () => {
    try {
      await UX.api('/api/player/cancel-travel', { method: 'POST' });
      alert('Travel canceled and return started.');
      await load();
    } catch (e) {
      alert(e.message || 'Cancel failed');
    }
  };
}

async function refreshPreview() {
  activePreview = await UX.api('/api/player/stat-preview?stat=' + selectedStat);
  const p = activePreview;
  id('globalStats').textContent = `STR ${me.strength} • EDU ${me.education} • END ${me.endurance}`;
  id('statInfo').textContent = `${selectedStat.toUpperCase()} Lv ${p.level} • Coins ${UX.fmt(p.coins_cost)} / ${Math.ceil(p.coins_duration_seconds/60)}m • Gold ${UX.fmt(p.gold_cost)} / ${Math.ceil(p.gold_duration_seconds/60)}m`;
  id('startCoins').textContent = `Start Coins (${UX.fmt(p.coins_cost)})`;
  id('startGold').textContent = `Start Gold (${UX.fmt(p.gold_cost)})`;
}

function renderStatProgress() {
  const isActive = Boolean(me.active_stat && me.stat_finish_time);
  if (!isActive) {
    id('statBar').style.width = '0%';
    id('statCountdown').textContent = 'No active stat training.';
    return;
  }
  const start = new Date(me.stat_started_at).getTime();
  const finish = new Date(me.stat_finish_time).getTime();
  const now = Date.now();
  const total = Math.max(1, finish - start);
  const done = Math.max(0, Math.min(total, now - start));
  const remain = Math.max(0, Math.floor((finish - now) / 1000));
  id('statBar').style.width = `${Math.round((done / total) * 100)}%`;
  id('statCountdown').textContent = `${me.active_stat.toUpperCase()} (${me.stat_mode}) • ${fmtSec(remain)} remaining`;
}

function bindStatActions() {
  document.querySelectorAll('[data-s]').forEach((btn) => {
    btn.onclick = async () => {
      selectedStat = btn.dataset.s;
      document.querySelectorAll('[data-s]').forEach((x) => x.classList.remove('active'));
      btn.classList.add('active');
      await refreshPreview();
    };
  });

  id('startCoins').onclick = async () => {
    try {
      await UX.api('/api/player/start-stat', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({stat: selectedStat, mode: 'coins'})
      });
      await load();
    } catch (e) {
      alert(e.message || 'Start failed');
    }
  };

  id('startGold').onclick = async () => {
    try {
      await UX.api('/api/player/start-stat', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({stat: selectedStat, mode: 'gold'})
      });
      await load();
    } catch (e) {
      alert(e.message || 'Start failed');
    }
  };

  id('stopStat').onclick = async () => {
    try {
      await UX.api('/api/player/stop-stat', {method: 'POST'});
      await load();
    } catch (e) {
      alert(e.message || 'Stop failed');
    }
  };
}

async function load() {
  [me, countries] = await Promise.all([
    UX.api('/api/player/me'),
    UX.api('/api/map/countries').then((rows) => rows || []).catch(() => [])
  ]);
  renderBase();
  renderEconomy();
  renderNation();
  renderTravel();
  await refreshPreview();
  renderStatProgress();
  bindStatActions();
}

(async () => {
  try {
    await load();
    if (timer) clearInterval(timer);
    timer = setInterval(async () => {
      try {
        me = await UX.api('/api/player/me');
        renderTravel();
        renderStatProgress();
      } catch (_) {}
    }, 1000);
  } catch (e) {
    pageRoot.innerHTML = `<div class='glass-card'>${e.message}</div>`;
  }
})();
</script>
</body>
</html>
