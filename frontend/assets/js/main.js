(function(){
  const mapEl = document.getElementById('map');
  if (!mapEl) return;

  const queryForm = document.getElementById('map-search-form');
  const markers = L.markerClusterGroup({ showCoverageOnHover:false, disableClusteringAtZoom: 17 });
  const map = L.map('map');
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap katılımcıları'
  }).addTo(map);
  map.setView([39.0, 35.0], 6);
  map.addLayer(markers);

  function categoryIcon(cat){
    const colors = ['#0d6efd','#dc3545','#198754','#6f42c1','#fd7e14','#20c997'];
    const color = colors[Math.abs(hash(cat)) % colors.length];
    return L.divIcon({
      className: 'custom-div-icon',
      html: `<div style="background:${color};" class="marker-pin"></div><span class="marker-label">${(cat||'').substring(0,2).toUpperCase()}</span>`
    });
  }
  function hash(str){ let h=0; for(let i=0;i<str.length;i++){ h=((h<<5)-h)+str.charCodeAt(i); h|=0;} return h; }

  function renderMarkers(data){
    markers.clearLayers();
    if(!Array.isArray(data)) return;
    data.forEach(p=>{
      if(!p.latitude || !p.longitude) return;
      const marker = L.marker([parseFloat(p.latitude), parseFloat(p.longitude)], { icon: categoryIcon(p.business_type || '') });
      marker.bindPopup(`<div class="map-card"><strong>${p.name}</strong><br><small>${p.business_type||''}</small><br>${p.formatted_address||''}<br><a class="btn btn-sm btn-primary mt-2" href="/${p.slug}">Detaya git</a></div>`);
      markers.addLayer(marker);
    });
    if (markers.getLayers().length) {
      map.fitBounds(markers.getBounds(), { padding: [20,20] });
    }
  }

  async function fetchPlaces(){
    const params = new URLSearchParams(new FormData(queryForm));
    const res = await fetch(`/frontend/api/places.php?${params.toString()}`);
    if(!res.ok) return;
    const json = await res.json();
    renderMarkers(json.data || []);
  }

  if(queryForm){
    queryForm.addEventListener('submit', function(e){ e.preventDefault(); fetchPlaces(); });
  }
  fetchPlaces();
})();
