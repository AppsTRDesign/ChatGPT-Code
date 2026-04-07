<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>Dashboard</title><link rel='stylesheet' href='/styles.css'></head>
<body><div class='auth-wrap'><div class='auth-card glass' style='max-width:1000px'>
<h2 id='titleText'>World Dashboard</h2><p><a href='/profile' id='profileLink'>Profile</a> • <a href='/map' id='mapLink'>Map</a></p>
<div class='btn-row'><select id='countrySel'></select><select id="langSelect" style="max-width:120px"><option value="tr">TR</option><option value="en">EN</option></select><button id='logoutBtn'>Logout</button></div>
<div id='regions'></div><hr>
<h3 id='topRegionsTitle'>Top Regions</h3><div id='topRegions'></div>
<h3 id='topCountriesTitle'>Top Countries</h3><div id='topCountries'></div>
<hr><h3 id='detailTitle'>Detail</h3><div id='detailPanel' class='muted'>Select a ranking item.</div>
</div></div>
<script>
let LANG = localStorage.getItem('lang') || '';
let I18N = {};
function detectLang(){const b=(navigator.language||'en').toLowerCase();if(b.startsWith('tr')) return 'tr'; if(b.startsWith('en')) return 'en'; return 'en';}
function t(key,f=''){const p=key.split('.');let c=I18N;for(const k of p)c=c?.[k];return typeof c==='string'?c:(f||key);}
async function loadLang(){
  LANG = LANG || detectLang();
  langSelect.value = LANG;
  const r = await fetch(`/api/i18n?lang=${LANG}`).then(x=>x.json()).catch(()=>({}));
  I18N = r.data || {};
  LANG = r.lang || LANG;
  localStorage.setItem('lang', LANG);
  titleText.textContent = t('dash.title','World Dashboard');
  profileLink.textContent = t('nav.profile','Profile');
  mapLink.textContent = t('nav.map','Map');
  logoutBtn.textContent = t('auth.logout','Logout');
  topRegionsTitle.textContent = t('dash.top_regions','Top Regions');
  topCountriesTitle.textContent = t('dash.top_countries','Top Countries');
  detailTitle.textContent = t('dash.detail','Detail');
}
async function loadCountries(){
  const c=(await (await fetch('/api/map/countries')).json()).data||[];
  countrySel.innerHTML=c.map(x=>`<option value='${x.id}'>${x.name}</option>`).join('');
}
async function loadRegions(){
  const r=(await (await fetch('/api/map/regions')).json()).data||[];
  const selected=Number(countrySel.value||0);
  const rows=r.filter(x=>Number(x.country_id)===selected).map(x=>`<div class='glass' style='padding:10px;margin:6px 0'><b>${x.name}</b><br>${t('dash.army','Army')} ${x.army_level} • ${t('dash.edu','Edu')} ${x.education_level} • ${t('dash.hosp','Hosp')} ${x.hospital_level} • ${t('dash.air','Air')} ${x.airport_level}${x.is_coastal==1?` • ${t('dash.port','Port')} ${x.port_level}`:''}</div>`).join('');
  regions.innerHTML=rows||t('dash.no_regions','No regions');
}
async function loadRankings(){
  const d=(await (await fetch('/api/stats/dashboard')).json()).data;
  topRegions.innerHTML=(d.top_regions||[]).map(r=>`<div><a href='#' data-region-id='${r.id}' class='rank-region'>${r.name} (${r.country_name}) — score ${Number(r.score).toFixed(1)} — pop ${r.population}</a></div>`).join('');
  topCountries.innerHTML=(d.top_countries||[]).map(c=>`<div><a href='#' data-country-region-id='${c.id}' class='rank-country'>${c.name} — avg ${Number(c.avg_score).toFixed(1)} — pop ${c.population}</a></div>`).join('');
  bindRankClicks();
}
function bindRankClicks(){
  document.querySelectorAll('.rank-region').forEach(el=>el.onclick=(e)=>{e.preventDefault();showRegionDetail(Number(el.dataset.regionId||0));});
  document.querySelectorAll('.rank-country').forEach(el=>el.onclick=(e)=>{e.preventDefault();showCountryDetail(Number(el.dataset.countryRegionId||0));});
}
async function showRegionDetail(id){
  const res = await fetch(`/api/map/region-detail?id=${id}`).then(r=>r.json());
  if(!res.data){ detailPanel.textContent = t('errors.invalid_region','Invalid region.'); return; }
  const r = res.data;
  const neighbors=(r.neighbors||[]).map(n=>`${n.name} (#${n.id})`).join(', ')||'-';
  detailPanel.innerHTML=`<div class='glass' style='padding:10px'><b>${r.name}</b><br>${t('dash.army','Army')} ${r.army_level} • ${t('dash.edu','Edu')} ${r.education_level} • ${t('dash.hosp','Hosp')} ${r.hospital_level} • ${t('dash.air','Air')} ${r.airport_level}<br>Neighbors: ${neighbors}<br>Country: ${r.country_name}<br>Type: ${r.region_type}<br><button class='primary-btn' id='travelRegionBtn'>${t('ui.travel_to_region','Travel to this region')}</button></div>`;
  document.getElementById('travelRegionBtn').onclick=()=>travelToRegion(r.id);
}
async function showCountryDetail(id){
  const res = await fetch(`/api/map/country-detail?id=${id}`).then(r=>r.json());
  if(!res.data){ detailPanel.textContent = t('errors.invalid_region','Invalid region.'); return; }
  const c = res.data;
  const regs=(c.regions||[]).map(x=>x.name).join(', ');
  detailPanel.innerHTML=`<div class='glass' style='padding:10px'><b>${c.country_name}</b><br>Capital: ${c.name}<br>Regions: ${c.region_count}<br>${regs}<br><button class='primary-btn' id='travelCapitalBtn'>${t('ui.travel_to_region','Travel to this region')}</button></div>`;
  document.getElementById('travelCapitalBtn').onclick=()=>travelToRegion(c.id);
}
async function travelToRegion(id){
  const r=await fetch('/api/region/action',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({region_id:id,action:'travel'})}).then(x=>x.json());
  alert(r.message||r.error||'travel');
}
countrySel.onchange=loadRegions;
langSelect.onchange=()=>{LANG=langSelect.value||'en';loadLang().then(()=>Promise.all([loadCountries(),loadRankings()]).then(loadRegions));};
logoutBtn.onclick=async()=>{await fetch('/api/auth/logout',{method:'POST'});location.href='/login';}
loadLang().then(()=>Promise.all([loadCountries(),loadRankings()]).then(loadRegions));
</script></body></html>
