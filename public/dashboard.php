<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>Dashboard</title><link rel='stylesheet' href='/styles.css'></head>
<body><div class='auth-wrap'><div class='auth-card glass' style='max-width:1000px'>
<h2 id='titleText'>World Dashboard</h2><p><a href='/profile' id='profileLink'>Profile</a> • <a href='/map' id='mapLink'>Map</a></p>
<div class='btn-row'><select id='countrySel'></select><button id='logoutBtn'>Logout</button></div>
<div id='regions'></div><hr>
<h3>Top Regions (Total Buildings)</h3><div id='topRegions'></div>
<h3>Top Countries (Owned Region Building Sum)</h3><div id='topCountries'></div>
<h3>Top Airports</h3><div id='topAirports'></div>
<h3>Top Armies</h3><div id='topArmies'></div>
<h3>Top Hospitals</h3><div id='topHospitals'></div>
<h3>Top Educations</h3><div id='topEducations'></div>
<h3>Top Ports</h3><div id='topPorts'></div>
<h3>Top Country Population</h3><div id='topCountryPopulation'></div>
<h3>Top Region Population</h3><div id='topRegionPopulation'></div>
<hr><h3>Detail</h3><div id='detailPanel' class='muted'>Select a ranking item.</div>
</div></div>
<script>
let COUNTRY_ROWS=[];let REGION_ROWS=[];
async function loadCountries(){
  COUNTRY_ROWS=(await (await fetch('/api/map/countries')).json()).data||[];
  countrySel.innerHTML=COUNTRY_ROWS.map(x=>`<option value='${x.id}'>${x.country_name} (${x.name})</option>`).join('');
}
async function loadRegions(){
  REGION_ROWS=(await (await fetch('/api/map/regions')).json()).data||[];
  const selected=Number(countrySel.value||0);
  const rows=REGION_ROWS.filter(x=>Number(x.owner_region_id)===selected).map(x=>`<div class='glass' style='padding:10px;margin:6px 0'><b>${x.name}</b> (${x.region_type})<br>Total: ${x.total_level} • Airport ${x.airport_level} • Army ${x.army_level} • Hospital ${x.hospital_level} • Education ${x.education_level} • School ${x.school_level} • Port ${x.port_level}</div>`).join('');
  regions.innerHTML=rows||'No regions';
}
function metricRows(list){return (list||[]).map(x=>`<div><a href='#' data-region-id='${x.id}' class='rank-region'>${x.name} (${x.country_name}) — ${x.value ?? x.score}</a></div>`).join('');}
async function loadRankings(){
  const d=(await (await fetch('/api/stats/dashboard')).json()).data;
  topRegions.innerHTML=metricRows(d.top_regions);
  topCountries.innerHTML=(d.top_countries||[]).map(c=>`<div><a href='#' data-country-region-id='${c.id}' class='rank-country'>${c.country_name} — total ${c.total_score} (A:${c.airport_total} Ar:${c.army_total} H:${c.hospital_total} E:${c.education_total} P:${c.port_total})</a></div>`).join('');
  topAirports.innerHTML=metricRows(d.top_airports);
  topArmies.innerHTML=metricRows(d.top_armies);
  topHospitals.innerHTML=metricRows(d.top_hospitals);
  topEducations.innerHTML=metricRows(d.top_educations);
  topPorts.innerHTML=metricRows(d.top_ports);
  topCountryPopulation.innerHTML=(d.top_country_population||[]).map(x=>`<div><a href='#' data-country-region-id='${x.id}' class='rank-country'>${x.country_name} — pop ${x.total_population}</a></div>`).join('');
  topRegionPopulation.innerHTML=(d.top_region_population||[]).map(x=>`<div><a href='#' data-region-id='${x.id}' class='rank-region'>${x.name} (${x.country_name}) — pop ${x.total_population}</a></div>`).join('');
  bindRankClicks();
}
function bindRankClicks(){
  document.querySelectorAll('.rank-region').forEach(el=>el.onclick=(e)=>{e.preventDefault();showRegionDetail(Number(el.dataset.regionId||0));});
  document.querySelectorAll('.rank-country').forEach(el=>el.onclick=(e)=>{e.preventDefault();showCountryDetail(Number(el.dataset.countryRegionId||0));});
}
async function showRegionDetail(id){
  const res = await fetch(`/api/map/region-detail?id=${id}`).then(r=>r.json());
  const r = res.data; if(!r){detailPanel.textContent='Invalid region';return;}
  detailPanel.innerHTML=`<div class='glass' style='padding:10px'><b>${r.name}</b><br>Total: ${r.total_level}<br>Airport ${r.airport_level} • Army ${r.army_level} • Hospital ${r.hospital_level} • Education ${r.education_level} • School ${r.school_level} • Port ${r.port_level}<br><button class='primary-btn' id='travelRegionBtn'>Travel</button></div>`;
  document.getElementById('travelRegionBtn').onclick=()=>travelToRegion(r.id);
}
async function showCountryDetail(id){
  const res = await fetch(`/api/map/country-detail?id=${id}`).then(r=>r.json());
  const c = res.data; if(!c){detailPanel.textContent='Invalid country';return;}
  const regs=(c.regions||[]).map(x=>`${x.name} (${Number(x.airport_level)+Number(x.army_level)+Number(x.hospital_level)+Number(x.education_level)+Number(x.school_level)+Number(x.port_level)})`).join(', ');
  detailPanel.innerHTML=`<div class='glass' style='padding:10px'><b>${c.country_name}</b><br>Capital: ${c.name}<br>Government: ${c.government_type}<br>Regions: ${c.region_count}<br>State Money: ${c.treasury_state_money}<br>Gold: ${c.treasury_gold}<br>Diamond: ${c.treasury_diamond}<br>Oil: ${c.treasury_oil}<br>Mineral: ${c.treasury_mineral}<br>Uranium: ${c.treasury_uranium}<br>${regs}<br><button class='primary-btn' id='travelCapitalBtn'>Travel</button></div>`;
  document.getElementById('travelCapitalBtn').onclick=()=>travelToRegion(c.id);
}
async function travelToRegion(id){const r=await fetch('/api/region/action',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({region_id:id,action:'travel'})}).then(x=>x.json());alert(r.message||r.error||'travel');}
countrySel.onchange=loadRegions;
logoutBtn.onclick=async()=>{await fetch('/api/auth/logout',{method:'POST'});location.href='/login';}
Promise.all([loadCountries(),loadRankings()]).then(loadRegions);
</script></body></html>
