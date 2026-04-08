(function(){
  const LINKS = [
    { href:'/dashboard', label:'Dashboard', icon:'fa-solid fa-chart-line' },
    { href:'/map', label:'Map', icon:'fa-solid fa-earth-europe' },
    { href:'/profile', label:'Profile', icon:'fa-solid fa-user' },
    { href:'/rankings', label:'Rankings', icon:'fa-solid fa-trophy' }
  ];

  window.GameUI = {
    mountShell(active){
      const top = document.createElement('header');
      top.className = 'topbar glass';
      top.innerHTML = `
        <div class="brand"><i class="fa-solid fa-chess-king"></i> GeoPolity</div>
        <nav class="topnav">${LINKS.map(l=>`<a href="${l.href}" class="${active===l.href?'active':''}"><i class="${l.icon}"></i><span>${l.label}</span></a>`).join('')}</nav>
        <div class="top-right"><select id="globalLang"><option value="tr">TR</option><option value="en">EN</option></select><a href="/profile" class="avatar-chip"><i class="fa-solid fa-circle-user"></i></a></div>
      `;
      document.body.prepend(top);

      const bottom = document.createElement('nav');
      bottom.className = 'mobile-nav glass';
      bottom.innerHTML = LINKS.map(l=>`<a href="${l.href}" class="${active===l.href?'active':''}"><i class="${l.icon}"></i><small>${l.label}</small></a>`).join('');
      document.body.appendChild(bottom);

      const lang = localStorage.getItem('lang') || 'en';
      const sel = document.getElementById('globalLang');
      if (sel) {
        sel.value = lang;
        sel.onchange = ()=>{ localStorage.setItem('lang', sel.value); location.reload(); };
      }
    },
    async api(url, opt={}){
      const res = await fetch(url, opt);
      const json = await res.json().catch(()=>({}));
      if (!res.ok || json.error) throw new Error(json.message || json.error || `HTTP ${res.status}`);
      return json.data ?? json;
    },
    rankBadge(i){ return i===0?'🥇':i===1?'🥈':i===2?'🥉':`#${i+1}`; },
    fmt(n){ return Number(n||0).toLocaleString(); }
  };
})();
