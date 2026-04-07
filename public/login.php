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
      <h1>Welcome Back</h1>
      <p class="muted">Login to continue your geopolitical campaign.</p>
      <input id="email" type="email" placeholder="Email">
      <input id="password" type="password" placeholder="Password">
      <button id="loginBtn" class="primary-btn">Login</button>
      <p class="muted">No account? <a href="/register">Register here</a></p>
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

loginBtn.onclick = async () => {
  const btn = loginBtn;
  btn.disabled = true;
  btn.textContent = 'Logging in...';
  try {
    const res = await fetch('/api/auth/login', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ email: email.value.trim(), password: password.value })
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
      toast(data.error || 'Login failed', true);
      return;
    }
    toast('Login successful');
    setTimeout(()=> location.href='/dashboard', 450);
  } catch {
    toast('Network error', true);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Login';
  }
};
</script>
</body>
</html>
