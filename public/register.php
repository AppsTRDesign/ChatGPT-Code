<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register - NoaSoft MMO</title>
  <link rel="stylesheet" href="/styles.css">
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-card glass">
      <select id="langSelect" style="max-width:120px;float:right"><option value="tr">TR</option><option value="en">EN</option></select>
      <h1 id="titleText">Create Account</h1>
      <p class="muted" id="subText">Join the geopolitical simulation.</p>
      <input id="username" type="text" placeholder="Username">
      <input id="email" type="email" placeholder="Email">
      <input id="password" type="password" placeholder="Password (8+ chars)">
      <button id="registerBtn" class="primary-btn">Create account</button>
      <p class="muted"><span id="hasAccText">Already have an account?</span> <a href="/login" id="loginLinkText">Login</a></p>
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
  titleText.textContent=t('auth.register_title','Create Account');
  subText.textContent=t('auth.register_sub','Join the geopolitical simulation.');
  username.placeholder=t('auth.username','Username');
  email.placeholder=t('auth.email','Email');
  password.placeholder=t('auth.password_hint','Password (8+ chars)');
  registerBtn.textContent=t('auth.create_account','Create account');
  hasAccText.textContent=t('auth.have_account','Already have an account?');
  loginLinkText.textContent=t('auth.login','Login');
}
langSelect.onchange=()=>{LANG=langSelect.value||'en';loadLang();};

registerBtn.onclick = async () => {
  const btn = registerBtn;
  btn.disabled = true;
  btn.textContent = t('auth.creating','Creating...');
  try {
    const res = await fetch('/api/auth/register', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ username: username.value.trim(), email: email.value.trim(), password: password.value })
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
      toast(data.message || t('auth.register_failed','Registration failed'), true);
      return;
    }
    toast(t('auth.register_success','Account created'));
    setTimeout(()=> location.href='/dashboard', 450);
  } catch {
    toast(t('toast.request_failed','Request failed'), true);
  } finally {
    btn.disabled = false;
    btn.textContent = t('auth.create_account','Create account');
  }
};
loadLang();
</script>
</body>
</html>
