<!doctype html>
<html>
<head>
<meta charset="utf-8"><title>World Map</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="/styles.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
</head>
<body>
<div id="map"></div>
<div id="sheetBackdrop" class="sheet-backdrop"></div>
<div id="toast" class="toast"></div>
<div id="travelStatus" class="travel-status" style="display:none"></div>
<div id="playerHud" class="travel-status" style="display:none;top:74px"></div>
<div id="sheet" class="action-sheet">
  <div class="sheet-handle"></div>
  <button class="sheet-close" id="closeSheet">×</button>
  <h3 id="regionTitle">Region</h3>
  <p id="regionMeta"></p>
  <p id="modeHint" class="muted"></p>
  <p id="costHint" class="muted"></p>
  <button id="travelBtn" class="primary-btn">Travel to this region</button>
  <button id="sheetCancelBtn" class="primary-btn" style="display:none;margin-top:8px;background:#dc2626">Cancel Active Travel</button>
</div>
<div class="map-nav">
  <a class="map-back" href="/dashboard" id="dashLink">Dashboard</a>
  <a class="map-back" href="/login" id="loginLink" style="display:none">Login</a>
  <a class="map-back" href="/register" id="registerLink" style="display:none">Register</a>
  <select id="langSelect" class="map-back" style="border:none;outline:none">
    <option value="tr">TR</option>
    <option value="en">EN</option>
  </select>
</div>
<script>
let selectedRegion = null;
let me = null;
let isAuthed = false;
let activeTravel = null;
const layers = new Map();
let currentRegionId = null;
let selectedRegionId = null;
let marker = null;
let routeLine = null;
let planeMarker = null;
let countdownTimer = null;
let isActing = false;
let isCanceling = false;
let I18N = {};
let LANG = localStorage.getItem('lang') || '';

const map = L.map('map').setView([20,0],2);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:18,attribution:'© OSM'}).addTo(map);
map.createPane('routePane');
map.getPane('routePane').style.zIndex = 9000;
map.createPane('planePane');
map.getPane('planePane').style.zIndex = 10000;
map.getPane('planePane').style.pointerEvents = 'auto';
const sheet = document.getElementById('sheet');
const backdrop = document.getElementById('sheetBackdrop');
const closeSheetBtn = document.getElementById('closeSheet');
const toastEl = document.getElementById('toast');
const travelStatusEl = document.getElementById('travelStatus');
const playerHud = document.getElementById('playerHud');
const sheetCancelBtn = document.getElementById('sheetCancelBtn');
const langSelect = document.getElementById('langSelect');

function detectLang() {
  const browser = (navigator.language || 'en').toLowerCase();
  if (browser.startsWith('tr')) return 'tr';
  if (browser.startsWith('en')) return 'en';
  return 'en';
}

function updateNavMode() {
  dashLink.style.display = isAuthed ? 'inline-block' : 'none';
  loginLink.style.display = isAuthed ? 'none' : 'inline-block';
  registerLink.style.display = isAuthed ? 'none' : 'inline-block';
}

async function api(url, opts={}){
  const r = await fetch(url,opts);
  return r.json();
}

async function loadLang(){
  LANG = LANG || detectLang();
  langSelect.value = LANG;
  try {
    const payload = await fetch(`/api/i18n?lang=${LANG}`).then(r => r.json());
    I18N = payload?.data || {};
    LANG = payload?.lang || LANG;
    localStorage.setItem('lang', LANG);
  } catch (_) {
    I18N = {};
  }
  applyStaticTexts();
}

function t(key, fallback=''){
  const parts = key.split('.');
  let cur = I18N;
  for (const p of parts) cur = cur?.[p];
  return typeof cur === 'string' ? cur : (fallback || key);
}

function showToast(message, error=false){
  toastEl.textContent = message;
  toastEl.className = `toast show ${error ? 'error' : ''}`;
  setTimeout(()=> toastEl.className = 'toast', 2200);
}

function applyStaticTexts(){
  if (travelBtn) travelBtn.textContent = t('ui.travel_to_region', 'Travel to this region');
  if (sheetCancelBtn) sheetCancelBtn.textContent = isCanceling ? t('ui.canceling', 'Canceling...') : t('ui.cancel_travel', 'Cancel Travel');
}

