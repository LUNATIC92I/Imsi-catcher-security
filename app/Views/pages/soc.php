<?php $page_script = 'soc'; ?>
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
