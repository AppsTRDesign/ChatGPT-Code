function hash(str){ let h=0; for(let i=0;i<str.length;i++){ h=((h<<5)-h)+str.charCodeAt(i); h|=0;} return h; }

function renderBusyChart(){
  const chartEl = document.getElementById('busyChart');
  if(!chartEl || typeof Chart === 'undefined') return;
  const hours = JSON.parse(chartEl.dataset.hours||'{}');
  const dayOrder = ['Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi'];
  const labels = Array.from({length:18}, (_,i)=> String(i+6).padStart(2,'0') + ':00');
  const palette = ['#0d6efd','#198754','#dc3545','#6f42c1','#fd7e14','#20c997','#6c757d'];
  const datasets = [];
  dayOrder.forEach((day,idx)=>{
    if(!hours[day]) return;
    const values = labels.map(l=>{
      const row = (hours[day]||[]).find(item=> (item.saat||item.hour||item.time||'').startsWith(l.slice(0,2)));
      const raw = row ? (row.busy || row.yogunluk || row['yoğunluk'] || row.load || row.value || '0') : '0';
      return parseInt(String(raw).replace(/[^0-9]/g,'')) || 0;
    });
    datasets.push({ label: day, data: values, backgroundColor: palette[idx%palette.length], borderColor: palette[idx%palette.length], fill:false, tension:0.3 });
  });
  new Chart(chartEl, { type:'line', data:{ labels, datasets }, options:{ plugins:{legend:{position:'bottom'}}, responsive:true, scales:{y:{beginAtZero:true, title:{display:true,text:'Yoğunluk %'}}} } });
}

function initDetailInteractions(){
  const visit = document.getElementById('visit-tracker');
  if(visit){
    const pid = visit.dataset.place;
    fetch('/includes/visit.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({place_id: pid}) }).catch(()=>{});
  }

  renderBusyChart();

  const modal = document.getElementById('photoModal');
  const modalImg = document.getElementById('photoModalImg');
  if(modal && modalImg){
    const bsModal = new bootstrap.Modal(modal);
    document.querySelectorAll('.review-thumb').forEach(img => {
      img.addEventListener('click', ()=>{
        modalImg.src = img.dataset.full || img.src;
        bsModal.show();
      });
    });
  }

  const form = document.getElementById('userReviewForm');
  if(form){
    const tagLabels = Array.from(document.querySelectorAll('#extraTags label'));
    tagLabels.forEach(label => {
      const input = label.querySelector('input');
      if(!input) return;
      label.addEventListener('click', ()=>{
        input.checked = !input.checked;
        label.classList.toggle('active', input.checked);
      });
    });
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const payload = Object.fromEntries(new FormData(form).entries());
      payload.place_id = form.dataset.place;
      const extras = [];
      tagLabels.forEach(label=>{ const input = label.querySelector('input'); if(input && input.checked) extras.push(input.value); });
      if(payload.text_extra){
        payload.text_extra.split(',').map(s=>s.trim()).filter(Boolean).forEach(v=>extras.push(v));
      }
      payload.text_extra = extras;
      const res = await fetch('/includes/submit_review.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
      if(res.ok){
        Swal.fire({ icon:'success', title:'Teşekkürler', text:'Yorumunuz kaydedildi' });
        form.reset();
        tagLabels.forEach(l=>l.classList.remove('active'));
      } else {
        Swal.fire({ icon:'error', title:'Hata', text:'Yorum kaydedilemedi' });
      }
    });
  }
}

function initMap(){
  const mapEl = document.getElementById('map');
  if (!mapEl) return;

  const queryForm = document.getElementById('map-search-form');
  const resultsEl = document.getElementById('map-results');
  const markers = L.markerClusterGroup({ showCoverageOnHover:false, disableClusteringAtZoom: 17 });
  const map = L.map('map');
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap katılımcıları'
  }).addTo(map);
  map.setView([39.0, 35.0], 6);
  map.addLayer(markers);

  function categoryIcon(cat){
    const colors = ['#0d6efd','#dc3545','#198754','#6f42c1','#fd7e14','#20c997'];
    const color = colors[Math.abs(hash(cat||'')) % colors.length];
    return L.divIcon({
      className: 'custom-div-icon',
      html: `<div style="background:${color};" class="marker-pin"></div><span class="marker-label">${(cat||'').substring(0,2).toUpperCase()}</span>`
    });
  }

  function renderList(data){
    if(!resultsEl) return;
    resultsEl.innerHTML = '';
    if(!data || !data.length){
      resultsEl.innerHTML = '<div class="col-12 text-muted small">Sonuç bulunamadı.</div>';
      return;
    }
    data.forEach(p=>{
      const col = document.createElement('div');
      col.className = 'col-12 col-md-6 col-lg-4';
      const rating = Number(p.combined_rating ?? p.rating ?? 0).toFixed(1);
      const views = Number(p.views ?? 0);
      const totalReviews = Number(p.total_reviews ?? 0);
      col.innerHTML = `<div class="card card-hover h-100"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <a class="fw-semibold text-decoration-none" href="/${p.slug}">${p.name}</a>
          <span class="badge bg-primary">⭐ ${rating}</span>
        </div>
        <div class="text-muted small mb-1">${p.formatted_address||''}</div>
        <div class="small d-flex gap-2 flex-wrap text-muted">
          <span>${p.business_type||''}</span>
          <span>👁 ${views}</span>
          <span>💬 ${totalReviews}</span>
        </div>
      </div></div>`;
      resultsEl.appendChild(col);
    });
  }

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
    const res = await fetch(`/includes/places.php?${params.toString()}`);
    if(!res.ok) return;
    const json = await res.json();
    renderMarkers(json.data || []);
    renderList(json.data || []);
  }

  if(queryForm){
    queryForm.addEventListener('submit', function(e){ e.preventDefault(); fetchPlaces(); });
  }
  fetchPlaces();
}

document.addEventListener('DOMContentLoaded', ()=>{
  initMap();
  initDetailInteractions();
});
