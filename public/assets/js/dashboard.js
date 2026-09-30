/* LUNATIC — dashboard */
let chartEvents, chartTech, chartRisk;

function termLine(term, text, cls = '') {
  const ts = new Date().toISOString().substr(11, 8);
  const line = document.createElement('div');
  line.className = 't-line';
  line.innerHTML = `<span class="t-ts">[${ts}]</span> <span class="${cls}">${esc(text)}</span>`;
  term.appendChild(line);
  term.scrollTop = term.scrollHeight;
}

async function loadSummary() {
  const d = await Api.get('/api/v1/stats/summary');
  const s = d.stats;
  document.getElementById('s-devices').textContent = s.devices;
  document.getElementById('s-cells').textContent = s.cells_active;
  document.getElementById('s-rogue').textContent = s.rogue_cells;
  document.getElementById('s-alerts').textContent = s.alerts_open;
  document.getElementById('s-legit').textContent = s.legit_cells;
  document.getElementById('s-events').textContent = s.events;
  document.getElementById('s-anom').textContent = s.anomalies;
  document.getElementById('s-ho').textContent = s.handovers;

  // Tech doughnut
  const techLabels = d.by_tech.map(r => r.technology);
  const techData = d.by_tech.map(r => +r.n);
  chartTech && chartTech.destroy();
  chartTech = new Chart(document.getElementById('chartTech'), {
    type: 'doughnut',
    data: { labels: techLabels, datasets: [{ data: techData, backgroundColor: CHART_COLORS, borderWidth: 0 }] },
    options: { plugins: { legend: { position: 'right' } }, cutout: '62%' }
  });

  // Risk bar
  const riskOrder = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
  const riskMap = Object.fromEntries(d.by_risk.map(r => [r.risk_level, +r.n]));
  chartRisk && chartRisk.destroy();
  chartRisk = new Chart(document.getElementById('chartRisk'), {
    type: 'bar',
    data: {
      labels: riskOrder,
      datasets: [{ data: riskOrder.map(l => riskMap[l] || 0),
        backgroundColor: ['#34d399', '#fbbf24', '#f43f5e', '#b91c3c'] }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });
}

async function loadTimeseries() {
  const d = await Api.get('/api/v1/stats/timeseries');
  const labels = d.series.map(r => r.bucket.substr(11, 5));
  chartEvents && chartEvents.destroy();
  chartEvents = new Chart(document.getElementById('chartEvents'), {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label: 'Événements', data: d.series.map(r => +r.events), borderColor: '#3d8bff',
          backgroundColor: 'rgba(61,139,255,.15)', fill: true, tension: .35 },
        { label: 'Haut risque', data: d.series.map(r => +r.high), borderColor: '#f43f5e',
          backgroundColor: 'rgba(244,63,94,.12)', fill: true, tension: .35 },
      ]
    },
    options: { plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
  });
}

async function loadEvents() {
  const d = await Api.get('/api/v1/events');
  const body = document.getElementById('eventsBody');
  const term = document.getElementById('term');
  if (!d.data.length) { body.innerHTML = '<tr><td colspan="8" class="note">Aucun événement. Lancez le seed / un scénario.</td></tr>'; return; }
  body.innerHTML = d.data.slice(0, 40).map(e => `
    <tr>
      <td class="mono">#${e.id}</td>
      <td class="mono">${esc(e.created_at)}</td>
      <td>${esc(e.device_code)}</td>
      <td>${esc(e.cell_code || '—')}</td>
      <td><span class="chip">${esc(e.event_type)}</span></td>
      <td>${esc(e.technology || '—')}</td>
      <td class="mono">${esc(e.signal_dbm ?? '—')} dBm</td>
      <td>${riskPill(e.risk_level)}</td>
    </tr>`).join('');
  d.data.slice(0, 8).forEach(e => {
    const cls = (e.risk_level === 'HIGH' || e.risk_level === 'CRITICAL') ? 't-crit'
      : (e.risk_level === 'MEDIUM' ? 't-warn' : 't-info');
    termLine(term, `${e.event_type.toUpperCase()} ${e.device_code} → ${e.cell_code || 'n/a'} [${e.risk_level}]`, cls);
  });
}

async function refreshAll() {
  try {
    await Promise.all([loadSummary(), loadTimeseries(), loadEvents()]);
  } catch (e) { toast(e.message, 'err'); }
}

document.getElementById('btnRefresh').addEventListener('click', () => {
  document.getElementById('term').innerHTML = '';
  refreshAll();
  toast('Dashboard rafraîchi', 'ok');
});

refreshAll();
setInterval(refreshAll, 15000); // simulated real-time refresh
