<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>Dashboard</title><link rel='stylesheet' href='/styles.css'></head>
<body><div class='auth-wrap'><div class='auth-card glass' style='max-width:1000px'>
<h2>World Dashboard</h2><p><a href='/profile'>Profile</a> • <a href='/map'>Map</a></p>
<div class='btn-row'><select id='countrySel'></select><button id='logoutBtn'>Logout</button></div>
<div id='regions'></div><hr>
<h3>Top Regions</h3><div id='topRegions'></div>
<h3>Top Countries</h3><div id='topCountries'></div>
</div></div>
<script>
async function loadCountries(){
  const c=(await (await fetch('/api/map/countries')).json()).data||[];
  countrySel.innerHTML=c.map(x=>`<option value='${x.id}'>${x.name}</option>`).join('');
}
async function loadRegions(){
  const r=(await (await fetch('/api/map/regions')).json()).data||[];
  const selected=Number(countrySel.value||0);
  const rows=r.filter(x=>Number(x.country_id)===selected).map(x=>`<div class='glass' style='padding:10px;margin:6px 0'><b>${x.name}</b><br>Army ${x.army_level} • Edu ${x.education_level} • Hosp ${x.hospital_level} • Air ${x.airport_level}${x.is_coastal==1?` • Port ${x.port_level}`:''}</div>`).join('');
  regions.innerHTML=rows||'No regions';
}
async function loadRankings(){
  const d=(await (await fetch('/api/stats/dashboard')).json()).data;
  topRegions.innerHTML=(d.top_regions||[]).map(r=>`<div>${r.name} (${r.country_name}) — score ${Number(r.score).toFixed(1)} — pop ${r.population}</div>`).join('');
  topCountries.innerHTML=(d.top_countries||[]).map(c=>`<div>${c.name} — avg ${Number(c.avg_score).toFixed(1)} — pop ${c.population}</div>`).join('');
}
countrySel.onchange=loadRegions;
logoutBtn.onclick=async()=>{await fetch('/api/auth/logout',{method:'POST'});location.href='/login';}
Promise.all([loadCountries(),loadRankings()]).then(loadRegions);
</script></body></html>
