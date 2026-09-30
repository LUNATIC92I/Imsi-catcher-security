<div class="glass panel mb">
  <div class="flex-between">
    <div><h3 style="margin:0">Audit Log</h3>
      <div class="note">Utilisateur, action, scénario, date. Aucun identifiant télécom réel n'est jamais enregistré.</div></div>
    <button class="btn" id="btnReload">Rafraîchir</button>
  </div>
</div>
<div class="glass panel">
  <div class="scroll-y">
    <table class="lab"><thead><tr>
      <th>ID</th><th>Date</th><th>Utilisateur</th><th>Action</th><th>Scénario</th><th>Résultat</th><th>Sim</th>
    </tr></thead><tbody id="auditBody"><tr><td colspan="7" class="note">Chargement…</td></tr></tbody></table>
  </div>
</div>
<script>
async function loadAudit() {
  try {
    const d = await Api.get('/api/v1/audit');
    const b = document.getElementById('auditBody');
    if (!d.data.length) { b.innerHTML = '<tr><td colspan="7" class="note">Aucune entrée.</td></tr>'; return; }
    b.innerHTML = d.data.map(a => `<tr>
      <td class="mono">#${a.id}</td><td class="mono">${esc(a.created_at)}</td>
      <td>${esc(a.username || '—')}</td><td><span class="chip">${esc(a.action)}</span></td>
      <td>${esc(a.scenario || '—')}</td><td>${esc(a.result || '—')}</td>
      <td><span class="pill risk-LOW">SIM</span></td></tr>`).join('');
  } catch (e) { toast(e.message, 'err'); }
}
document.getElementById('btnReload').addEventListener('click', loadAudit);
loadAudit();
</script>
