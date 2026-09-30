async function loadAlerts() {
  const d = await Api.get('/api/v1/alerts');
  const b = document.getElementById('alertsBody');
  if (!d.data.length) { b.innerHTML = '<tr><td colspan="4" class="note">Aucune alerte.</td></tr>'; return; }
  b.innerHTML = d.data.slice(0, 50).map(a => `
    <tr><td class="mono">#${a.id}</td><td>${esc(a.event_type)}</td>
    <td>${riskPill(a.severity)}</td><td><span class="chip">${esc(a.status)}</span></td></tr>`).join('');
}
document.getElementById('btnExport').addEventListener('click', async () => {
  try {
    const d = await Api.get('/api/v1/soc/export');
    document.getElementById('jsonOut').textContent = JSON.stringify(d.siem_events, null, 2);
    toast(`${d.siem_events.length} événements SIEM générés (simulation:true)`, 'ok');
  } catch (e) { toast(e.message, 'err'); }
});
loadAlerts();
