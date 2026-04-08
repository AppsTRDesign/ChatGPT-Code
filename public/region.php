<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>Region</title><link rel='stylesheet' href='/styles.css'><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css'></head><body><div class='page'><div id='content' class='card'>Loading...</div></div><script src='/ui.js'></script><script>
GameUI.mountShell('');
(async()=>{
 const id = Number(location.pathname.split('/').pop()||0); if(!id) return content.textContent='Invalid region id';
 try{
   const r = await GameUI.api('/api/map/region-detail?id='+id);
   content.innerHTML=`<h2>${r.name}</h2><p class='muted'>${r.country_name}</p>
   <div class='grid cards'><div class='card'><b>Population</b><div>${GameUI.fmt(r.population)}</div></div><div class='card'><b>Resource</b><div>${r.resource_type||'-'}</div></div><div class='card'><b>Total Buildings</b><div>${GameUI.fmt(r.total_score||0)}</div></div></div>
   <h3>Infrastructure</h3><table class='table'><tr><th>Airport</th><th>Army</th><th>Hospital</th><th>Education</th><th>Port</th></tr><tr><td>${r.airport_level}</td><td>${r.army_level}</td><td>${r.hospital_level}</td><td>${r.education_level}</td><td>${r.port_level}</td></tr></table>
   <h3>Neighbors</h3><div class='list'>${(r.neighbors||[]).map(n=>`<a class='list-row' href='/region/${n.id}'>${n.name}<span>${n.country_name||''}</span></a>`).join('')||'<div class="muted">No neighbors.</div>'}</div>
   <div style='margin-top:12px'><button id='travelBtn' class='primary-btn'><i class='fa-solid fa-plane'></i> Travel</button> <small id='travelHint' class='muted'></small></div>`;
   document.getElementById('travelBtn').onclick=async()=>{try{await GameUI.api('/api/region/action',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({region_id:id,action:'travel'})});travelHint.textContent='Travel started';}catch(e){travelHint.textContent=e.message;}};
 }catch(e){content.textContent=e.message}
})();
</script></body></html>