function openSheet(region){
  selectedRegion = region;
  selectedRegionId = Number(region.id);
  regionTitle.textContent = `${region.name}, ${region.country_name}`;
  regionMeta.innerHTML = `Owner Region: ${region.owner_region_name || region.country_name}<br>Resource: ${region.resource_type}<br>Population: ${region.population}`;
  modeHint.textContent = !isAuthed ? 'Login to interact with this region.' : activeTravel ? 'Travel already in progress.' : '';
  let cost = 0;
  if (isAuthed && currentRegionId && layers.get(currentRegionId)) {
    const from = layers.get(currentRegionId).region;
    const dist = distanceKm(Number(from.lat), Number(from.lng), Number(region.lat), Number(region.lng));
    cost = Math.max(1000, dist * 50);
    const airportCount = Number(from.airport_level || 1);
    const effectiveSpeed = 3200 * (1 + Math.log(airportCount + 1) * 0.25);
    const avgSeconds = Math.max(5, Math.round((dist / Math.max(1, effectiveSpeed)) * 3600));
    costHint.textContent = `Distance: ${dist.toFixed(1)} km • Travel Cost: ${Math.ceil(cost)} coins • Avg Flight: ${fmt(avgSeconds)}`;
  } else {
    costHint.textContent = '';
  }
  const notEnoughCoins = isAuthed && me && Number(me.coins) < cost;
  if (notEnoughCoins) modeHint.textContent = 'Not enough coins for this trip.';
  const cancelLocked = isCanceling || activeTravel?.status === 'returning';
  travelBtn.disabled = !isAuthed || isActing || !!activeTravel || currentRegionId === Number(region.id) || notEnoughCoins;
  sheetCancelBtn.style.display = activeTravel ? 'block' : 'none';
  sheetCancelBtn.disabled = cancelLocked;
  sheetCancelBtn.textContent = cancelLocked ? t('ui.canceling', 'Canceling...') : t('ui.cancel_travel', 'Cancel Travel');
  sheet.classList.add('open');
  backdrop.classList.add('open');
  refreshStyles();
}

function closeSheetPanel(){
  sheet.classList.remove('open');
  backdrop.classList.remove('open');
  selectedRegionId = null;
  refreshStyles();
}

function styleFor(region){
  const id = Number(region.id);
  const isCurrent = currentRegionId === id;
  const isSelected = selectedRegionId === id;
  return {
    color: isSelected ? '#ffffff' : '#1e293b',
    fillColor: region.color || '#7c3aed',
    fillOpacity: isCurrent ? 0.58 : 0.26,
    opacity: 0.95,
    weight: isCurrent ? 3 : (isSelected ? 3 : 2)
  };
}

function refreshStyles(){
  layers.forEach((obj)=> obj.layer.setStyle(styleFor(obj.region)));
}


function distanceKm(a,b,c,d){
  const R=6371;
  const dLat=(c-a)*Math.PI/180;
  const dLng=(d-b)*Math.PI/180;
  const x=Math.sin(dLat/2)**2 + Math.cos(a*Math.PI/180)*Math.cos(c*Math.PI/180)*Math.sin(dLng/2)**2;
  return R*(2*Math.atan2(Math.sqrt(x),Math.sqrt(1-x)));
}

function fmt(sec){
  const m = String(Math.floor(sec/60)).padStart(2,'0');
  const s = String(sec%60).padStart(2,'0');
  return `${m}:${s}`;
}

function drawTravel(travel){
  clearTravelVisuals();
  activeTravel = travel;

  const from = layers.get(Number(travel.from_region_id))?.region;
  const to = layers.get(Number(travel.to_region_id))?.region;
  if(!from || !to) return;

  const backendPos = travel.current_position || { lat: Number(from.lat), lng: Number(from.lng) };
  const isReturning = travel.status === 'returning';
  const routeColor = isReturning ? '#ef4444' : '#60a5fa';
  const routeStart = isReturning ? [Number(backendPos.lat), Number(backendPos.lng)] : [Number(from.lat), Number(from.lng)];
  const routeEnd = isReturning ? [Number(from.lat), Number(from.lng)] : [Number(to.lat), Number(to.lng)];
  routeLine = L.polyline([routeStart, routeEnd],{color:routeColor,dashArray:'8,8',weight:3,pane:'routePane'}).addTo(map);
  planeMarker = L.marker([Number(backendPos.lat), Number(backendPos.lng)], {
    pane:'planePane',
    icon: L.divIcon({
      className:'plane-icon-wrap',
      html:'<div class="plane-ping"></div><i class="fa-solid fa-plane plane-icon"></i>',
      iconSize:[32,32],
      iconAnchor:[16,16]
    })
  }).addTo(map);
  planeMarker.setZIndexOffset(100000);
  planeMarker.on('click', () => {
    showPlanePopup();
    openTravelSheet();
  });
  planeMarker.bindPopup('');

  updateTravelVisuals();
}

