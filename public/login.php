<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - NoaSoft MMO</title>
  <link rel="stylesheet" href="/styles.css">
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-card glass">
      <select id="langSelect" style="max-width:120px;float:right"><option value="tr">TR</option><option value="en">EN</option></select>
      <h1 id="titleText">Welcome Back</h1>
      <p class="muted" id="subText">Login to continue your geopolitical campaign.</p>
      <input id="email" type="email" placeholder="Email">
      <input id="password" type="password" placeholder="Password">
      <button id="loginBtn" class="primary-btn">Login</button>
      <p class="muted"><span id="noAccText">No account?</span> <a href="/register" id="regLinkText">Register here</a></p>
    </div>
  </div>
  <div id="toast" class="toast"></div>
<script>
function toast(msg, error=false){
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = `toast show ${error ? 'error' : ''}`;
  setTimeout(()=> t.className='toast', 2400);
}
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
  titleText.textContent = t('auth.login_title','Welcome Back');
  subText.textContent = t('auth.login_sub','Login to continue your geopolitical campaign.');
  email.placeholder = t('auth.email','Email');
  password.placeholder = t('auth.password','Password');
  loginBtn.textContent = t('auth.login','Login');
  noAccText.textContent = t('auth.no_account','No account?');
  regLinkText.textContent = t('auth.register_here','Register here');
}
langSelect.onchange = ()=>{LANG=langSelect.value||'en'; loadLang();};

loginBtn.onclick = async () => {
  const btn = loginBtn;
  btn.disabled = true;
  btn.textContent = t('auth.logging_in','Logging in...');
  try {
    const res = await fetch('/api/auth/login', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ email: email.value.trim(), password: password.value })
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
      toast(data.message || t('auth.login_failed','Login failed'), true);
      return;
    }
    toast(t('auth.login_success','Login successful'));
    setTimeout(()=> location.href='/dashboard', 450);
  } catch {
    toast(t('toast.request_failed','Request failed'), true);
  } finally {
    btn.disabled = false;
    btn.textContent = t('auth.login','Login');
  }
};
loadLang();
</script>
</body>
</html>
