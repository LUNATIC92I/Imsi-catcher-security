<div class="glass panel mb">
  <div class="flex-between">
    <div><h3 style="margin:0">SOC / SIEM Integration</h3>
      <div class="note">Export des alertes simulées au format JSON SIEM. Champ <code>"simulation": true</code> obligatoire.</div></div>
    <button class="btn btn-primary" id="btnExport">Générer l'export SIEM</button>
  </div>
</div>

<div class="grid grid-2">
  <div class="glass panel">
    <h3>Alertes récentes</h3>
    <div class="scroll-y">
      <table class="lab"><thead><tr><th>ID</th><th>Type</th><th>Sévérité</th><th>Statut</th></tr></thead>
      <tbody id="alertsBody"><tr><td colspan="4" class="note">Chargement…</td></tr></tbody></table>
    </div>
  </div>
  <div class="glass panel">
    <h3>Payload SIEM (JSON)</h3>
    <div class="terminal mono" id="jsonOut" style="height:420px">Cliquez sur « Générer l'export SIEM »…</div>
  </div>
</div>

<script>
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
</script>
