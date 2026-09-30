/* LUNATIC — Attack Simulation Replay */
let lastAlertId = null;

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

async function startSimulation() {
  const btn = document.getElementById('btnStart');
  const scenario = document.getElementById('scenarioSel').value;
  const tl = document.getElementById('timeline');
  const analysis = document.getElementById('analysis');
  const riskBadge = document.getElementById('riskBadge');

  btn.disabled = true;
  tl.innerHTML = '';
  analysis.innerHTML = '<span class="note">Simulation en cours…</span>';
  riskBadge.innerHTML = '';
  setDefenseButtons(true);

  let res;
  try {
    res = await Api.post('/api/v1/simulation/scenario', { scenario });
  } catch (e) { toast(e.message, 'err'); btn.disabled = false; return; }

  const r = res.result;

  // Reveal timeline step by step.
  for (const step of r.timeline) {
    const li = document.createElement('li');
    if (step.type === 'detection' || step.type === 'alert') li.className = 'crit';
    li.innerHTML = `
      <div class="tl-title">${esc(step.title)} <span class="tl-ref">${esc(step.ref)}</span></div>
      <div class="tl-desc">${esc(step.description)}</div>`;
    tl.appendChild(li);
    await sleep(650);
  }

  // Show analysis
  const a = r.analysis;
  riskBadge.innerHTML = riskPill(a.risk_level) + ` <span class="chip">score ${a.score}/100</span>`;
  analysis.innerHTML = `
    <div class="mb"><strong>Type d'anomalie :</strong> ${esc(a.anomaly_type)}</div>
    <table class="lab"><thead><tr><th>Règle</th><th>Poids</th><th>Raison</th></tr></thead><tbody>
    ${a.findings.map(f => `<tr><td>${esc(f.rule)}</td><td class="mono">+${f.weight}</td><td>${esc(f.reason)}</td></tr>`).join('')}
    </tbody></table>`;

  lastAlertId = r.alert_id;
  setDefenseButtons(false);
  toast(`Alerte #${r.alert_id} générée (${a.risk_level})`, 'warn');
  btn.disabled = false;
}

function setDefenseButtons(disabled) {
  document.querySelectorAll('[data-act]').forEach(b => b.disabled = disabled);
}

document.querySelectorAll('[data-act]').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!lastAlertId) return;
    try {
      const r = await Api.post('/api/v1/defensive/respond', { action: btn.dataset.act, alert_id: lastAlertId });
      toast(r.message, 'ok');
    } catch (e) { toast(e.message, 'err'); }
  });
});

document.getElementById('btnStart').addEventListener('click', startSimulation);
