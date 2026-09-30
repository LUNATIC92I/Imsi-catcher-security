<?php $page_script = 'map'; ?>
<div class="glass panel mb">
  <div class="flex-between">
    <div><h3 style="margin:0">Carte du réseau virtuel</h3>
      <div class="note">BTS, cellules, appareils et stations suspectes — coordonnées 100% artificielles.</div></div>
    <div class="flex">
      <button class="btn" id="btnReload">Recharger</button>
      <button class="btn btn-danger" id="btnSpawn">+ Rogue cell simulée</button>
    </div>
  </div>
</div>
<div class="glass panel"><div id="map"></div></div>
<div class="glass panel mt">
  <h3>Légende</h3>
  <div class="flex" style="flex-wrap:wrap;gap:18px">
    <span class="chip" style="color:#3d8bff">● BTS légitime</span>
    <span class="chip" style="color:#22d3ee">● Zone de couverture</span>
    <span class="chip" style="color:#34d399">● Appareil virtuel</span>
    <span class="chip" style="color:#f43f5e">● Station suspecte (rogue)</span>
  </div>
</div>
