/* LUNATIC — shared frontend helpers */
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

const Api = {
  async get(url) {
    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!r.ok) throw await this._err(r);
    return r.json();
  },
  async post(url, body = {}) {
    const r = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-Token': CSRF,
      },
      body: JSON.stringify(body),
    });
    if (!r.ok) throw await this._err(r);
    return r.json();
  },
  async _err(r) {
    const d = await r.json().catch(() => ({}));
    return new Error(d.error || ('HTTP ' + r.status));
  },
};

function esc(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));
}

function riskPill(level) {
  return `<span class="pill risk-${esc(level)}">${esc(level)}</span>`;
}

function toast(msg, type = 'info') {
  let box = document.getElementById('toastBox');
  if (!box) {
    box = document.createElement('div');
    box.id = 'toastBox';
    box.style.cssText = 'position:fixed;right:20px;bottom:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
    document.body.appendChild(box);
  }
  const el = document.createElement('div');
  const color = { info: 'var(--cyan)', ok: 'var(--green)', warn: 'var(--amber)', err: 'var(--red)' }[type] || 'var(--cyan)';
  el.className = 'glass';
  el.style.cssText = `padding:12px 16px;border-left:3px solid ${color};font-size:13px;max-width:340px;`;
  el.textContent = msg;
  box.appendChild(el);
  setTimeout(() => el.remove(), 4200);
}

const CHART_COLORS = ['#3d8bff', '#22d3ee', '#34d399', '#fbbf24', '#f43f5e', '#a78bfa'];
Chart && (Chart.defaults.color = '#8ea3c4', Chart.defaults.borderColor = 'rgba(80,130,200,0.12)');