function showPlanePopup(){
  if(!activeTravel || !planeMarker) return;
  planeMarker.getPopup().setContent(buildPlanePopupContent());
  planeMarker.openPopup();
}

function openTravelSheet(){
  if (!activeTravel) return;
  regionTitle.textContent = `${t('ui.traveling','Traveling')} #${activeTravel.from_region_id} → #${activeTravel.to_region_id}`;
  regionMeta.innerHTML = `${t('ui.remaining','remaining')}: ${fmt(Math.max(0, Number(activeTravel.remaining_seconds||0)))}`;
  modeHint.textContent = activeTravel.status === 'returning'
    ? t('ui.returning','Returning')
    : t('ui.traveling','Traveling');
  costHint.textContent = '';
  travelBtn.disabled = true;
  const cancelLocked = isCanceling || activeTravel.status === 'returning';
  sheetCancelBtn.style.display = 'block';
  sheetCancelBtn.disabled = cancelLocked;
  sheetCancelBtn.textContent = cancelLocked ? t('ui.canceling', 'Canceling...') : t('ui.cancel_travel', 'Cancel Travel');
  sheet.classList.add('open');
  backdrop.classList.add('open');
}

function buildPlanePopupContent(){
  const statusText = activeTravel?.status === 'returning' ? t('ui.returning', 'Returning') : t('ui.traveling', 'Traveling');
  return `
    <div style="min-width:220px">
      <strong>${statusText}</strong><br>
      ${t('ui.remaining', 'Remaining')}: ${fmt(Math.max(0, Number(activeTravel?.remaining_seconds || 0)))}<br>
      Route: ${activeTravel?.from_region_id} → ${activeTravel?.to_region_id}<br>
      <button id="popupCancelTravelBtn" style="margin-top:8px;background:#dc2626;color:#fff;border:0;padding:8px 10px;border-radius:8px;cursor:pointer">${isCanceling ? t('ui.canceling', 'Canceling...') : t('ui.cancel_travel', 'Cancel Travel')}</button>
    </div>
  `;
}

function updateTravelVisuals(){
  if(!activeTravel) return;
  const from = layers.get(Number(activeTravel.from_region_id))?.region;
  const to = layers.get(Number(activeTravel.to_region_id))?.region;
  if(!from || !to) return;

  const pos = activeTravel.current_position || { lat: Number(from.lat), lng: Number(from.lng) };
  const lat = Number(pos.lat);
  const lng = Number(pos.lng);
  if (planeMarker) planeMarker.setLatLng([lat,lng]);

  const remaining = Math.max(0, Number(activeTravel.remaining_seconds || 0));
  travelStatusEl.style.display = 'block';
  const statusText = activeTravel.status === 'returning' ? t('ui.returning', 'Returning') + '...' : t('ui.traveling', 'Traveling') + '...';
  travelStatusEl.textContent = `${statusText} ${fmt(remaining)} ${t('ui.remaining', 'remaining')}`;

  const routeColor = activeTravel.status === 'returning' ? '#ef4444' : '#60a5fa';
  if (routeLine) {
    const startPoint = activeTravel.status === 'returning'
      ? [lat, lng]
      : [Number(from.lat), Number(from.lng)];
    const endPoint = activeTravel.status === 'returning'
      ? [Number(from.lat), Number(from.lng)]
      : [Number(to.lat), Number(to.lng)];
    routeLine.setStyle({ color: routeColor });
    routeLine.setLatLngs([startPoint, endPoint]);
  }
}

async function completeTravelCheck(){
  const meRes = await fetch('/api/player/me');
  if(!meRes.ok) return;
  const payload = await meRes.json();
  if(payload.data){
    const prevTravel = activeTravel;
    currentRegionId = Number(payload.data.current_region_id);
    activeTravel = payload.data.active_travel;
    if(!activeTravel){
      const destinationId = finalDestinationFromTravel(prevTravel);
      const fromId = prevTravel ? Number(prevTravel.from_region_id) : 0;
      if (destinationId) applyPopulationTransfer(fromId, destinationId);
      clearTravelVisuals();
      const currentRegion = layers.get(currentRegionId)?.region;
      if(currentRegion && marker){
        marker.setLatLng([currentRegion.lat,currentRegion.lng]);
        map.panTo([currentRegion.lat,currentRegion.lng]);
      }
      refreshStyles();
      showToast(t('toast.arrived', 'Arrival complete.'));
    }
  }
}


