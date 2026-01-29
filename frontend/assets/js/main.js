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
  if(chartEl._chart){ chartEl._chart.destroy(); }
  chartEl._chart = new Chart(chartEl, { type:'line', data:{ labels, datasets }, options:{ plugins:{legend:{position:'bottom'}}, responsive:true, maintainAspectRatio:true, aspectRatio: 2.4, scales:{y:{beginAtZero:true, max:100, title:{display:true,text:'Yoğunluk %'}}} } });
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
  const bindPhotoClicks = () => {
    if(!(modal && modalImg)) return;
    const bsModal = new bootstrap.Modal(modal);
    document.querySelectorAll('.review-thumb').forEach(img => {
      img.addEventListener('click', ()=>{
        modalImg.src = img.dataset.full || img.src;
        bsModal.show();
      });
    });
  };
  bindPhotoClicks();

  const form = document.getElementById('userReviewForm');
  if(form){
    const extraInputs = Array.from(document.querySelectorAll('#extraInputs [data-extra-key]'));
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const payload = Object.fromEntries(new FormData(form).entries());
      payload.place_id = form.dataset.place;
      const extras = {};
      extraInputs.forEach(inp=>{
        const key = inp.dataset.extraKey;
        const val = (inp.value || '').trim();
        if(key && val){ extras[key] = val; }
      });
      payload.text_extra = extras;
      const res = await fetch('/includes/submit_review.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
      if(res.ok){
        Swal.fire({ icon:'success', title:'Teşekkürler', text:'Yorumunuz kaydedildi' });
        form.reset();
      } else {
        Swal.fire({ icon:'error', title:'Hata', text:'Yorum kaydedilemedi' });
      }
    });
  }

  const reviewBox = document.getElementById('reviews-container');
  const reviewPager = document.getElementById('reviews-pagination');
  const reviewTotal = document.getElementById('review-total');
  if(reviewBox){
    const limit = Number(reviewBox.dataset.limit || 10);
    const placeId = reviewBox.dataset.place;
    const loadReviews = async (page=1)=>{
      reviewBox.innerHTML = '<div class="text-muted">Yükleniyor...</div>';
      try{
        const res = await fetch(`/includes/reviews.php?place_id=${placeId}&s=${page}`);
        if(!res.ok) throw new Error();
        const data = await res.json();
        reviewBox.innerHTML = data.html || '<div class="text-muted">Yorum bulunamadı.</div>';
        bindPhotoClicks();
        if(reviewTotal && data.total !== undefined){
          reviewTotal.textContent = `Toplam ${data.total} yorum`;
        }
        if(reviewPager){
          reviewPager.innerHTML = '';
          const totalPages = Math.max(1, Number(data.total_pages||1));
          if(totalPages > 1){
            for(let i=1;i<=totalPages;i++){
              const btn = document.createElement('button');
              btn.type='button';
              btn.className = 'btn btn-sm ' + (i===page ? 'btn-primary' : 'btn-outline-primary');
              btn.textContent = i;
              btn.addEventListener('click', ()=> loadReviews(i));
              reviewPager.appendChild(btn);
            }
          }
        }
      }catch(err){
        reviewBox.innerHTML = '<div class="text-muted">Yorumlar getirilemedi.</div>';
      }
    };
    loadReviews(1);
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

  const searchLimit = Number(document.getElementById('search-card')?.dataset.limit || 0) || 0;

  function renderPagination(total, page, perPage){
    const container = document.getElementById('search-pagination');
    if(!container) return;
    container.innerHTML='';
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    if(totalPages <= 1) return;
    for(let i=1;i<=totalPages;i++){
      const btn = document.createElement('button');
      btn.type='button';
      btn.className = 'btn btn-sm ' + (i===page ? 'btn-primary' : 'btn-outline-primary');
      btn.textContent = i;
      btn.addEventListener('click', ()=> fetchList(i));
      container.appendChild(btn);
    }
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
      const cat = p.business_type || '';
      const catLink = p.category_slug ? `/kategoriler/${encodeURIComponent(p.category_slug)}` : '#';
      col.innerHTML = `<div class="card card-hover h-100"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <a class="fw-semibold text-decoration-none" title="${p.name}" href="/${p.slug}">${p.name}</a>
          <span class="badge bg-primary">⭐ ${rating}</span>
        </div>
        <div class="text-muted small mb-1">${p.formatted_address||''}</div>
        <div class="small d-flex gap-2 flex-wrap text-muted">
          <a class="text-decoration-none" title="${cat}" href="${catLink}">${cat}</a>
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

  async function fetchList(page=1){
    const params = new URLSearchParams(new FormData(queryForm));
    params.set('mode','list');
    params.set('page', page);
    params.set('per_page', searchLimit || 9);
    const res = await fetch(`/includes/places.php?${params.toString()}`);
    if(!res.ok) return;
    const json = await res.json();
    renderList(json.data || []);
    const noResults = document.getElementById('map-no-results');
    if(noResults){
      const hasData = Array.isArray(json.data) && json.data.length>0;
      noResults.classList.toggle('d-none', hasData);
    }
    if(json.total !== undefined){
      renderPagination(json.total, json.page || page, json.per_page || searchLimit || 9);
    }
  }

  async function fetchPlaces(){
    const params = new URLSearchParams(new FormData(queryForm));
    params.set('mode','map');
    const res = await fetch(`/includes/places.php?${params.toString()}`);
    if(!res.ok) return;
    const json = await res.json();
    renderMarkers(json.data || []);
    fetchList(1);
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
