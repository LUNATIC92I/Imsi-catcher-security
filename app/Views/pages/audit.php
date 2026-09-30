<?php $page_script = 'audit'; ?>
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