function finalDestinationFromTravel(travel){
  if(!travel) return null;
  return travel.status === 'returning' ? Number(travel.from_region_id) : Number(travel.to_region_id);
}

function applyPopulationTransfer(fromRegionId, toRegionId){
  if(!fromRegionId || !toRegionId || fromRegionId===toRegionId) return;
  const fromObj = layers.get(Number(fromRegionId));
  const toObj = layers.get(Number(toRegionId));
  if(fromObj?.region){
    fromObj.region.population = Math.max(0, Number(fromObj.region.population||0)-1);
  }
  if(toObj?.region){
    toObj.region.population = Number(toObj.region.population||0)+1;
  }
  if(selectedRegion){
    const selectedObj = layers.get(Number(selectedRegion.id));
    if(selectedObj?.region){
      selectedRegion = selectedObj.region;
      regionMeta.innerHTML = `Owner Region: ${selectedRegion.owner_region_name || selectedRegion.country_name}<br>Resource: ${selectedRegion.resource_type}<br>Population: ${selectedRegion.population}`;
    }
  }
}

function clearTravelVisuals(){
  if(routeLine){ map.removeLayer(routeLine); routeLine = null; }
  if(planeMarker){ map.removeLayer(planeMarker); planeMarker = null; }
  travelStatusEl.style.display = activeTravel ? 'block' : 'none';
  if(!activeTravel) travelStatusEl.textContent = '';
}

async function syncTravelFromBackend(){
  if(!isAuthed) return;
  const meRes = await fetch('/api/player/me');
  if(!meRes.ok) return;
  const payload = await meRes.json();
  if(!payload.data) return;

  const latestTravel = payload.data.active_travel || null;
  currentRegionId = Number(payload.data.current_region_id);
  me = payload.data;
  playerHud.innerHTML = `Lv ${me.level} • Coins ${Number(me.coins).toFixed(0)} • Instant ${me.instant_energy}/${me.max_instant_energy}`;

  if(!latestTravel){
    const prevTravel = activeTravel;
    activeTravel = null;
    const destinationId = finalDestinationFromTravel(prevTravel);
    const fromId = prevTravel ? Number(prevTravel.from_region_id) : 0;
    if (destinationId) applyPopulationTransfer(fromId, destinationId);
    clearTravelVisuals();
    if (marker && payload.data.current_position) {
      marker.setLatLng([Number(payload.data.current_position.lat), Number(payload.data.current_position.lng)]);
    }
    refreshStyles();
    return;
  }

  activeTravel = latestTravel;
  if (marker && latestTravel.current_position) {
    marker.setLatLng([Number(latestTravel.current_position.lat), Number(latestTravel.current_position.lng)]);
  }
  if (!planeMarker || !routeLine) {
    drawTravel(activeTravel);
    return;
  }
  updateTravelVisuals();
  if (planeMarker && planeMarker.isPopupOpen() && planeMarker.getPopup()) {
    planeMarker.getPopup().setContent(buildPlanePopupContent());
  }
}

async function load(){
  const meRes = await fetch('/api/player/me');
  if (meRes.ok) {
    const parsed = await meRes.json();
    if (parsed.data) {
      me = parsed.data;
      isAuthed = true;
      currentRegionId = Number(me.current_region_id);
      activeTravel = me.active_travel || null;
      playerHud.style.display='block';
      playerHud.innerHTML = `Lv ${me.level} • Coins ${Number(me.coins).toFixed(0)} • Instant ${me.instant_energy}/${me.max_instant_energy}`;
    }
  }
  updateNavMode();

  const regions = (await fetch('/api/map/regions').then(r=>r.json())).data || [];
  regions.forEach(region=>{
    const geo = JSON.parse(region.polygon_json);
    const layer = L.geoJSON(geo,{style: styleFor(region)}).addTo(map);
    layer.on('click',()=> openSheet(region));
    layers.set(Number(region.id),{layer,region});
  });

  if (isAuthed && currentRegionId) {
    const markerLat = Number(me?.current_position?.lat ?? 0);
    const markerLng = Number(me?.current_position?.lng ?? 0);
    marker = L.circleMarker([markerLat, markerLng],{radius:8,color:'#fff',fillColor:'#0ea5e9',fillOpacity:1}).addTo(map);
    if (markerLat || markerLng) map.setView([markerLat, markerLng],5);
  }

  if(activeTravel){
    drawTravel(activeTravel);
  }
  if (isAuthed && !countdownTimer) {
    countdownTimer = setInterval(syncTravelFromBackend, 1000);
  }
}

