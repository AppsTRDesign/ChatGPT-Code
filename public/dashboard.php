<!doctype html>
<html>
<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width,initial-scale=1'>
  <title>Dashboard</title>
  <link rel='stylesheet' href='/assets/css/ui.css'>
  <link rel='stylesheet' href='/assets/css/components.css'>
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css'>
  <style>
    .dash-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    .dash-actions .search{max-width:260px}
    .rank-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:12px}
    .rank-grid .glass-card{min-height:200px}
    .row{display:flex;justify-content:space-between;gap:8px}
    .row .left{display:flex;gap:8px;align-items:center;min-width:0}
    .row .left strong{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px}
    .badge-flag{font-size:16px;line-height:1}
    .detail-grid{display:grid;grid-template-columns:2fr 1fr;gap:10px}
    .meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
    @media (max-width:1200px){.rank-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media (max-width:900px){.detail-grid{grid-template-columns:1fr}.meta{grid-template-columns:1fr}.rank-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<script src='/assets/js/ui.js'></script>
<script>
UX.shell('/dashboard', `
  <div class='hero'>
    <h2>Strategic Dashboard</h2>
    <div class='dash-actions'>
      <select id='countryFilter' class='search'><option value=''>All countries</option></select>
      <button id='clearFilter' class='btn alt'>Clear</button>
    </div>
  </div>
  <div id='kpi' class='grid cols-3'></div>
  <div class='rank-grid'>
    <div class='glass-card'><h3>Top Regions</h3><div id='topRegions' class='list'></div></div>
    <div class='glass-card'><h3>Top Countries</h3><div id='topCountries' class='list'></div></div>
    <div class='glass-card'><h3>Top Players</h3><div id='topPlayers' class='list'></div></div>
    <div class='glass-card'><h3>Top Infra</h3><div id='topInfra' class='list'></div></div>
  </div>
  <div class='glass-card' style='margin-top:12px'>
    <h3>Selected Detail</h3>
    <div id='detail' class='detail-grid'>
      <div class='sub'>Pick an item from any list to inspect details and action shortcuts.</div>
    </div>
  </div>
`);

let stats = null;
let me = null;

const rankIcons = ['🥇','🥈','🥉'];
const id = (x) => document.getElementById(x);

function scoreColor(v){
  const n = Number(v || 0);
  return n < 3 ? 'stat-red' : n < 6 ? 'stat-yellow' : 'stat-green';
}

function fmtProgress(val, max){
  const percent = max > 0 ? Math.max(0, Math.min(100, Math.round((Number(val || 0) / max) * 100))) : 0;
  return `<div class='progress'><i style='width:${percent}%'></i></div>`;
}

function updateKpi() {
  const totalRegions = Number(stats?.top_regions?.length || 0);
  const totalPlayers = Number(stats?.top_players?.length || 0);
  const economy = (stats?.top_countries || []).reduce((a, b) => a + Number(b.total_score || 0), 0);
  id('kpi').innerHTML = `
    <div class='glass-card'><div class='sub'>Listed Regions</div><div class='kpi'>${UX.fmt(totalRegions)}</div></div>
    <div class='glass-card'><div class='sub'>Listed Players</div><div class='kpi'>${UX.fmt(totalPlayers)}</div></div>
    <div class='glass-card'><div class='sub'>Economy Score</div><div class='kpi'>${UX.fmt(economy)}</div></div>
  `;
}

function applyFilter(rows, type) {
  const selected = id('countryFilter').value;
  if (!selected) return rows;
  if (type === 'players') return rows.filter((x) => String(x.nation_name || '') === selected);
  if (type === 'countries') return rows.filter((x) => String(x.country_name || '') === selected);
  return rows.filter((x) => String(x.country_name || '') === selected);
}

function renderList(el, rows, type, scoreField, nameField, onSelect){
  el.innerHTML = rows.map((r, i) => {
    const rank = rankIcons[i] || `#${i + 1}`;
    const score = Number(r[scoreField] || 0);
    const nation = type === 'players' ? (r.nation_name || '-') : (r.country_name || '-');
    const flag = type === 'players' ? (r.nation_flag_url ? `<img src='${r.nation_flag_url}' alt='' width='18' height='12'>` : '🏳️') : '🌍';
    return `
      <a class='row' href='javascript:void(0)' data-type='${type}' data-id='${r.id}'>
        <span class='left'>
          <span>${rank}</span>
          <span class='badge-flag'>${flag}</span>
          <strong>${r[nameField] || '-'}</strong>
        </span>
        <b class='${scoreColor(score)}'>${UX.fmt(score)}</b>
      </a>
      <small class='sub'>${nation}</small>
    `;
  }).join('') || `<div class='sub'>No data</div>`;

  el.querySelectorAll('[data-type]').forEach((node) => {
    node.onclick = () => {
      const item = rows.find((x) => String(x.id) === String(node.dataset.id));
      if (item) onSelect(item);
    };
  });
}

function renderInfra() {
  const infraBlocks = [
    { title: 'Airports', data: stats.top_airports || [] },
    { title: 'Armies', data: stats.top_armies || [] },
    { title: 'Hospitals', data: stats.top_hospitals || [] },
    { title: 'Education', data: stats.top_educations || [] },
    { title: 'Ports', data: stats.top_ports || [] },
  ];
  id('topInfra').innerHTML = infraBlocks.map((b) => {
    const first = b.data[0];
    if (!first) return '';
    return `<a class='row' href='/region/${first.id}'><span>${b.title}: ${first.name}</span><b>${UX.fmt(first.value)}</b></a>`;
  }).join('');
}

async function travelToRegion(regionId){
  if (!regionId) return;
  try {
    await UX.api('/api/player/travel', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({to_region_id: Number(regionId)})
    });
    alert('Travel started.');
  } catch (e) {
    alert(e.message || 'Travel failed');
  }
}

function showRegionDetail(r) {
  id('detail').innerHTML = `
    <div>
      <h4>${r.name}</h4>
      <div class='sub'>${r.country_name || '-'}</div>
      <div class='meta'>
        <div class='glass-card'><div class='sub'>Score</div><div class='kpi'>${UX.fmt(r.score)}</div></div>
        <div class='glass-card'><div class='sub'>Population</div><div class='kpi'>${UX.fmt(r.population)}</div></div>
      </div>
    </div>
    <div>
      <a class='btn' href='/region/${r.id}' style='display:block;text-align:center'>Open Region</a>
      <button id='travelAction' class='btn alt' style='width:100%;margin-top:8px'>Travel Here</button>
    </div>
  `;
  id('travelAction').onclick = () => travelToRegion(r.id);
}

function showCountryDetail(c) {
  id('detail').innerHTML = `
    <div>
      <h4>${c.country_name}</h4>
      <div class='sub'>Government: ${c.government_type || '-'}</div>
      <div class='meta'>
        <div class='glass-card'><div class='sub'>Total Score</div><div class='kpi'>${UX.fmt(c.total_score)}</div></div>
        <div class='glass-card'><div class='sub'>Population</div><div class='kpi'>${UX.fmt(c.population)}</div></div>
      </div>
    </div>
    <div>
      <a class='btn' href='/country/${c.id}' style='display:block;text-align:center'>Open Country</a>
    </div>
  `;
}

function showPlayerDetail(p) {
  const levelMax = Math.max(1, Number((p.level || 1) * 100));
  id('detail').innerHTML = `
    <div>
      <h4>${p.username}</h4>
      <div class='sub'>Nation: ${p.nation_name || '-'}</div>
      <div class='meta'>
        <div class='glass-card'><div class='sub'>Level</div><div class='kpi'>${UX.fmt(p.level)}</div></div>
        <div class='glass-card'><div class='sub'>XP</div><div class='kpi'>${UX.fmt(p.xp)}</div></div>
      </div>
      ${fmtProgress(p.xp, levelMax)}
      <small class='sub'>Region: ${p.region_name || '-'}</small>
    </div>
    <div>
      <a class='btn' href='/player/${p.id}' style='display:block;text-align:center'>Open Player</a>
      <button id='playerTravelAction' class='btn alt' style='width:100%;margin-top:8px'>Travel to Player Region</button>
    </div>
  `;
  id('playerTravelAction').onclick = () => travelToRegion(p.current_region_id);
}

function render() {
  updateKpi();

  const regions = applyFilter([...(stats.top_regions || [])], 'regions');
  const countries = applyFilter([...(stats.top_countries || [])], 'countries');
  const players = applyFilter([...(stats.top_players || [])], 'players');

  renderList(id('topRegions'), regions, 'regions', 'score', 'name', showRegionDetail);
  renderList(id('topCountries'), countries, 'countries', 'total_score', 'country_name', showCountryDetail);
  renderList(id('topPlayers'), players, 'players', 'level', 'username', showPlayerDetail);
  renderInfra();
}

(async () => {
  try {
    const [d, m] = await Promise.all([
      UX.api('/api/stats/dashboard'),
      UX.api('/api/player/me').catch(() => null)
    ]);
    stats = d;
    me = m;

    const countryNames = new Set();
    (stats.top_countries || []).forEach((x) => x.country_name && countryNames.add(x.country_name));
    (stats.top_players || []).forEach((x) => x.nation_name && countryNames.add(x.nation_name));
    id('countryFilter').innerHTML += [...countryNames].sort().map((c) => `<option value='${c}'>${c}</option>`).join('');

    id('countryFilter').onchange = render;
    id('clearFilter').onclick = () => { id('countryFilter').value = ''; render(); };

    render();

    const first = stats.top_players?.[0] || stats.top_regions?.[0] || stats.top_countries?.[0];
    if (first) {
      if (first.username) showPlayerDetail(first);
      else if (first.score !== undefined) showRegionDetail(first);
      else showCountryDetail(first);
    }
  } catch (e) {
    pageRoot.innerHTML = `<div class='glass-card'>${e.message}</div>`;
  }
})();
</script>
</body>
</html>
