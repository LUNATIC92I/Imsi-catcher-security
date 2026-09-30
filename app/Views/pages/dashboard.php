<?php $page_script = 'dashboard'; ?>

<div class="grid grid-4 mb">
  <div class="stat glass accent-blue"><div class="k">Appareils simulés</div><div class="v" id="s-devices">—</div><span class="spark">📱</span></div>
  <div class="stat glass accent-cyan"><div class="k">Cellules actives</div><div class="v" id="s-cells">—</div><span class="spark">📡</span></div>
  <div class="stat glass accent-red"><div class="k">Cellules suspectes</div><div class="v" id="s-rogue">—</div><span class="spark">⚠</span></div>
  <div class="stat glass accent-amber"><div class="k">Alertes ouvertes</div><div class="v" id="s-alerts">—</div><span class="spark">🚨</span></div>
</div>

<div class="grid grid-4 mb">
  <div class="stat glass accent-green"><div class="k">Stations légitimes</div><div class="v" id="s-legit">—</div></div>
  <div class="stat glass"><div class="k">Événements</div><div class="v" id="s-events">—</div></div>
  <div class="stat glass"><div class="k">Anomalies</div><div class="v" id="s-anom">—</div></div>
  <div class="stat glass"><div class="k">Handovers</div><div class="v" id="s-ho">—</div></div>
</div>

<div class="grid grid-2 mb">
  <div class="glass panel">
    <div class="flex-between mb"><h3 style="margin:0">Activité réseau (24h simulées)</h3><span class="chip">temps réel simulé</span></div>
    <canvas id="chartEvents" height="120"></canvas>
  </div>
  <div class="glass panel">
    <h3>Répartition par technologie</h3>
    <canvas id="chartTech" height="120"></canvas>
  </div>
</div>

<div class="grid grid-2 mb">
  <div class="glass panel">
    <h3>Niveau de risque des anomalies</h3>
    <canvas id="chartRisk" height="120"></canvas>
  </div>
  <div class="glass panel">
    <div class="flex-between mb"><h3 style="margin:0">Console SOC simulée</h3>
      <button class="btn btn-primary" id="btnRefresh">Rafraîchir</button></div>
    <div class="terminal" id="term"></div>
  </div>
</div>

<div class="glass panel">
  <div class="flex-between mb"><h3 style="margin:0">Derniers événements de connexion</h3>
    <span class="chip">source : réseau virtuel</span></div>
  <div class="scroll-y">
    <table class="lab"><thead><tr>
      <th>ID</th><th>Timestamp</th><th>Appareil</th><th>Cellule</th><th>Type</th><th>Tech</th><th>Signal</th><th>Risque</th>
    </tr></thead><tbody id="eventsBody"><tr><td colspan="8" class="note">Chargement…</td></tr></tbody></table>
  </div>
</div>