travelBtn.onclick = async ()=>{
  if (!isAuthed) { showToast(t('toast.login_required', 'Please login first.'), true); return; }
  if(!selectedRegion || isActing || activeTravel) return;
  isActing = true;
  travelBtn.textContent = t('ui.starting_travel', 'Starting travel...');
  travelBtn.disabled = true;

  const result = await api('/api/region/action',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({region_id:selectedRegion.id, action:'travel'})
  });

  if(result.error){
    showToast(result.message || t(`errors.${result.error}`, result.error), true);
  } else {
    activeTravel = result.travel;
    me.instant_energy = Math.max(0, Number(me.instant_energy||0) - Number(result.travel.energy_cost||0));
    me.coins = Math.max(0, Number(me.coins||0) - Number(result.travel.cost_coins||0));
    playerHud.innerHTML = `Lv ${me.level} • Coins ${Number(me.coins).toFixed(0)} • Instant ${me.instant_energy}/${me.max_instant_energy}`;
    drawTravel(activeTravel);
    closeSheetPanel();
    showToast(t('toast.travel_started', 'Travel started.'));
  }

  isActing = false;
  travelBtn.textContent = t('ui.travel_to_region', 'Travel to this region');
};

async function cancelActiveTravel(){
  if (isCanceling || activeTravel?.status === 'returning') { showToast(t('toast.cancel_in_progress', 'Canceling...'), true); return; }
  isCanceling = true;
  sheetCancelBtn.disabled = true;
  sheetCancelBtn.textContent = t('ui.canceling', 'Canceling...');
  const res = await fetch('/api/player/cancel-travel', { method:'POST' });
  const json = await res.json();
  if (json.error) {
    showToast(json.message || t(`errors.${json.error}`, json.error), true);
    isCanceling = false;
    sheetCancelBtn.disabled = false;
    sheetCancelBtn.textContent = t('ui.cancel_travel', 'Cancel Travel');
    return;
  }
  showToast(json.message || t('toast.travel_reversed', 'Travel canceled, return started.'));
  activeTravel = json.travel || null;
  if (activeTravel) {
    drawTravel(activeTravel);
  } else {
    clearTravelVisuals();
  }
  closeSheetPanel();
  isCanceling = false;
  sheetCancelBtn.disabled = false;
  sheetCancelBtn.textContent = t('ui.cancel_travel', 'Cancel Travel');
}

function initSocket(){
  try {
    const socket = io(window.location.origin, { path: '/socket.io', transports:['websocket'], withCredentials:true, auth: { user_id: me?.id || '' } });
    socket.on('travel_progress', (evt)=> {
      if(!activeTravel || Number(evt.user_id) !== Number(me?.id)) return;
      activeTravel.progress_percent = Number(evt.progress_percent || 0);
      activeTravel.current_position = { lat: Number(evt.lat), lng: Number(evt.lng) };
      if (evt.status) activeTravel.status = evt.status;
      if (planeMarker && typeof evt.lat === 'number' && typeof evt.lng === 'number') {
        planeMarker.setLatLng([evt.lat, evt.lng]);
      }
      if (marker && typeof evt.lat === 'number' && typeof evt.lng === 'number') {
        marker.setLatLng([evt.lat, evt.lng]);
      }
      updateTravelVisuals();
      if(Number(evt.remaining_seconds || 0) <= 0) completeTravelCheck();
    });
    socket.on('travel_complete', (evt)=> {
      if(Number(evt.user_id) === Number(me?.id)) completeTravelCheck();
    });
  } catch (_) {}
}

closeSheetBtn.onclick = closeSheetPanel;
backdrop.onclick = closeSheetPanel;
sheetCancelBtn.onclick = cancelActiveTravel;
document.addEventListener('click', (evt) => {
  if (evt.target && evt.target.id === 'popupCancelTravelBtn') {
    cancelActiveTravel();
  }
});
langSelect.onchange = () => {
  LANG = langSelect.value || 'en';
  loadLang();
};
loadLang().then(() => load().then(initSocket));
</script>
</body></html>
