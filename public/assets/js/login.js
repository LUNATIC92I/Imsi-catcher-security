/* LUNATIC — login (external to satisfy strict CSP, no inline scripts) */
document.getElementById('loginForm').addEventListener('submit', async (ev) => {
  ev.preventDefault();
  const err = document.getElementById('err');
  err.style.display = 'none';
  const fd = new FormData(ev.target);
  try {
    const res = await fetch('/login', {
      method: 'POST',
      headers: { 'X-CSRF-Token': fd.get('csrf_token'), 'Accept': 'application/json' },
      body: fd,
    });
    const data = await res.json().catch(() => ({}));
    if (res.ok && data.ok) {
      window.location = data.redirect || '/dashboard';
    } else {
      err.textContent = data.error || 'Échec de la connexion';
      err.style.display = 'block';
    }
  } catch (e) {
    err.textContent = 'Erreur réseau';
    err.style.display = 'block';
  }
});
