window.UX=(function(){
  const menu=[['/dashboard','fa-chart-line','Dashboard'],['/map','fa-earth-europe','Map'],['/profile','fa-user','Profile'],['/rankings','fa-trophy','Rankings']];
  function shell(active,content){
    document.body.innerHTML=`<div class='app'><aside class='sidebar'><div class='logo'><i class='fa-solid fa-chess-queen'></i>GeoPolity Nexus</div><nav class='side-nav'>${menu.map(m=>`<a href='${m[0]}' class='${active===m[0]?'active':''}'><i class='fa-solid ${m[1]}'></i>${m[2]}</a>`).join('')}</nav></aside><main class='main'><header class='topbar'><strong>${menu.find(m=>m[0]===active)?.[2]||'Game'}</strong><div class='user-chip'><select id='uxLang'><option value='tr'>TR</option><option value='en'>EN</option></select><span id='uxUser'>Commander</span></div></header><section class='page' id='pageRoot'>${content||''}</section></main></div><nav class='mobile-nav'>${menu.map(m=>`<a href='${m[0]}' class='${active===m[0]?'active':''}'><i class='fa-solid ${m[1]}'></i><small>${m[2]}</small></a>`).join('')}</nav>`;
    const l=localStorage.getItem('lang')||'en'; const sel=document.getElementById('uxLang'); if(sel){sel.value=l; sel.onchange=()=>{localStorage.setItem('lang',sel.value); location.reload();};}
  }
  async function api(url,opt){const r=await fetch(url,opt||{});const j=await r.json().catch(()=>({}));if(!r.ok||j.error)throw new Error(j.message||j.error||`HTTP ${r.status}`);return j.data??j;}
  const fmt=n=>Number(n||0).toLocaleString();
  return {shell,api,fmt};
})();
