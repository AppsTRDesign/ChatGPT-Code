<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>Dashboard</title><link rel='stylesheet' href='/styles.css'><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css'></head><body>
<div class='page'>
  <div class='hero'><h2>World Command Center</h2><span class='muted'>Live geopolitical overview</span></div>
  <div class='grid cards' id='kpis'></div>
  <div class='grid cards' style='margin-top:12px'>
    <div class='card'><h3>Top Regions</h3><div id='regions' class='list'></div></div>
    <div class='card'><h3>Top Countries</h3><div id='countries' class='list'></div></div>
    <div class='card'><h3>Top Players</h3><div id='players' class='list'></div></div>
  </div>
</div>
<script src='/ui.js'></script>
<script>
GameUI.mountShell('/dashboard');
(async()=>{
  try{
    const d = await GameUI.api('/api/stats/dashboard');
    const regions = d.top_regions||[];
    const countries = d.top_countries||[];
    const players = (d.top_region_population||[]).map((x,i)=>({id:x.id,name:`Region Leader ${x.name}`,score:x.total_population||0,region:x.name})).slice(0,10);

    kpis.innerHTML = `
      <div class='card'><div class='muted'>Total Regions</div><div class='kpi'>${GameUI.fmt(regions.length)}</div></div>
      <div class='card'><div class='muted'>Total Players</div><div class='kpi'>${GameUI.fmt((d.top_country_population||[]).reduce((a,b)=>a+Number(b.total_population||0),0))}</div></div>
      <div class='card'><div class='muted'>Economy Overview</div><div class='kpi'>${GameUI.fmt(countries.reduce((a,b)=>a+Number(b.total_score||0),0))}</div></div>`;

    regionsEl(regions); countriesEl(countries); playersEl(players);
  }catch(e){document.querySelector('.page').insertAdjacentHTML('beforeend',`<div class='card'>${e.message}</div>`)}
})();
function regionsEl(rows){regions.innerHTML=rows.slice(0,10).map((r,i)=>`<a class='list-row' href='/region/${r.id}'><span><span class='badge-top'>${GameUI.rankBadge(i)}</span> ${r.name}</span><b>${GameUI.fmt(r.score)}</b></a>`).join('');}
function countriesEl(rows){countries.innerHTML=rows.slice(0,10).map((r,i)=>`<a class='list-row' href='/country/${r.id}'><span><span class='badge-top'>${GameUI.rankBadge(i)}</span> ${r.country_name}</span><b>${GameUI.fmt(r.total_score)}</b></a>`).join('');}
function playersEl(rows){players.innerHTML=rows.slice(0,10).map((r,i)=>`<a class='list-row' href='/player/${r.id}'><span><span class='badge-top'>${GameUI.rankBadge(i)}</span> ${r.name}</span><b>${GameUI.fmt(r.score)}</b></a>`).join('');}
</script></body></html>
